<?php

namespace Drupal\intercept_room_reservation\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\intercept_core\ReservationManagerInterface;
use Drupal\intercept_core\Utility\Dates;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides alter functions for the event node form.
 */
class EventNodeFormHelper implements ContainerInjectionInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The reservation manager.
   *
   * @var \Drupal\intercept_core\ReservationManagerInterface
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
   * EventNodeFormHelper constructor.
   *
   * @param \Drupal\intercept_core\ReservationManagerInterface $reservation_manager
   *   The reservation manager.
   * @param \Drupal\intercept_core\Utility\Dates $date_utility
   *   The Intercept dates utility.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ReservationManagerInterface $reservation_manager, Dates $date_utility, EntityTypeManagerInterface $entity_type_manager) {
    $this->reservationManager = $reservation_manager;
    $this->dateUtility = $date_utility;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('intercept_core.reservation.manager'),
      $container->get('intercept_core.utility.dates'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Adds reservation functionality to the node edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function nodeFormAlter(array &$form, FormStateInterface $form_state) {
    // Since all of this functionality centers around the reservation element
    // we can avoid this alter if it's not displayed.
    $node = $form_state->getFormObject()->getEntity();
    $reservation = $this->reservationManager->getEventReservation($node);

    $form['reservation'] = [
      '#title' => $this->t('Reservation'),
      '#type' => 'fieldset',
      '#prefix' => '<div id="event-room-reservation-ajax-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    $form['field_room']['widget']['#ajax'] = $this->updateStatusAjax();

    $form['reservation']['create'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create a room reservation'),
      '#default_value' => 0,
      '#states' => [
        'enabled' => [':input[name="field_room"]' => ['!value' => '_none']],
      ],
      '#attributes' => ['class' => ['reservation-prepopulate-dates']],
      '#access' => empty($reservation),
      '#ajax' => $this->updateStatusAjax(),
    ];

    $start_date_object = NULL;
    $end_date_object = NULL;
    if (!empty($reservation)) {
      $start_date_object = $this->dateUtility
        ->convertTimezone($reservation->field_dates->start_date, 'default');
      $end_date_object = $this->dateUtility
        ->convertTimezone($reservation->field_dates->end_date, 'default');
    }

    $params = [
      'start' => $start_date_object ? $start_date_object->format($this->reservationManager::FORMAT) : NULL,
      'end' => $end_date_object ? $end_date_object->format($this->reservationManager::FORMAT) : NULL,
    ];
    if ($room = $node->field_room->entity) {
      $params['rooms'] = [$room->id()];
    }

    $form['reservation']['#attached'] = [
      'library' => [
        'intercept_core/reservation_form_helper',
        'intercept_core/delay_keyup',
      ],
    ];

    $form['reservation']['dates'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="reservation[create]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['reservation']['dates']['start'] = [
      '#title' => $this->t('Reservation start time'),
      '#type' => 'datetime',
      '#default_value' => $start_date_object,
      '#ajax' => [
        'callback' => [$this, 'updateFormStatusField'],
        'event' => 'blur delayed_keyup',
        'disable-refocus' => TRUE,
        'wrapper' => 'event-room-reservation-status-ajax-wrapper',
      ],
      '#attributes' => [
        'class'      => [
          'delayed-keyup',
        ],
      ],
    ];

    $form['reservation']['dates']['end'] = [
      '#title' => $this->t('Reservation end time'),
      '#type' => 'datetime',
      '#default_value' => $end_date_object,
      '#ajax' => [
        'callback' => [$this, 'updateFormStatusField'],
        'event' => 'blur delayed_keyup',
        'disable-refocus' => TRUE,
        'wrapper' => 'event-room-reservation-status-ajax-wrapper',
      ],
      '#attributes' => [
        'class'      => [
          'delayed-keyup',
        ],
      ],
    ];

    $form['reservation']['dates']['status'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#prefix' => '<div id="event-room-reservation-status-ajax-wrapper">',
      '#suffix' => '</div>',
    ];

    if ($form_state->getValue('field_room') && $form_state->getValue(['reservation', 'dates'])) {
      // Check if date or room was changed.
      $this->updateFormStatusField($form, $form_state);
    }
    if ($form_state->getValue('field_room') && $form_state->getValue(['reservation', 'create'])) {
      // Check that the create reservation checkbox has been checked.
      $this->updateFormStatusField($form, $form_state);
    }

    if (!empty($reservation)) {
      /* @var $view_builder \Drupal\Core\Entity\EntityViewBuilderInterface */
      $view_builder = $this->entityTypeManager
        ->getViewBuilder('room_reservation');
      $form['reservation']['view'] = $view_builder->view($reservation, 'event');
      $form_state->set('reservation', $reservation);
    }

    $form['#validate'][] = [$this, 'nodeFormValidate'];
    $form['actions']['submit']['#submit'][] = [$this, 'nodeFormAlterSubmit'];
  }

  /**
   * Custom form validate handler to process a reservation for an event node.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see self::nodeFormAlter()
   *
   * @internal
   */
  public function nodeFormValidate(array &$form, FormStateInterface $form_state) {
    // Validate the requested room reservation.
    $room = $form_state->getValue('field_room');

    // They clicked create reservation before the event date was set.
    $dates = $form_state->getValue('field_date_time');
    if (empty($dates) || empty($dates[0]['value']) || empty($dates[0]['end_value'])) {
      return;
    }
    if (empty($room[0]['target_id'])) {
      return;
    }
    $start_date = $dates[0]['value'];
    $end_date = $dates[0]['end_value'];

    if ($start_date > $end_date) {
      $message = $this->t('The selected reservation times are invalid.');
      $form_state->setError($form['reservation'], $message);
    }
  }

  /**
   * Provides the form ajax callback information.
   *
   * @return array
   *   The form ajax callback array.
   */
  private function updateStatusAjax() {
    return [
      'callback' => [$this, 'ajaxCallback'],
      'wrapper' => 'event-room-reservation-status-ajax-wrapper',
    ];
  }

  /**
   * Custom ajax form submit handler to update reservation status.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @internal
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['reservation']['dates']['status'];
  }

  /**
   * Update the inline element from $form_state changes.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function updateFormStatusField(array &$form, FormStateInterface $form_state) {
    $event = $form_state->getFormObject()->getEntity();
    $room = $form_state->getValue('field_room');
    $status_element = &$form['reservation']['dates']['status'];
    // They clicked create reservation before the reservation date was set.
    $dates = $form_state->getValue(['reservation', 'dates']);
    if (empty($dates) || empty($dates['start']) || empty($dates['end'])) {
      $dates = $form_state->getValue('field_date_time');
      if (empty($dates) || empty($dates[0]['value']) || empty($dates[0]['end_value'])) {
        return $status_element;
      }
      $start_date = $dates[0]['value'];
      $end_date = $dates[0]['end_value'];
    }
    else {
      if (is_array($dates['start'])) {
        return $status_element;
      }
      $start_date = $dates['start'];
      $end_date = $dates['end'];
    }
    $params = [
      'debug' => TRUE,
      'rooms' => [$room[0]['target_id']],
      'start' => $this->dateUtility->convertTimezone($start_date)->format($this->reservationManager::FORMAT),
      'end' => $this->dateUtility->convertTimezone($end_date)->format($this->reservationManager::FORMAT),
    ];
    if (!$event->isNew()) {
      $params['event'] = $event->id();
    }
    $availability = $this->reservationManager->availability($params);
    $status = reset($availability);
    if ($status['has_reservation_conflict']) {
      $status_element['#value'] = $this->t('This room is not available due to a conflict.');
      $status_element['#attributes']['class'][] = 'error-text-color';
    }
    elseif ($status['has_open_hours_conflict']) {
      $status_element['#value'] = $this->t('Reservation times conflict with location open hours.');
      $status_element['#attributes']['class'][] = 'error-text-color';
    }
    if (!$status['has_open_hours_conflict'] && !$status['has_reservation_conflict']) {
      $status_element['#value'] = $this->t('This room is available.');
      $status_element['#attributes']['class'][] = 'status-text-color';
    }
    if ($start_date > $end_date) {
      $status_element['#value'] = $this->t('The selected reservation times are invalid.');
      $status_element['#attributes']['class'][] = 'error-text-color';
    }

    return $status_element;
  }

  /**
   * Custom form submit handler to process a reservation for an event node.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see self::nodeFormAlter()
   *
   * @internal
   */
  public function nodeFormAlterSubmit(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['reservation', 'create'])) {
      $this->createEventReservationSubmit($form, $form_state);
    }
    if ($form_state->get('reservation')) {
      $this->updateEventReservationSubmit($form, $form_state);
    }
  }

  /**
   * Internal helper function to create a reservation for the node add form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @internal
   */
  public function createEventReservationSubmit(array &$form, FormStateInterface $form_state) {
    $node_event = $form_state->getFormObject()->getEntity();
    $dates = $form_state->getValue(['reservation', 'dates']);
    return $this->reservationManager->createEventReservation($node_event, [
      'field_dates' => [
        'value' => $this->dateUtility->convertTimezone($dates['start'])->format($this->reservationManager::FORMAT),
        'end_value' => $this->dateUtility->convertTimezone($dates['end'])->format($this->reservationManager::FORMAT),
      ],
    ]);
  }

  /**
   * Helper function to update an existing reservation for the node edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @internal
   */
  public function updateEventReservationSubmit(array &$form, FormStateInterface $form_state) {
    $node_event = $form_state->getFormObject()->getEntity();
    $reservation = $form_state->get('reservation');
    $dates = $form_state->getValue(['reservation', 'dates']);
    $reservation->set('field_dates', [
      'value' => $this->dateUtility->convertTimezone($dates['start'])->format($this->reservationManager::FORMAT),
      'end_value' => $this->dateUtility->convertTimezone($dates['end'])->format($this->reservationManager::FORMAT),
    ]);
    return $this->reservationManager->updateEventReservation($reservation, $node_event);
  }

}
