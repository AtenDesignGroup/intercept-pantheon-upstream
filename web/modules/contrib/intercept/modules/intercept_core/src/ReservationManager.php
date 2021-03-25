<?php

namespace Drupal\intercept_core;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Token;
use Drupal\intercept_core\Utility\Dates;
use Drupal\intercept_room_reservation\Entity\RoomReservationInterface;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\node\NodeInterface;

/**
 * Class ReservationManager.
 *
 * @TODO: Move partially over to an EntityReservationManager/RoomReservationManager.
 */
class ReservationManager implements ReservationManagerInterface {

  use DependencySerializationTrait;

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The Intercept dates utility.
   *
   * @var \Drupal\intercept_core\Utility\Dates
   */
  protected $dateUtility;

  /**
   * The token utility service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The current session account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ReservationManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\intercept_core\Utility\Dates $date_utility
   *   The Intercept dates utility.
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current session account.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, Dates $date_utility, Token $token, AccountProxyInterface $current_user, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
    $this->dateUtility = $date_utility;
    $this->token = $token;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function dateUtility() {
    return $this->dateUtility;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventReservation(NodeInterface $event) {
    if ($event->isNew()) {
      return FALSE;
    }
    $reservations = $this->reservations('room', function ($query) use ($event) {
      $query->condition('field_event', $event->id(), '=');
      $query->condition('field_status', ['canceled', 'denied'], 'NOT IN');
      $query->sort('field_dates.value', 'ASC');
    });
    if (!empty($reservations)) {
      $reservation = reset($reservations);
      return $reservation;
    }
    return FALSE;
  }

  /**
   * Create a new room reservation entity based on an event node.
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
   */
  public function updateEventReservation(RoomReservationInterface $reservation, NodeInterface $event) {
    if (!$event->field_room->equals($reservation->field_room)) {
      $reservation->field_room = $event->field_room;
    }
    $reservation->save();
  }

  /**
   * A list of emails to use for reservations.
   */
  public static function emails() {
    $emails = [
      'reservation_requested' => new TranslatableMarkup('Reservation requested'),
      'reservation_canceled' => new TranslatableMarkup('Reservation canceled'),
      'reservation_approved_staff' => new TranslatableMarkup('Reservation approved (by staff)'),
      'reservation_approved_auto' => new TranslatableMarkup('Reservation approved (auto)'),
      'reservation_canceled_staff' => new TranslatableMarkup('Reservation canceled (by staff)'),
      'reservation_denied_staff' => new TranslatableMarkup('Reservation denied (by staff)'),
    ];

    \Drupal::moduleHandler()->alter('intercept_reservation_emails', $emails);

    return $emails;
  }

  /**
   * Determines whether a user has exceeded the global room reservation limit.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to check.
   *
   * @return bool
   *   Whether the user has exceeded the global room reservation limit.
   */
  public function userExceededReservationLimit(AccountInterface $user) {
    if ($user->hasPermission('bypass room reservation limit')) {
      return FALSE;
    }
    $config = $this->configFactory->get('intercept_room_reservation.settings');
    return $this->userReservationCount($user) >= $config->get('reservation_limit');
  }

  /**
   * Gets the number of room reservations made by a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to check.
   *
   * @return int
   *   The number of room reservations made by a user.
   */
  public function userReservationCount(AccountInterface $user) {
    $reservations = $this->currentUserReservations($user);

    $this->moduleHandler->alter('intercept_room_reservation_limit', $reservations);

    return count($reservations);
  }

  /**
   * Gets the room reservations made by a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to check.
   *
   * @return array
   *   The room reservations made by a user.
   */
  public function currentUserReservations(AccountInterface $user) {
    $reservations = $this->reservations('room', function ($query) use ($user) {
      $query->condition('field_user', $user->id(), '=');
      $date = new DrupalDateTime('now', $this->dateUtility->getDefaultTimezone());
      $query->condition('field_dates.end_value', $date->format('Y-m-d\TH:i:sP'), '>');
      $query->condition('field_status', ['requested', 'approved'], 'IN');
      $query->sort('field_dates.value', 'ASC');
    });

    return $reservations;
  }

