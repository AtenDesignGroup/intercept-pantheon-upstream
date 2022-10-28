<?php

namespace Drupal\intercept_room_reservation\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_core\Utility\Dates;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\intercept_core\ReservationManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\intercept_room_reservation\ValidationMessageBuilder;
use Drupal\intercept_core\Plugin\Field\FieldWidget\DateRangeTimeSelectWidget;

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
   * The intercept_room_reservation.validation_message_builder service.
   *
   * @var \Drupal\intercept_room_reservation\ValidationMessageBuilder
   */
  protected $validationMessageBuilder;

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
   * @param \Drupal\intercept_room_reservation\ValidationMessageBuilder $validationMessageBuilder
   *   The intercept_room_reservation.validation_message_builder service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $date_storage, ReservationManager $reservation_manager, Dates $date_utility, EntityTypeManagerInterface $entity_type_manager, ValidationMessageBuilder $validationMessageBuilder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $date_storage);

    $this->reservationManager = $reservation_manager;
    $this->dateUtility = $date_utility;
    $this->entityTypeManager = $entity_type_manager;
    $this->validationMessageBuilder = $validationMessageBuilder;
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
      $container->get('entity_type.manager'),
      $container->get('intercept_room_reservation.validation_message_builder')
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
      'event' => 'delayed_keyup',
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

  /**
   * Returns the ValidationMessageBuilder service's availabilityCallback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public function availabilityCallback(array &$form, FormStateInterface $form_state) {
    return $this->validationMessageBuilder->availabilityCallback($form, $form_state);
  }

}
