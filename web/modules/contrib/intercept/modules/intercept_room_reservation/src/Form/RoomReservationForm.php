<?php

namespace Drupal\intercept_room_reservation\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\SetDialogTitleCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\intercept_core\Utility\Dates;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Room reservation edit forms.
 *
 * @ingroup intercept_room_reservation
 */
class RoomReservationForm extends ContentEntityForm {

  use AjaxFormHelperTrait;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The Intercept dates utility.
   *
   * @var \Drupal\intercept_core\Utility\Dates
   */
  protected $dateUtility;

  /**
   * The saved status of the entity.
   *
   * @var int
   */
  protected $savedStatus;

  /**
   * Constructs a RoomReservationForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\intercept_core\Utility\Dates $date_utility
   *   The Intercept dates utility.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, EntityDisplayRepositoryInterface $entity_display_repository, Dates $date_utility) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->entityDisplayRepository = $entity_display_repository;
    $this->dateUtility = $date_utility;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('entity_display.repository'),
      $container->get('intercept_core.utility.dates')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    parent::init($form_state);
    if ($this->getRequestWrapperFormat() == 'drupal_dialog.off_canvas') {
      $config = $this->config('intercept_room_reservation.settings');
      if ($form_mode = $config->get('off_canvas_form_mode')) {
        $form_display = $this->entityDisplayRepository->getFormDisplay('room_reservation', 'room_reservation', $form_mode);
        $this->setFormDisplay($form_display, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $entity */
    $entity = $this->entity;
    if ($entity->isNew() && ($room = $this->getRequest()->query->get('room'))) {
      $entity->set('field_room', $room);
    }
    $form = parent::buildForm($form, $form_state);

    $form['#attached'] = [
      'library' => [
        'intercept_room_reservation/room-reservations',
        'intercept_core/delay_keyup',
      ],
    ];

    $form['field_room']['widget'][0]['target_id']['#ajax'] = [
      'callback' => '::availabilityCallback',
      'event' => 'autocompleteclose',
      'wrapper' => 'edit-field-dates-0-message',
      'progress' => [
        'type' => 'throbber',
        'message' => t('Verifying reservation dates...'),
      ],
    ];

    $form['field_dates']['widget'][0]['message'] = [
      '#type' => 'item',
    ];

    // Add an ajax callback validating the room reservation availability.
    $form['field_dates']['widget'][0]['value']['#ajax'] = [
      'callback' => '::availabilityCallback',
      // 'disable-refocus' => TRUE,
      'event' => 'change',
      'wrapper' => 'edit-field-dates-0-message',
      'progress' => [
        'type' => 'throbber',
        'message' => $this->t('Verifying reservation dates...'),
      ],
    ];
    $form['field_dates']['widget'][0]['end_value']['#ajax'] = [
      'callback' => '::availabilityCallback',
      // 'disable-refocus' => TRUE,
      'event' => 'change',
      'wrapper' => 'edit-field-dates-0-message',
      'progress' => [
        'type' => 'throbber',
        'message' => $this->t('Verifying reservation dates...'),
      ],
    ];
    $form['field_dates']['widget'][0]['value']['#attributes']['class'] = [
      'delayed-keyup',
    ];
    $form['field_dates']['widget'][0]['end_value']['#attributes']['class'] = [
      'delayed-keyup',
    ];

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => TRUE,
        '#weight' => 10,
      ];
    }

    $form['messages'] = [
      '#markup' => '<div id="room-reservation-form__messages"></div>',
      '#weight' => -100,
    ];

    if ($this->isAjax()) {
      $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
      // @todo Remove when https://www.drupal.org/node/2897377 lands.
      $form['#id'] = Html::getId($form_state->getBuildInfo()['form_id']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $this->status = parent::save($form, $form_state);

    switch ($this->status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Room reservation.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Room reservation.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.room_reservation.canonical', ['room_reservation' => $entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($this->status === SAVED_NEW) {
      $response->addCommand(new SetDialogTitleCommand('#drupal-off-canvas', 'Edit Reservation'));
    }

    $messages = ['#type' => 'status_messages'];
    $response->addCommand(new HtmlCommand('#room-reservation-form__messages', $messages));

    // Trigger the Save success event.
    $command = new InvokeCommand('html', 'trigger', [
      'intercept:saveRoomReservationSuccess',
    ]);
    $response->addCommand($command);

    return $response;
  }

  /**
   * Gets the base reservation parameters.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   An array of room, and date values.
   */
  protected function getReservationParams(FormStateInterface $form_state) {
    $reservation_params = [];

    $field_room = $form_state->getValue('field_room');
    if (isset($field_room[0]['target_id'])) {
      $reservation_params['room'] = $field_room[0]['target_id'];
    }

    $field_dates = $form_state->getValue('field_dates');
    if (($start_date = $field_dates[0]['value']['date']) && ($start_time = $field_dates[0]['value']['time'])) {
      $reservation_params['start'] = $this->dateUtility->convertDate($start_date . 'T' . $start_time);
    }

    if (($end_date = $field_dates[0]['end_value']['date']) && ($end_time = $field_dates[0]['end_value']['time'])) {
      $reservation_params['end'] = $this->dateUtility->convertDate($end_date . 'T' . $end_time);
    }

    return $reservation_params;
  }

  /**
   * Checks to see if the given resource is available at the current time.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   A validation message render array.
   */
  public function checkAvailability(array &$form, FormStateInterface $form_state) {
    $reservation_params = $this->getReservationParams($form_state);

    if (!$reservation_params['room']) {
      return [];
    }

    $message = [];

    if (isset($reservation_params['start']) && isset($reservation_params['end'])) {
      $reservation = $form_state->getFormObject()->getEntity();
      $reservation_params['rooms'] = [$reservation_params['room']];
      $reservation_params['start'] = $reservation_params['start']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
      $reservation_params['end'] = $reservation_params['end']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

      if ($reservation->id()) {
        $reservation_params['exclude'] = [$reservation->id()];
      }
      $reservation_manager = \Drupal::service('intercept_core.reservation.manager');
      if ($availability = $reservation_manager->availability($reservation_params)) {
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
    }

    return [
      '#type' => 'html_tag',
      '#attributes' => [
        'id' => 'edit-field-dates-0-message',
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
    $response = new AjaxResponse();

    $validationMessage = $this->checkAvailability($form, $form_state);
    if (!empty($validationMessage)) {
      $command = new ReplaceCommand('[id^="edit-field-dates-0-message"]', $validationMessage);
      $response->addCommand($command);
    }

    // Trigger the intercept:updateRoomReservation event.
    $reservation_params = $this->getReservationParams($form_state);
    if (isset($reservation_params['start']) && isset($reservation_params['end'])) {
      $reservation = $form_state->getFormObject()->getEntity();
      $reservation_params['id'] = $reservation->uuid();
      $reservation_params['start'] = $reservation_params['start']->format(\DateTime::RFC3339);
      $reservation_params['end'] = $reservation_params['end']->format(\DateTime::RFC3339);

      if (!empty($reservation->field_room) && $room = $form_state->getValue('field_room')[0]) {
        $reservation->set('field_room', $room['target_id']);
        $reservation_params['room'] = $reservation->field_room->entity->uuid();
      }
      $command = new InvokeCommand('html', 'trigger', [
        'intercept:updateRoomReservation', $reservation_params,
      ]);
      $response->addCommand($command);
    }

    return $response;
  }

}