  /**
   * {@inheritdoc}
   */
  public function duration(string $start, string $end) {
    return $this->dateUtility->duration(
      $this->dateUtility->getDrupalDate($start),
      $this->dateUtility->getDrupalDate($end)
    );
  }

  /**
   * Checks if a reservation has an event.
   *
   * @param $reservation
   *   The reservation entity.
   *
   * @return bool
   */
  public function hasEvent($reservation) {
    return $reservation->hasField('field_event') && !empty($reservation->field_event->getValue());
  }

  /**
   * Checks if the user associated with the reservation is staff.
   *
   * @param $reservation
   *   The reservation entity.
   *
   * @return bool
   */
  public function isStaff($reservation) {
    $is_staff = FALSE;
    $user_roles = $reservation->get('field_user')->entity->getRoles();
    if (in_array('intercept_staff', $user_roles, TRUE)) {
      $is_staff = TRUE;
    }
    return $is_staff;
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

    $user_exceeded_limit = $this->userExceededReservationLimit($this->currentUser);
    $room_reservations = $this->roomReservationsByNode($params);

    $rooms = $this->nodes('room', isset($params['rooms']) ? $params['rooms'] : []);
    $return = [];

    $timezone_info = [
      'default_timezone' => $this->dateUtility->getDefaultTimezone()->getName(),
      'storage_timezone' => $this->dateUtility->getStorageTimezone()->getName(),
    ];

    $param_info = [];
    foreach (['start', 'end'] as $param) {
      $date = $this->dateUtility->getDrupalDate($params[$param]);
      $param_info[$param]['storage_timezone'] = $date->format(self::FORMAT);
      $param_info[$param]['default_timezone'] = $this->dateUtility->convertTimezone($date, 'default')->format(self::FORMAT);
    }

    foreach ($rooms as $room) {
      $uuid = $room->uuid();
      if ($debug) {
        $return[$uuid]['debug'] = [];
        $debug_data = &$return[$uuid]['debug'];
      }
      $return[$uuid]['user_exceeded_limit'] = $user_exceeded_limit;
      $reservations = !empty($room_reservations[$uuid]) ? $room_reservations[$uuid] : [];
      $blocked_dates = $this->getBlockedDates($reservations, $params, $room);
      $return[$uuid]['has_reservation_conflict'] = $this->hasReservationConflict($blocked_dates, $params);
      $return[$uuid]['has_conflict'] = $this->aggressiveOpeningHoursConflict($blocked_dates, $params, $room);
      $return[$uuid]['has_open_hours_conflict'] = $this->hasOpeningHoursConflict($blocked_dates, $params, $room);
      $return[$uuid]['has_max_duration_conflict'] = $this->hasMaxDurationConflict($params, $room);
      $return[$uuid]['is_closed'] = $this->isClosed($params, $room);
      $return[$uuid]['closed_message'] = $this->closedMessage($params, $room);
      $return[$uuid]['has_location'] = !empty($this->getLocation($room));
      if ($debug) {
        $debug_data['schedule'] = $this->getSchedule($reservations, $params);
        $debug_data['schedule_by_open_hours'] = $this->getScheduleByOpenHours($reservations, $params, $room);
        $debug_data['hours'] = FALSE;
        if (!$this->isClosed($params, $room)) {
          $hours = $this->getHours($params, $room);
          $hours_start = $this->timeToDate($hours['start_datetime'], $this->dateUtility->getDate($params['start']));
          $hours_end = $this->timeToDate($hours['end_datetime'], $this->dateUtility->getDate($params['end']));
          $debug_data['hours'] = [
            'start' => ['raw' => $hours['start_datetime']],
            'end' => ['raw' => $hours['end_datetime']],
          ];

          $debug_data['hours']['start']['default_timezone'] = $hours_start->format(self::FORMAT);
          $debug_data['hours']['start']['storage_timezone'] = $this->dateUtility->convertTimezone($hours_start, 'storage')->format(self::FORMAT);
          $debug_data['hours']['end']['default_timezone'] = $hours_end->format(self::FORMAT);
          $debug_data['hours']['end']['storage_timezone'] = $this->dateUtility->convertTimezone($hours_end, 'storage')->format(self::FORMAT);
        }
      }
      $return[$uuid]['dates'] = $blocked_dates;
      if ($debug) {
        $debug_data['room_nid'] = $room->id();
        $debug_data['location_nid'] = !empty($this->getLocation($room)) ? $this->getLocation($room)->id() : FALSE;
        $debug_data['param_info'] = $param_info;
        $debug_data['timezone_info'] = $timezone_info;
      }
    }
    $debug = 'da';
    return $return;
  }

