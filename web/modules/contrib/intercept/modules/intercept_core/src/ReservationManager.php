<?php

namespace Drupal\intercept_core;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Drupal\intercept_core\Utility\Dates;
use Drupal\intercept_room_reservation\Entity\RoomReservation;
use Drupal\intercept_room_reservation\Entity\RoomReservationInterface;

/**
 * Class ReservationManager.
 *
 * @TODO: Move partially over to an EntityReservationManager/RoomReservationManager.
 */
class ReservationManager implements ReservationManagerInterface {

  use DependencySerializationTrait;

  use StringTranslationTrait;

  const FORMAT = 'Y-m-d\TH:i:s';

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\intercept_core\Utility\Dates
   */
  protected $dateUtility;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ReservationManager object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, Dates $date_utility, Token $token, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
    $this->dateUtility = $date_utility;
    $this->token = $token;
    $this->currentUser = $current_user;
  }

  /**
   * Expose the date utility for functions that use this service.
   *
   * @return \Drupal\intercept_core\Utility\Dates
   */
  public function dateUtility() {
    return $this->dateUtility;
  }

  /**
   * Get a reservation entity for an event node.
   *
   * @param \Drupal\node\NodeInterface $event
   *
   * @return bool|RoomReservationInterface
   */
  public function getEventReservation(NodeInterface $event) {
    if ($event->isNew()) {
      return FALSE;
    }
    $reservations = $this->reservations('room', function ($query) use ($event) {
      $query->condition('field_event', $event->id(), '=');
      $query->condition('field_status', ['canceled', 'denied'], 'NOT IN');
    });
    if (!empty($reservations)) {
      $reservation = reset($reservations);
      return $reservation;
    }
    return FALSE;
  }

  /**
   * Create a new reservation entity based on an event node.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   * @param array $params
   *   Additional field info to pass to the create method.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createEventReservation(NodeInterface $event, array $params) {
    $values = [
      'field_event' => $event->id(),
      'field_room' => $event->field_room->entity->id(),
      'field_user' => $this->currentUser->id(),
    ] + $params;
    $room_reservation = $this->entityTypeManager->getStorage('room_reservation')->create($values);
    $room_reservation->save();
  }

  /**
   * Update a reservation entity when a node is updated.
   *
   * @param \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $reservation
   *   The reservation entity to update.
   * @param \Drupal\node\NodeInterface $event
   *   The event node that also has been updated.
   * @param array $params
   *   Additional info to pass to the reservation entity. (not used)
   */
  public function updateEventReservation(RoomReservationInterface $reservation, NodeInterface $event, array $params = []) {
    if (!$event->field_room->equals($reservation->field_room)) {
      $reservation->field_room = $event->field_room;
      $reservation->save();
    }
  }

  /**
   * Adds reservation functionality to the node edit form.
   */
  public function nodeFormAlter(&$form, FormStateInterface $form_state) {
    // Since all of this functionality centers around the reservation element
    // we can avoid this alter if it's not displayed.
    $form_display = $form_state->getFormObject()->getFormDisplay($form_state);
    $node = $form_state->getFormObject()->getEntity();
    $reservation = $this->getEventReservation($node);
    $form['field_date_time']['widget'][0]['value']['#ajax'] = $this->updateStatusAjax() + [
      'event' => 'change',
      // Keep the refocus from re-activating the date select widget.
      'disable-refocus' => TRUE,
    ];

    $form['field_date_time']['widget'][0]['end_value']['#ajax'] = $this->updateStatusAjax() + [
      'event' => 'change',
      // Keep the refocus from re-activating the date select widget.
      'disable-refocus' => TRUE,
    ];

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

    $start_date_object = $node->field_date_time->start_date ?: new \DateTime();
    $end_date_object = $node->field_date_time->end_date ?: new \DateTime();
    if (!empty($reservation)) {
      $start_date_object = $this->dateUtility
        ->convertTimezone($reservation->field_dates->start_date, 'default');
      $end_date_object = $this->dateUtility
        ->convertTimezone($reservation->field_dates->end_date, 'default');
    }

    $params = [
      'start' => $start_date_object->format(self::FORMAT),
      'end' => $end_date_object->format(self::FORMAT),
    ];
    if ($room = $node->field_room->entity) {
      $params['rooms'] = [$room->id()];
    }

    $form['reservation']['#attached'] = [
      'library' => ['intercept_core/reservation_form_helper'],
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
      '#title' => t('Reservation start time'),
      '#type' => 'datetime',
      '#default_value' => $start_date_object,
    ];

    $form['reservation']['dates']['end'] = [
      '#title' => t('Reservation end time'),
      '#type' => 'datetime',
      '#default_value' => $end_date_object,
    ];

    $form['reservation']['dates']['status'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#prefix' => '<div id="event-room-reservation-status-ajax-wrapper">',
      '#suffix' => '</div>',
    ];

    if ($form_state->getValue('field_room') && !empty($reservation) && $form_state->getValue(['reservation', 'dates'])) {
      // If there is already a reservation just check if date or room was changed.
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
    $form['actions']['submit']['#submit'][] = [static::class, 'nodeFormAlterSubmit'];
    $form_state->set('reservation_manager', $this);

  }

  /**
   * Custom form validate handler to process a reservation for an event node.
   *
   * @see self::nodeFormAlter()
   *
   * @internal
   */
  public function nodeFormValidate(&$form, FormStateInterface $form_state) {
    // Validate the requested room reservation.
    $event = $form_state->getFormObject()->getEntity();
    $room = $form_state->getValue('field_room');
    $status_element = &$form['reservation']['dates']['status'];
    $reservation = $form_state->getValue('reservation');
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
    $params = [
      'debug' => TRUE,
      'rooms' => [$room[0]['target_id']],
      'start' => $start_date->format(self::FORMAT),
      'end' => $end_date->format(self::FORMAT),
    ];
    if (!$event->isNew()) {
      $params['event'] = $event->id();
    }
    // @TODO: Re-enable this when issue in CRL-149 is resolved.
    // else if ($status['has_open_hours_conflict']) {
    //  $message = t('Reservation times conflict with location open hours.');
    //  $form_state->setError($form['reservation'], $message);
    //}
    if ($start_date > $end_date) {
      $message = t('The selected reservation times are invalid.');
      $form_state->setError($form['reservation'], $message);
    }
  }

  private function updateStatusAjax() {
    return [
      'callback' => [$this, 'ajaxCallback'],
      'wrapper' => 'event-room-reservation-status-ajax-wrapper',
    ];
  }

  /**
   * Update the inline element from $form_state changes.
   */
  private function updateFormStatusField(&$form, FormStateInterface $form_state) {
    $event = $form_state->getFormObject()->getEntity();
    $room = $form_state->getValue('field_room');
    $status_element = &$form['reservation']['dates']['status'];
    $reservation = $form_state->getValue('reservation');
    // They clicked create reservation before the event date was set.
    $dates = $form_state->getValue('field_date_time');
    if (empty($dates) || empty($dates[0]['value']) || empty($dates[0]['end_value'])) {
      return;
    }
    $start_date = $dates[0]['value'];
    $end_date = $dates[0]['end_value'];
    $params = [
      'debug' => TRUE,
      'rooms' => [$room[0]['target_id']],
      'start' => $start_date->format(self::FORMAT),
      'end' => $end_date->format(self::FORMAT),
    ];
    if (!$event->isNew()) {
      $params['event'] = $event->id();
    }
    $availability = $this->availability($params);
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
  }

  /**
   * Internal helper function to create a reservation for the node add form.
   *
   * @internal
   */
  public function createEventReservationSubmit(&$form, FormStateInterface $form_state) {
    $node_event = $form_state->getFormObject()->getEntity();
    $dates = $form_state->getValue(['reservation', 'dates']);
    return $this->createEventReservation($node_event, [
      'field_dates' => [
        'value' => $this->dateUtility->convertTimezone($dates['start'])->format(self::FORMAT),
        'end_value' => $this->dateUtility->convertTimezone($dates['end'])->format(self::FORMAT),
      ],
    ]);
  }

  /**
   * Internal helper function to update an existing reservation for the node edit form.
   *
   * @internal
   */
  public function updateEventReservationSubmit(&$form, FormStateInterface $form_state) {
    $node_event = $form_state->getFormObject()->getEntity();
    $reservation = $form_state->get('reservation');
    $dates = $form_state->getValue(['reservation', 'dates']);
    $reservation->set('field_dates', [
      'value' => $this->dateUtility->convertTimezone($dates['start'])->format(self::FORMAT),
      'end_value' => $this->dateUtility->convertTimezone($dates['end'])->format(self::FORMAT),
    ]);
    return $this->updateEventReservation($reservation, $node_event);
  }

  /**
   * Custom form submit handler to process a reservation for an event node.
   *
   * @see self::nodeFormAlter()
   *
   * @internal
   */
  public static function nodeFormAlterSubmit(&$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['reservation', 'create'])) {
      $form_state->get('reservation_manager')->createEventReservationSubmit($form, $form_state);
    }
    if ($form_state->get('reservation')) {
      $form_state->get('reservation_manager')->updateEventReservationSubmit($form, $form_state);
    }
  }

  /**
   * Custom ajax form submit handler to update reservation status.
   *
   * @internal
   */
  public function ajaxCallback(&$form, $form_state) {
    return $form['reservation']['dates']['status'];
  }

  /**
   * A list of emails to use for reservations.
   *
   * TODO: Make this a hook so that it can be customized and altered.
   */
  public static function emails() {
    return [
      'reservation_requested' => t('Reservation requested'),
      'reservation_canceled' => t('Reservation canceled'),
      'reservation_approved_staff' => t('Reservation approved (by staff)'),
      'reservation_approved_auto' => t('Reservation approved (auto)'),
      'reservation_canceled_staff' => t('Reservation canceled (by staff)'),
      'reservation_denied_staff' => t('Reservation denied (by staff)'),
    ];
  }

  public function userExceededReservationLimit(AccountInterface $user) {
    if ($user->hasPermission('bypass room reservation limit')) {
      return FALSE;
    }
    $config = $this->configFactory->get('intercept_room_reservation.settings');
    return $this->userReservationCount($user) >= $config->get('reservation_limit');
  }

  public function userReservationCount(AccountInterface $user) {
    $reservations = $this->reservations('room', function ($query) use ($user) {
      $query->condition('field_user', $user->id(), '=');
      $date = new DrupalDateTime('now', $this->dateUtility->getUtcTimezone());
      $query->condition('field_dates.end_value', $date->format('Y-m-d\TH:i:s'), '>');
      $query->condition('field_status', ['requested', 'approved'], 'IN');
    });
    return count($reservations);
  }

  /**
   * Determines the duration between a start and end date.
   *
   * @param string $start
   *   The start time.
   * @param string $end
   *   The end time.
   */
  public function duration(string $start, string $end) {
    return $this->dateUtility->duration(
      $this->dateUtility->getDrupalDate($start),
      $this->dateUtility->getDrupalDate($end)
    );
  }

  /**
   * Check the availability of a room at a given date range.
   *
   * Determines the availability of a specified time in a specified room.
   *
   * @param array $params
   *   The parameters to check availability for.
   */
  public function availability(array $params = []) {
    // Show debug information in return.
    $debug = !empty($params['debug']);
    // Reservations keyed by room uuid.
    if (empty($params['duration'])) {
      $params['duration'] = $this->duration($params['start'], $params['end']);
    }
    $data = $this->reservationsByNode('room', function ($query) use ($params) {
      $start_date = $this->dateUtility->getDate($params['start']);
      $end_date = $this->dateUtility->getDate($params['end']);
      // Add period to end to send to query.
      $period = 'PT' . (int) $params['duration'] . 'M';
      $end_date->add(new \DateInterval($period));
      $params['end'] = $end_date->format(self::FORMAT);
      if (!empty($params['rooms'])) {
        $query->condition('field_room', $params['rooms'], 'IN');
      }
      // If we're editing an event reservation, we need to make sure to not
      // count the existing reservation towards unavailability.
      if (!empty($params['event'])) {
        $query->condition('field_event', $params['event'], '!=');
      }
      if (!empty($params['exclude'])) {
        $query->condition('id', $params['exclude'], '!=');
      }
      $query->condition('field_status', ['canceled', 'denied'], 'NOT IN');
      $range = [$start_date->format(self::FORMAT), $end_date->format(self::FORMAT)];
      $query->condition($query->orConditionGroup()
        // Date start value is in between start / end params.
        ->condition('field_dates.value', $range, 'BETWEEN')
        // OR Date end value is in between start / end params.
        ->condition('field_dates.end_value', $range, 'BETWEEN')
        // OR Date start and date end values span larger than the start / end params.
        ->condition($query->andConditionGroup()
          ->condition('field_dates.value', $range[0], '<=')
          ->condition('field_dates.end_value', $range[1], '>=')
        )
      );
    });

    $nodes = $this->nodes('room', isset($params['rooms']) ? $params['rooms'] : []);
    $return = [];

    $timezone_info = [
      'default_timezone' => $this->dateUtility->getDefaultTimezone()->getName(),
      'storage_timezone' => $this->dateUtility->getStorageTimezone()->getName(),
    ];

    $param_info = [];
    foreach (['start', 'end'] as $param) {
      $date = $this->dateUtility->getDate($params[$param]);
      $param_info[$param]['storage_timezone'] = $date->format(self::FORMAT);
      $param_info[$param]['default_timezone'] = $this->dateUtility->convertTimezone($date, 'default')->format(self::FORMAT);
    }

    foreach ($nodes as $nid => $node) {
      $uuid = $node->uuid();
      if ($debug) {
        $return[$uuid]['debug'] = [];
        $debug_data = &$return[$uuid]['debug'];
      }
      $reservations = !empty($data[$node->uuid()]) ? $data[$node->uuid()] : [];
      $return[$uuid]['has_reservation_conflict'] = $this->hasReservationConflict($reservations, $params);
      $return[$uuid]['has_open_hours_conflict'] = $this->hasOpeningHoursConflict($reservations, $params, $node);
      $return[$uuid]['has_max_duration_conflict'] = $this->hasMaxDurationConflict($params, $node);
      $is_closed = $this->isClosed($params, $node);
      $return[$uuid]['is_closed'] = $is_closed;
      $return[$uuid]['closed_message'] = $this->closedMessage($params, $node);
      $return[$uuid]['has_location'] = !empty($this->getLocation($node));
      if ($debug) {
        $debug_data['schedule'] = $this->getSchedule($reservations, $params);
        $debug_data['schedule_by_open_hours'] = $this->getScheduleByOpenHours($reservations, $params, $node);
        $debug_data['hours'] = FALSE;
        if (!$this->isClosed($params, $node)) {
          $hours = $this->getHours($params, $node);
          $hours_start = $this->timeToDate($hours['starthours'], $this->dateUtility->getDate($params['start']));
          $hours_end = $this->timeToDate($hours['endhours'], $this->dateUtility->getDate($params['end']));
          $debug_data['hours'] = [
            'start' => ['raw' => $hours['starthours']],
            'end' => ['raw' => $hours['endhours']],
          ];

          $debug_data['hours']['start']['default_timezone'] = $hours_start->format(self::FORMAT);
          $debug_data['hours']['start']['storage_timezone'] = $this->dateUtility->convertTimezone($hours_start, 'storage')->format(self::FORMAT);
          $debug_data['hours']['end']['default_timezone'] = $hours_end->format(self::FORMAT);
          $debug_data['hours']['end']['storage_timezone'] = $this->dateUtility->convertTimezone($hours_end, 'storage')->format(self::FORMAT);
        }
      }
      $return[$uuid]['dates'] = $this->getDates($reservations);
      if ($debug) {
        $debug_data['room_nid'] = $node->id();
        $debug_data['location_nid'] = !empty($this->getLocation($node)) ? $this->getLocation($node)->id() : FALSE;
        $debug_data['param_info'] = $param_info;
        $debug_data['timezone_info'] = $timezone_info;
      }
    }
    return $return;
  }

  public function getDates($reservations) {
    $return = [];
    foreach ($reservations as $reservation) {
      $return[$reservation->uuid()] = [
        'start' => $reservation->getStartDate()->format(self::FORMAT),
        'end' => $reservation->getEndDate()->format(self::FORMAT),
      ];
    }
    return $return;
  }

  public function convertIds(array $uuids) {
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'uuid' => $uuids,
    ]);
    return !empty($nodes) ? array_keys($nodes) : [];
  }

  public function reservationsByNode($type, $exec = NULL) {
    $reservations = [];
    foreach ($this->reservations($type, $exec) as $reservation) {
      $reservations[$reservation->field_room->entity->uuid()][$reservation->uuid()] = $reservation;
    }
    return $reservations;
  }

  public function reservations($type, $exec = NULL) {
    $storage = $this->entityTypeManager->getStorage($type . '_reservation');
    $query = $storage->getQuery();
    if (is_callable($exec)) {
      $exec($query);
    }
    $ids = $query->sort('field_dates.value', 'ASC')->execute();
    $reservations = $storage->loadMultiple($ids);
    return $reservations;
  }

  public function hasReservationConflict(array $reservations, array $params) {
    return empty($this->getOpeningsByDuration($reservations, $params));
  }

  public function hasOpeningHoursConflict(array $reservations, array $params, $node) {
    if (!$params = $this->getOpenHoursParams($reservations, $params, $node)) {
      // Appears to be closed.
      return TRUE;
    }
    return $this->hasReservationConflict($reservations, $params);
  }

  /**
   * Checks if a reservation duration exceeds the room's maximum duration limit.
   *
   * @param array $params
   *   The requested start and end times.
   * @param \Drupal\node\NodeInterface $node
   *   The Room node.
   *
   * @return bool
   *   Whether there is a conflict with the room's maximum duration.
   */
  public function hasMaxDurationConflict(array $params, NodeInterface $node) {
    if (!empty($params['duration']) && $max_duration_interval = $this->getMaxRoomDuration($node)) {
      $max_duration = 0;
      if ($max_duration_interval->format('%h') > 0) {
        $max_duration = $max_duration_interval->format('%h') * 60;
      }
      $max_duration += $max_duration_interval->format('%i');
      if ($params['duration'] > $max_duration) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @param \Drupal\intercept_room_reservation\Entity\RoomReservationInterface[] $reservations
   *
   * @param array $params
   *
   * @param bool $open_only
   *
   * @return array
   */
  protected function getOpenings(array $reservations, array $params, $open_only = TRUE) {
    $openings = [];
    // Check if there is open space between existing reservations.
    $preceding = [];
    array_reduce($reservations, function ($datetime, $reservation) use (&$openings, &$preceding) {
      // Diff between current res start time and (either start time param or end date of last reservation).
      if ($opening = $this->getOpening($datetime, $reservation->getStartDate())) {
        if (!empty($preceding)) {
          $opening['preceding_reservations'] = $preceding;
          $preceding = [];
        }
        $opening['following_reservation'] = $reservation->id();
        $openings[] = $opening;
      }
      else {
        $preceding[] = $reservation->id();
      }
      return $reservation->getEndDate();
    }, $this->dateUtility->getDrupalDate($params['start']));

    // Now check open space between (start time or last reservation) and end time.
    $last_date = empty($reservations) ? $this->dateUtility->getDrupalDate($params['start']) : end($reservations)->getEndDate();
    $slot_end = $this->dateUtility->getDrupalDate($params['end']);
    if ($opening = $this->getOpening($last_date, $slot_end)) {
      if (!empty($preceding)) {
        $opening['preceding_reservations'] = $preceding;
        $preceding = [];
      }
      $openings[] = $opening;
    }
    // Openings will include all schedule info, so return either way.
    if (!empty($openings)) {
      return $openings;
    }
    // Return empty for a conflict check, but otherwise slot info.
    return $open_only ? [] : $preceding;
  }

  protected function getOpeningsByOpenHours(array $reservations, array $params, $node) {
    if (!$params = $this->getOpenHoursParams($reservations, $params, $node)) {
      return [];
    }
    return $this->getOpenings($reservations, $params);
  }

  protected function getOpeningsByDuration(array $reservation, array $params) {
    $openings = [];
    foreach ($this->getOpenings($reservation, $params) as $id => $opening) {
      if ($opening['duration'] >= $params['duration']) {
        $openings[$id] = $opening;
      }
    }
    return $openings;
  }

  private function getOpening($start, $end) {
    if ($start > $end) {
      return FALSE;
    }
    $total = Dates::duration($start, $end);
    if ($total > 0) {
      $data = [
        'duration' => $total,
        'start' => $start->format(self::FORMAT),
        'end' => $end->format(self::FORMAT),
      ];
      $data['unique_hash'] = hash('sha256', serialize($data));
      return $data;
    }
    return FALSE;
  }

  protected function getSchedule(array $reservations, array $params) {
    return $this->getOpenings($reservations, $params, FALSE);
  }

  protected function getScheduleByOpenHours(array $reservations, array $params, $node) {
    if (!$params = $this->getOpenHoursParams($reservations, $params, $node)) {
      return [];
    }
    return $this->getSchedule($reservations, $params);
  }

  /**
   * @param $reservations
   * @param $params
   * @param $node
   * @return bool|array
   */
  private function getOpenHoursParams($reservations, $params, $node) {
    // No changes if the location has no hours.
    if (!$hours = $this->getHours($params, $node)) {
      return FALSE;
    }
    foreach (['start', 'end'] as $type) {
      // Get location start/end hours for location.
      // Convert to date objects using the start date param as a base, but default timezone.
      // Convert timezone to UTC.
      // Return dates.
      $selected_date = $this->dateUtility->getDrupalDate($params[$type]);
      // Hardcode get start date here because the end date might span into another day.
      // @TODO: Make this less error prone by defining a way to specify the current searched "day".
      $date = $this->timeToDate($hours[$type . 'hours'], $this->dateUtility->getDate($params['start']));
      $converted_date = $this->dateUtility->convertTimezone($date);
      if ($type == 'start' && ($converted_date > $selected_date)) {
        $params['start'] = $converted_date->format(self::FORMAT);
      }
      if ($type == 'end' && ($converted_date > $selected_date)) {
        $params['end'] = $converted_date->format(self::FORMAT);
      }
    }
    return $params;
  }

  protected function getLocation(NodeInterface $node) {
    return !empty($node->field_location->entity) ? $node->field_location->entity : FALSE;
  }

  /**
   * Gets the room's maximum reservation duration.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Room node.
   *
   * @return DateInterval|bool
   *   The DateInterval object or FALSE.
   */
  protected function getMaxRoomDuration(NodeInterface $node) {
    if (!$node->hasField('field_reservation_time_max')) {
      return FALSE;
    }
    if ($duration = $node->get('field_reservation_time_max')->getValue()) {
      return new \DateInterval($duration[0]['value']);
    }
    return FALSE;
  }

  protected function getHours(array $params, NodeInterface $node) {
    if (!$location = $this->getLocation($node)) {
      return FALSE;
    }
    // Convert date to default time to match the field location storage.
    $start_date = $this->dateUtility->convertDate($params['start'], FALSE);
    $d = $start_date->format('w');
    // Eventually there is going to be a TIMEZONE setting on this field.
    $hours = $location->field_location_hours;
    $values = $hours->getValue();
    // e.g. 'starthours' => '0900', 'endhours' => '1700'.
    return array_reduce($values, function ($car, $val) use ($d) {
      if ($val['day'] == $d) {
        $car = $val;
        switch ($car['endhours']) {
          case '0000':
            $car['endhours'] = 2345;
            break;

          case substr($car['endhours'], 2) == '00':
            $car['endhours'] -= 55;
            break;

          default:
            $car['endhours'] -= 15;
        }
      }
      return $car;
    });
  }

  /**
   * Whether a location is closed.
   *
   * @param array $params
   *   The requested reservation parameters.
   * @param \Drupal\node\NodeInterface $node
   *   The room node.
   */
  protected function isClosed(array $params, NodeInterface $node) {
    $closed = empty($this->getHours($params, $node));

    \Drupal::moduleHandler()->alter('intercept_location_closed', $closed, $params, $node);

    return $closed;
  }

  /**
   * Get the message to display to users if a location is closed.
   *
   * @param array $params
   *   The requested reservation parameters.
   * @param \Drupal\node\NodeInterface $node
   *   The room node.
   */
  protected function closedMessage(array $params, NodeInterface $node) {
    $closed_message = t('Location Closed');

    \Drupal::moduleHandler()->alter('intercept_location_closed_message', $closed_message, $params, $node);

    return $closed_message;
  }

  protected function timeToDate($time, $base_date) {
    // Then just covert that time to a full date using the date part specified.
    // Make sure it's 4 digits.
    $time = \Drupal\office_hours\OfficeHoursDateHelper::datePad($time, 4);
    // Parse to be in the format for a date format.
    if (!strstr($time, ':')) {
      $time = substr('0000' . $time, -4);
      $hour = substr($time, 0, -2);
      $min = substr($time, -2);
      $time = $hour . ':' . $min;
    }
    $new_date_time = new DrupalDateTime($base_date->format('Y-m-d\T') . $time, $this->dateUtility->getDefaultTimezone());
    return $new_date_time;
  }

  protected function nodes($type, $ids = []) {
    $properties = [
      'type' => $type,
      'status' => 1,
      'field_reservable_online' => 1,
    ];
    if (!empty($ids)) {
      return $this->entityTypeManager->getStorage('node')->loadMultiple($ids);
    }
    return $this->entityTypeManager->getStorage('node')->loadByProperties($properties);
  }

  protected function getEmailConfig($type) {
    $config = $this->configFactory->get('intercept_room_reservation.settings')->get('email');
    return !empty($config[$type]) ? $config[$type] : FALSE;
  }

  /**
   * Run configured email notifications depending on reservation status.
   */
  public function notifyStatusChange(RoomReservationInterface $room_reservation, $original, $new) {
    if ($room_reservation->isNew()) {
      $original = 'empty';
    }
    $config = $this->configFactory->get('intercept_room_reservation.settings')->get('email');
    $emails = [];
    foreach ($config as $mail_key => $settings) {
      // Check if this email should be only sent out for certain logged in users.
      $pass = FALSE;
      if (!empty($settings['user'])) {
        switch ($settings['user']) {
          case 'reservation_user':
            $reservation_user = $room_reservation->getRegistrant();
            $pass = $reservation_user && $this->matchesCurrentUser($reservation_user->id());
            break;

          case 'reservation_author':
            $reservation_author = $room_reservation->getOwner();
            $pass = $reservation_author && $this->matchesCurrentUser($reservation_author->id());
            break;

          case 'user_role':
            $user_roles = !empty($settings['user_role']) ? $settings['user_role'] : [];
            $roles = $this->currentUser->getRoles();
            $pass = !empty(array_intersect(array_values($user_roles), $roles));
            break;
        }

        if (!$pass) {
          continue;
        }
      }
      if (empty($settings['status_original']) || empty($settings['status_new'])) {
        continue;
      }
      $status_original = $settings['status_original'];
      $status_new      = $settings['status_new'];
      if (empty($status_original[$original]) && empty($status_original['any'])) {
        continue;
      }
      if (empty($status_new[$new]) && empty($status_new['any'])) {
        continue;
      }
      $emails[$mail_key] = $settings;
    }
    foreach ($emails as $mail_key => $settings) {
      $this->email($mail_key, $room_reservation);
    }
  }

  private function matchesCurrentUser($uid) {
    return $uid == $this->currentUser->id();
  }

  public function email($key, RoomReservation $room_reservation) {
    if (!$config = $this->getEmailConfig($key)) {
      return;
    }

    // Get room reservation author.
    if (!($user = $room_reservation->field_user->entity) || !$user->getEmail()) {
      // Watchdog error possibly.
      return;
    }

    $this->mailManager->mail('intercept_room_reservation', $key, $user->getEmail(), 'en', [
      'reservation_manager' => $this,
      'email_config' => $config,
      'room_reservation' => $room_reservation,
    ]);
  }

  /**
   * Build email content from configuration and parameters.
   *
   * @see intercept_room_reservation_mail()
   */
  public function buildEmail($key, &$message, $params) {
    $headers['content-type'] = 'text/html';
    $message['headers'] = $headers;
    $email_config = $params['email_config'];
    $variables = [
      '%site_name' => \Drupal::config('system.site')->get('name'),
      '%username' => 'username',
    ];

    $token_replacements = [
      'room_reservation' => $params['room_reservation'],
    ];
    $subject = $this->token->replace($email_config['subject'], $token_replacements);
    $body = $this->token->replace($email_config['body'], $token_replacements);
    $message['subject'] = str_replace(["\r", "\n"], '', $subject);
    $message['body'][] = $body;
  }

}
