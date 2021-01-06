<?php

namespace Drupal\intercept_room_reservation\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\intercept_core\ReservationManager;
use Drupal\intercept_core\Plugin\Field\FieldWidget\DateRangeTimeSelectWidget;
use Drupal\intercept_core\Utility\Dates;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'daterange_availability' widget.
 *
 * @FieldWidget(
 *   id = "intercept_room_date_availability",
 *   label = @Translation("Datetime Select with Room Availability"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class RoomDateAvailabilityWidget extends DateRangeTimeSelectWidget implements ContainerFactoryPluginInterface {

  /**
   * The Reservation manager.
   *
   * @var \Drupal\intercept_core\ReservationManager
   */
  protected $reservationManager;

  /**
   * The Intercept dates utility.
   *
   * @var \Drupal\intercept_core\Utility\Dates
   */
  protected $dateUtility;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The room reservation entity.
   *
   * @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface
   */
  protected $entity;

  /**
   * The room attached to this reservation.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $room;

  /**
   * Constructs a RoomDateAvailabilityWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_storage
   *   The date format storage manager.
   * @param \Drupal\intercept_core\ReservationManager $reservation_manager
   *   The reservation manager.
   * @param \Drupal\intercept_core\Utility\Dates $date_utility
   *   The Intercept dates utility.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $date_storage, ReservationManager $reservation_manager, Dates $date_utility, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $date_storage);

    $this->reservationManager = $reservation_manager;
    $this->dateUtility = $date_utility;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')->getStorage('date_format'),
      $container->get('intercept_core.reservation.manager'),
      $container->get('intercept_core.utility.dates'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['message'] = [
      '#type' => 'item',
      '#wrapper_attributes' => [
        'class' => ['edit-message'],
        'id' => 'edit-message',
      ],
    ];
    $ajax_callback = [
      'callback' => [$this, 'availabilityCallback'],
      'event' => 'change',
      'wrapper' => 'edit-message',
      'progress' => [
        'type' => 'throbber',
        'message' => $this->t('Verifying reservation dates...'),
      ],
    ];
    $element['value']['#ajax'] = $ajax_callback;
    $element['end_value']['#ajax'] = $ajax_callback;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getName() === 'field_dates') && $field_definition->getTargetEntityTypeId() == 'room_reservation';
  }

  /**
   * Sets the base reservation room.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  protected function setReservationRoom(FormStateInterface $form_state) {
    $room = $form_state->getValue('field_room');
    if ($room && isset($room[0]['target_id'])) {
      $this->room = $this->entityTypeManager->getStorage('node')->load($room[0]['target_id']);
    }
  }

  /**
   * Checks to see if the given resource is available at the current time.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The start datetime.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The end datetime.
   *
   * @return array
   *   A validation message render array.
   */
  public function checkAvailability(DrupalDateTime $start_date, DrupalDateTime $end_date) {
    if (!$this->room) {
      return [];
    }

    $message = [];
    $reservation_params = [];

    $reservation = $this->entity;
    $reservation_params['rooms'] = [$this->room->id()];
    $reservation_params['start'] = $start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $reservation_params['end'] = $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    if ($reservation->id()) {
      $reservation_params['exclude'] = [$reservation->id()];
    }
    if ($availability = $this->reservationManager->availability($reservation_params)) {
      foreach ($availability as $room_availability) {
        if ($room_availability['has_reservation_conflict']) {
          $message = [
            '#type' => 'intercept_field_error_message',
            '#message' => 'WARNING: It looks like the time that you\'re picking already has a room reservation. Are you sure you want to proceed?',
          ];
        }
        if ($room_availability['has_open_hours_conflict']) {
          $message = [
            '#type' => 'intercept_field_error_message',
            '#message' => 'WARNING: You are reserving a closed space. Are you sure you want to proceed?',
          ];
        }
      }
    }

    return [
      '#type' => 'html_tag',
      '#attributes' => [
        'id' => 'edit-message',
      ],
      '#tag' => 'div',
      'child' => $message,
    ];
  }

  /**
   * Runs availability validation and triggers an update event in the browser.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   An array of Ajax commands.
   */
  public function availabilityCallback(array &$form, FormStateInterface $form_state) {
    $this->setReservationRoom($form_state);
    $this->entity = $form_state->getFormObject()->getEntity();
    $response = new AjaxResponse();

    $field_name = $this->fieldDefinition->getName();
    $value = $form_state->getValue($field_name)[0];
    $start_date = $this->getDateObject($value['value']);
    $end_date = $this->getDateObject($value['end_value']);

    if (!$this->isValidDate($start_date) || !$this->isValidDate($end_date) || !$room = $this->room) {
      return $response;
    }

    $validationMessage = $this->checkAvailability($start_date, $end_date);
    if (!empty($validationMessage)) {
      $command = new ReplaceCommand('[class^="edit-message"]', $validationMessage);
      $response->addCommand($command);
    }

    // Trigger the intercept:updateRoomReservation event.
    if (!$this->entity->isNew()) {
      $reservation_params['id'] = $this->entity->uuid();
      $reservation_params['start'] = $start_date->format(\DateTime::RFC3339);
      $reservation_params['end'] = $end_date->format(\DateTime::RFC3339);
      $reservation_params['room'] = $room->uuid();
      $command = new InvokeCommand('html', 'trigger', [
        'intercept:updateRoomReservation', $reservation_params,
      ]);
      $response->addCommand($command);
    }

    return $response;
  }

  /**
   * Whether the date value is valid.
   */
  public function isValidDate($datetime) {
    return $datetime instanceof DrupalDateTime;
  }

  /**
   * Returns a DrupalDateTime object from a datetime array.
   *
   * @param array $form_date
   *   The form date, keyed with 'date' and 'time'.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The converted DrupalDateTime object.
   */
  protected function getDateObject(array $form_date) {
    if (($start_date = $form_date['date']) && ($start_time = $form_date['time'])) {
      return $this->dateUtility->convertDate($start_date . 'T' . $start_time);
    }

    return NULL;
  }

}