  /**
   * Gets an array of start and end dates, keyed by reservation UUID.
   *
   * @param array $reservations
   *   An array of existing reservations.
   * @param array $params
   *   An array of reservation parameters.
   * @param \Drupal\node\NodeInterface $node
   *   A Room node.
   *
   * @return array
   *   An array of start and end dates, keyed by reservation UUID.
   */
  public function getBlockedDates(array $reservations, array $params, NodeInterface $node) {
    $dates = [];
    foreach ($reservations as $reservation) {
      /** @var \Drupal\intercept_core\Entity\ReservationInterface $reservation */
      $message = $this->t('Booked');
      if ($reservation->hasField('field_event') && !empty($reservation->field_event->getValue())) {
        $message = $reservation->field_event->entity->title->value;
      }
      if ($reservation->hasField('field_group_name') && $reservation->field_group_name->value && $this->currentUser->hasPermission('update any room_reservation')) {
        $message = $reservation->field_group_name->value;
      }
      $dates[$reservation->uuid()] = [
        'uuid' => $reservation->uuid(),
        'id' => $reservation->id(),
        'resource' => $node->uuid(),
        'start' => $reservation->getStartDate()->format('c'),
        'end' => $reservation->getEndDate()->format('c'),
        'status' => $reservation->getStatus(),
        'message' => $message,
        'hasEvent' => $this->hasEvent($reservation),
        'isReservedByStaff' => $this->isStaff($reservation),
      ];
    }

    // Convert date to default time to match the field location storage.
    $start_date = $this->dateUtility->getDate($params['start']);
    $weekday = $start_date->format('w');
    // To accommodate gaps in open hours.
    $hours_on_day = $this->getLocationWeekdayOfficeHours($node, $weekday);
    if ($hours_on_day && count($hours_on_day) > 1) {
      usort($hours_on_day, function ($a, $b) {
        return strcasecmp($b['starthours'], $a['starthours']);
      });
      foreach ($hours_on_day as $key => $hours) {
        if (array_key_exists($key + 1, $hours_on_day) && $hours_on_day[$key]['endhours'] !== $hours_on_day[$key + 1]['starthours']) {
          $dates[] = [
            'id' => $key,
            'resource' => $node->uuid(),
            'start' => $this->dateUtility->convertTimezone($this->timeToDate($hours['endhours'], $start_date))->format('c'),
            'end' => $this->dateUtility->convertTimezone($this->timeToDate($hours_on_day[$key + 1]['starthours'], $start_date))->format('c'),
            'message' => $this->t('Location Closed'),
          ];
        }
      }
    }

    $this->moduleHandler->alter('intercept_room_reservation_dates', $dates, $params, $node);

    usort($dates, function ($a, $b) {
      return strcasecmp($a['start'], $b['start']);
    });
    return $dates;
  }

  /**
   * Converts an array of UUIDs to Node IDs.
   *
   * @param array $uuids
   *   An array of UUIDs.
   *
   * @return array
   *   An array of Node IDs.
   */
  public function convertIds(array $uuids) {
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'uuid' => $uuids,
    ]);
    return !empty($nodes) ? array_keys($nodes) : [];
  }

  /**
   * Gets all Reservations of a type keyed by room UUID.
   *
   * @param string $type
   *   The type of Reservation entity.
   * @param mixed $exec
   *   A query function, or NULL.
   *
   * @return array
   *   An array of Reservation entities keyed by room UUID.
   */
  public function reservationsByNode($type, $exec = NULL) {
    $reservations = [];
    $room_reservations = $this->reservations($type, $exec);
    foreach ($room_reservations as $reservation) {
      if ($reservation->field_room->entity) {
        $reservations[$reservation->field_room->entity->uuid()][$reservation->uuid()] = $reservation;
      }
    }
    return $reservations;
  }

  /**
   * {}.
   *
   * @param array $params
   *
   * @return array
   */
  public function roomReservationsByNode(array $params) {
    return $this->reservationsByNode('room', function ($query) use ($params) {
      $start_date = $this->dateUtility->getDate($params['start']);
      $end_date = $this->dateUtility->getDate($params['end']);
      if (!empty($params['rooms'])) {
        $query->condition('field_room', $params['rooms'], 'IN');
      }
      // If we're editing a room reservation, we need to make sure to not
      // count the existing reservation towards unavailability.
      if (!empty($params['event'])) {
        $query->condition('field_event', $params['event'], '!=');
      }
      if (!empty($params['exclude_uuid'])) {
        $query->condition('uuid', $params['exclude_uuid'], 'NOT IN');
      }
      if (!empty($params['exclude'])) {
        $query->condition('id', $params['exclude'], 'NOT IN');
      }
      $query->condition('field_status', ['canceled', 'denied'], 'NOT IN');
      $range = [$start_date->format(self::FORMAT), $end_date->format(self::FORMAT)];
      $query->condition($query->orConditionGroup()
        // Date start value is in between start / end params.
        ->condition('field_dates.value', $range, 'BETWEEN')
        // OR Date end value is in between start / end params.
        ->condition('field_dates.end_value', $range, 'BETWEEN')
        // OR Date start and date end values span larger
        // than the start / end params.
        ->condition($query->andConditionGroup()
          ->condition('field_dates.value', $range[0], '<=')
          ->condition('field_dates.end_value', $range[1], '>=')
        )
      );
      $query->sort('field_dates.value', 'ASC');
    });
  }

  /**
   * Gets all Reservations of a type.
   *
   * @param string $type
   *   The type of Reservation entity.
   * @param mixed $exec
   *   A query function, or NULL.
   *
   * @return array
   *   An array of Reservation entities.
   */
  public function reservations($type, $exec = NULL) {
    $storage = $this->entityTypeManager->getStorage($type . '_reservation');
    $query = $storage->getQuery();
    if (is_callable($exec)) {
      $exec($query);
    }
    $ids = $query->execute();
    $reservations = $storage->loadMultiple($ids);
    return $reservations;
  }

  /**
   * {@inheritdoc}
   */
  public function hasReservationConflict(array $reservations, array $params) {
    return empty($this->getOpeningsByDuration($reservations, $params));
  }

  /**
   * {@inheritdoc}
   */
  public function aggressiveOpeningHoursConflict(array $reservations, array $params, NodeInterface $room) {
    if ($this->hasOpeningHoursConflict($reservations, $params, $room)) {
      return TRUE;
    }

    // Get the open hours parameters.
    $openHoursParams = $this->getOpenHoursParams($params, $room);

    // Reservation is within opening hours, check for conflicting reservations
    // during opening hours.
    return $this->hasReservationConflict($reservations, $openHoursParams);
  }

  /**
   * {@inheritdoc}
   */
  public function hasOpeningHoursConflict(array $reservations, array $params, NodeInterface $room) {
    // Get the open hours parameters.
    $openHoursParams = $this->getOpenHoursParams($params, $room);

    // Appears to be closed. Considered conflicted.
    if (!$openHoursParams) {
      return TRUE;
    }

    // Get opening hours.
    $hours = $this->getHours($params, $room);

    // Get the open and close times in UTC so we can compare.
    $utc_open = $this->dateUtility->convertTimezone($hours['start_datetime']);
    $utc_open = $utc_open->format(self::FORMAT);
    $utc_close = $this->dateUtility->convertTimezone($hours['end_datetime']);
    $utc_close = $utc_close->format(self::FORMAT);

    // Constrain the start and end times to opening hours.
    $start_boundary = $utc_open > $params['start'] ? $utc_open : $params['start'];
    $end_boundary = $utc_close < $params['end'] ? $utc_close : $params['end'];

    // If the start and end time fall completely out of open hours, consider conflicted.
    if ($start_boundary > $end_boundary) {
      return TRUE;
    }

    // If the duration is greater than the boundaries, it won't fit. Consider conflicted.
    if ($params['duration'] > $this->duration($start_boundary, $end_boundary)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMaxDurationConflict(array $params, NodeInterface $room) {
    if (!empty($params['duration']) && $max_duration_interval = $this->getMaxRoomDuration($room)) {
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
   * Gets openings for a range of dates given existing Room Reservations.
   *
   * @param array $blocked_times
   *   An array of blocked time, keyed with 'start' and 'end'.
   * @param array $params
   *   The requested start and end times.
   * @param bool $open_only
   *   Whether a booked range of dates should return the reservation schedule.
   *
   * @return array
   *   An array of openings.
   */
  protected function getOpenings(array $blocked_times, array $params, $open_only = TRUE) {
    $openings = [];
    // Check if there is open space between existing reservations.
    $preceding = [];
    $parameter_start_date = $this->dateUtility->getDrupalDate($params['start']);
    $parameter_end_date = $this->dateUtility->getDrupalDate($params['end']);

    foreach ($blocked_times as $blocked_time) {
      $blocked_start_date = $this->dateUtility->getDrupalDate($blocked_time['start']);
      $blocked_end_date = $this->dateUtility->getDrupalDate($blocked_time['end']);
      if ($blocked_start_date < $parameter_start_date && $blocked_end_date > $parameter_end_date) {
        return [];
      }
      // Diff between current res start time and
      // (either start time param or end date of last reservation).
      if ($opening = $this->getOpening($parameter_start_date, $blocked_start_date)) {
        if (!empty($preceding)) {
          $opening['preceding_reservations'] = $preceding;
          $preceding = [];
        }
        if (isset($blocked_time['id'])) {
          $opening['following_reservation'] = $blocked_time['id'];
        }
        $openings[] = $opening;
      }
      else {
        if (!empty($blocked_time['id'])) {
          $preceding[] = $blocked_time['id'];
        }
      }
    }

    // Now check open space between
    // (start time or last reservation) and end time.
    $last_date = empty($blocked_times) ? $parameter_start_date : $this->dateUtility->getDrupalDate(end($blocked_times)['end']);
    $slot_end = $parameter_end_date;
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

  /**
   * Gets openings based on a Location's open hours.
   *
   * @param \Drupal\intercept_room_reservation\Entity\RoomReservationInterface[] $reservations
   *   An array of Room Reservation entities.
   * @param array $params
   *   The requested start and end times.
   * @param \Drupal\node\NodeInterface $node
   *   The Room node.
   *
   * @return array
   *   An array of openings.
   */
  protected function getOpeningsByOpenHours(array $reservations, array $params, NodeInterface $node) {
    if (!$params = $this->getOpenHoursParams($params, $node)) {
      return [];
    }
    foreach ($this->getOpenings($reservations, $params) as $id => $opening) {
      if ($opening['duration'] >= $params['duration']) {
        $openings[$id] = $opening;
      }
    }
    return $openings;
  }

  /**
   * Gets openings based on a Location's open hours.
   *
   * @param \Drupal\intercept_room_reservation\Entity\RoomReservationInterface[] $reservations
   *   An array of Room Reservation entities.
   * @param array $params
   *   An array of parameters that must include duration.
   *
   * @return array
   *   An array of openings.
   */
  protected function getOpeningsByDuration(array $reservations, array $params) {
    $openings = [];
    foreach ($this->getOpenings($reservations, $params) as $id => $opening) {
      if ($opening['duration'] >= $params['duration']) {
        $openings[$id] = $opening;
      }
    }
    return $openings;
  }

  /**
   * Formats an opening array based on a start and end date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The start DrupalDateTime object.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end
   *   The end DrupalDateTime object.
   */
  private function getOpening(DrupalDateTime $start, DrupalDateTime $end) {
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

  /**
   * Gets all reservations for a date range.
   *
   * @param \Drupal\intercept_room_reservation\Entity\RoomReservationInterface[] $reservations
   *   An array of Room Reservation entities.
   * @param array $params
   *   The requested start and end times.
   *
   * @return array
   *   An array of openings.
   */
  protected function getSchedule(array $reservations, array $params) {
    return $this->getOpenings($reservations, $params, FALSE);
  }

  /**
   * Gets all reservations during a Location's open hours.
   *
   * @param \Drupal\intercept_room_reservation\Entity\RoomReservationInterface[] $reservations
   *   An array of Room Reservation entities.
   * @param array $params
   *   The requested start and end times.
   * @param \Drupal\node\NodeInterface $node
   *   A Location node.
   *
   * @return array
   *   An array of openings.
   */
  protected function getScheduleByOpenHours(array $reservations, array $params, NodeInterface $node) {
    if (!$params = $this->getOpenHoursParams($params, $node)) {
      return [];
    }
    return $this->getSchedule($reservations, $params);
  }

  /**
   * Returns the open and close time of a Location as a parameter array.
   *
   * @param array $params
   *   The requested start and end times.
   * @param \Drupal\node\NodeInterface $node
   *   The Location node.
   *
   * @return bool|array
   *   The parameter array, or FALSE.
   */
  private function getOpenHoursParams(array $params, NodeInterface $node) {
    // No changes if the location has no hours.
    if (!$hours = $this->getHours($params, $node)) {
      return FALSE;
    }
    foreach (['start', 'end'] as $type) {
      // Get location start/end hours for location.
      // Convert to date objects us ing the start date param as a base,
      // but default timezone.
      // Convert timezone to UTC.
      // Return dates.
      $selected_date = $this->dateUtility->getDrupalDate($params[$type]);
      // Hardcode get start date here because the end date might span
      // into another day.
      // @TODO: Make this less error prone by defining a way to specify the current searched "day".
      $date = $hours[$type . '_datetime'];
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

  /**
   * Gets the Location Node attached to a Node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node with a field_location reference.
   *
   * @return \Drupal\node\NodeInterface
   *   The Location node.
   */
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
      return new \DateInterval($duration[0]['duration']);
    }
    return FALSE;
  }

  /**
   * Gets the office_hours value for a Location.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node referencing a Location.
   *
   * @return array|null
   *   An array of office_hours timeslots, or NULL.
   */
  protected function getLocationOfficeHours(NodeInterface $node) {
    $location = $this->getLocation($node);

    if (!$location || !$location->hasField('field_location_hours')) {
      return NULL;
    }

    return (array) $location->field_location_hours->getValue();
  }

  /**
   * Gets the office_hours value for a Location.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node referencing a Location.
   * @param string $weekday
   *   The 'w' numeric representation of the day of the week.
   *
   * @return array|null
   *   An array of office_hours timeslots, or NULL.
   */
  protected function getLocationWeekdayOfficeHours(NodeInterface $node, $weekday) {
    if (!$hours_values = $this->getLocationOfficeHours($node)) {
      return NULL;
    }
    return array_filter($hours_values, function ($period) use ($weekday) {
      return $period['day'] == $weekday;
    });
  }

  /**
   * Gets the open hours for a Location.
   *
   * @param array $params
   *   The requested start and end times.
   * @param \Drupal\node\NodeInterface $node
   *   The node referencing a Location.
   *
   * @return array
   *   An array of open hours.
   */
  protected function getHours(array $params, NodeInterface $node) {
    $open_hours = [];

    // Convert date to default time to match the field location storage.
    $start_date = $this->dateUtility->getDate($params['start']);
    $end_date = $this->dateUtility->getDate($params['end']);
    $weekday = $start_date->format('w');

    if (!$hours_on_day = $this->getLocationWeekdayOfficeHours($node, $weekday)) {
      return $open_hours;
    }

    $day_end_buffer = $this->getLastReservationConfig();
    // 'starthours' => '0900', 'endhours' => '1700'.
    $start_hours = min(array_column($hours_on_day, 'starthours'));
    $end_hours = max(array_column($hours_on_day, 'endhours'));
    if ($day_end_buffer > 0) {
      switch ($end_hours) {
        case '0000':
          $end_hours = 2400 - $day_end_buffer;
          break;

        case substr($end_hours, 2) == '00':
          $end_hours = $end_hours - 40 - $day_end_buffer;
          break;

        default:
          $end_hours = $end_hours - $day_end_buffer;
      }
    }

    // Create a DateTime object for the opening hours in the default timezone.
    $hours_on_date['start_datetime'] = $this->timeToDate($start_hours, $start_date);
    $hours_on_date['end_datetime'] = $this->timeToDate($end_hours, $start_date);

    return $hours_on_date;
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

    $this->moduleHandler->alter('intercept_location_closed', $closed, $params, $node);

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
    $closed_message = $this->t('Location Closed');

    $this->moduleHandler->alter('intercept_location_closed_message', $closed_message, $params, $node);

    return $closed_message;
  }

  /**
   * Converts a time string to a DrupalDateTime.
   *
   * @param string $time
   *   The time string.
   * @param \DateTime $base_date
   *   The DateTime object to convert a time for.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A DrupalDateTime object.
   */
  protected function timeToDate($time, \DateTime $base_date) {
    // Then just convert that time to a full date using the date part specified.
    // Make sure it's 4 digits.
    $time = OfficeHoursDateHelper::datePad($time, 4);
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

  /**
   * Gets the loaded Nodes given an array.
   *
   * @param string $type
   *   The Node bundle name.
   * @param array $ids
   *   An array of Node IDs.
   *
   * @return \Drupal\node\NodeInterface[]
   *   An array of loaded Node objects.
   */
  protected function nodes($type, array $ids = []) {
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

  /**
   * Gets the intercept_room_reservation email settings config.
   *
   * @param string $type
   *   The key of the email settings config.
   *
   * @return array
   *   The intercept_room_reservation email settings config.
   */
  protected function getEmailConfig($type) {
    $config = $this->configFactory->get('intercept_room_reservation.settings')->get('email');
    return !empty($config[$type]) ? $config[$type] : FALSE;
  }

  /**
   * Gets the intercept_room_reservation last reservation settings config.
   *
   * @return int
   *   The intercept_room_reservation last reservation settings config.
   */
  protected function getLastReservationConfig() {
    $config = (int) $this->configFactory->get('intercept_room_reservation.settings')->get('last_reservation_before_closing');
    return $config ?: 0;
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
      // Check if this email should be only sent out
      // for certain logged in users.
      $pass = FALSE;
      if (!empty($settings['user'])) {
        switch ($settings['user']) {
          case 'reservation_user':
            $reservation_user = $room_reservation->getReservor();
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
    // Don't send 2 emails to the same staff member if they canceled their own reservation.
    if (isset($emails['reservation_canceled']) && isset($emails['reservation_canceled_staff']) && $reservation_user && $reservation_author->id() == $reservation_user->id()) {
      unset($emails['reservation_canceled_staff']);
    }
    foreach ($emails as $mail_key => $settings) {
      $this->email($mail_key, $room_reservation);
    }
  }

  /**
   * Whether the provided uid matches the currently logged-in user.
   *
   * @param int $uid
   *   The user ID to check.
   *
   * @return bool
   *   Whether the provided uid matches the currently logged-in user.
   */
  private function matchesCurrentUser($uid) {
    return $uid === $this->currentUser->id();
  }

  /**
   * Generates an email for Room Reservations.
   *
   * @param string $key
   *   The type of email to generate.
   * @param \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $room_reservation
   *   The Room Reservation entity.
   */
  public function email($key, RoomReservationInterface $room_reservation) {
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

    $token_replacements = [
      'room_reservation' => $params['room_reservation'],
    ];
    $subject = $this->token->replace($email_config['subject'], $token_replacements);
    $body = $this->token->replace($email_config['body'], $token_replacements);
    $message['subject'] = str_replace(["\r", "\n"], '', $subject);
    $message['body'][] = $body;
  }

  /**
   * {@inheritdoc}
   */
  public function getReservationsByUser($type, AccountInterface $user) {
    return $this->reservations($type, function ($query) use ($user) {
      $orConditionGroup = $query->orConditionGroup();
      $orConditionGroup->condition('field_user', $user->id());
      $orConditionGroup->condition('author', $user->id());
      $query->condition($orConditionGroup);
      $query->sort('created', 'DESC');
    });
  }

}
