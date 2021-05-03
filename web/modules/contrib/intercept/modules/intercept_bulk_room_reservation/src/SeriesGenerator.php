<?php

namespace Drupal\intercept_bulk_room_reservation;

use RRule\RRule;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Generates a SeriesGenerator object.
 */
class SeriesGenerator implements SeriesGeneratorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SeriesGenerator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Creates a series of events from the values in a BulkReservationForm.
   *
   * If the form has been submitted, this service returns an array of events,
   * otherwise it returns an array of $dates and $rooms.
   *
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateInterface from BulkReservationForm.
   * @param string $return
   *   Indicates whether the service should return room data or an event series.
   *
   * @return array
   *   The series array of events for a bulk reservation.
   */
  public function generateSeries(FormStateInterface $form_state, $return = 'room_data') {
    // Initialize some variables.
    $series = [];
    $entity = $form_state->getFormObject()->getEntity();
    $locationNid = $entity->isNew() ? $form_state->getUserInput()['field_location']
      : $entity->field_location->target_id;
    $input = $form_state->getUserInput();
    $values = $form_state->getValues();
    $format = 'Y-m-d H:i';

    // Get a list of room node ids.
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'room')
      ->condition('field_location', $locationNid);
    $roomNids = $query->execute();

    switch ($return) {
      case 'room_data':
        $rooms = $this->entityTypeManager->getStorage('node')->loadMultiple($roomNids);
        break;

      default:
        foreach ($values['field_room'] as $key => $target) {
          $rooms[] = $this->entityTypeManager->getStorage('node')->load($target['target_id']);
        }
    }

    // For the room select field, need to return an array of Room labels
    // keyed by Room node ids, and another array similarly keyed with
    // availability attributes.
    $roomOptions = [];
    $roomAttributes = [];

    // Create an array of dates to check for room availability.
    $logistics = [];
    switch ($entity->isNew()) {
      case FALSE:
        $logistics = $this->getLogisticsFromEntity($entity);
        break;

      default:
        $logistics = $this->getLogisticsFromFormState($form_state);

    }

    $dates = $logistics['dates'];
    $timezone = new \DateTimeZone('America/New_York');
    foreach ($rooms as $room) {
      foreach ($dates as $date) {
        foreach (array_keys($logistics['times']) as $point) {
          $timestamp = strtotime($date . ' ' . $logistics['times'][$point]);
          $range[$point] = new \DateTime('@' . $timestamp);
          $range[$point]->setTimezone($timezone);
        }
        $roomOptions[$room->id()] = $room->label();

        // Determine this room's availability for the series.
        $available = intercept_bulk_room_reservation_check_availability([$room->id()], $range);
        $attribute = ($available) ? '' : 'disabled';
        $roomAttributes[$room->id()] = [
          'disabled' => [$attribute],
        ];
        if (!$available) {
          // Move on to the next room.
          continue 2;
        }
        $series[] = [
          'room' => $room->id(),
          'range' => $range,
        ];
      }
    }

    // On form submission we're only interested in the $series.
    if ($form_state->isSubmitted()) {
      return $series;
    }

    // Sort the room options alphabetically and build a combined array of
    // room data.
    asort($roomOptions);
    $room_data = [
      'room_options' => $roomOptions,
      'room_attributes' => $roomAttributes,
    ];

    if ($return == 'room_data') {
      return $room_data;
    }

    return $dates;
  }

  /**
   * {@inheritDoc}
   */
  public function getDayOfWeek($date) {
    $week = [
      'SU',
      'MO',
      'TU',
      'WE',
      'TH',
      'FR',
      'SA',
    ];
    return $week[date('w', strtotime($date))];
  }

  /**
   * {@inheritDoc}
   */
  public function getDaysToCheck(array $dateField) {
    $daysToCheck = 0;

    switch ($dateField['ends_mode']) {
      case 'infinite':
        // @todo Get $maxYears from the field configuration.
        $maxYears = 2;
        $daysToCheck = (365 * $maxYears);
        break;

      case 'count':
        $daysToCheck = $dateField['ends_count'];
        break;

      case 'date':
        $startDate = strtotime($dateField['start']['date'] . ' ' . $dateField['start']['time']);
        $endDate = strtotime($dateField['ends_date']['date'] . ' ' . $dateField['ends_date']['time']);
        $daysToCheck = round(($endDate - $startDate) / (60 * 60 * 24));
    }

    return $daysToCheck;
  }

  /**
   * {@inheritDoc}
   */
  public function getStartOfMonth($dateField) {
    $startDate = $dateField['start']['date'];
    $timeZone = new \DateTimeZone('America/New_York');
    return new \DateTime(substr($startDate, 0, 8) . '01', $timeZone);
  }

  /**
   * {@inheritDoc}
   */
  public function weekOfYear($date) {
    $weekOfYear = intval(date("W", $date));
    if (date('n', $date) == "1" && $weekOfYear > 51) {
      // It's the last week of the previous year.
      $weekOfYear = 0;
    }
    return $weekOfYear;
  }

  /**
   * {@inheritDoc}
   */
  public function weekOfMonth($date) {
    // Get the first day of the month.
    $firstOfMonth = strtotime(date("Y-m-01", $date));
    // Apply above formula.
    return $this->weekOfYear($date) - $this->weekOfYear($firstOfMonth) + 1;
  }

  /**
   * {@inheritDoc}
   */
  private function getLogisticsFromEntity($entity) {
    $logistics = [
      'dates' => [],
      'times' => [],
    ];

    switch ($entity->getEntityType()->id()) {
      case 'node':
        // Derive the start and end times from $entity->field_date_time.
        $logistics['times'] = [
          'start' => \DateTime::createFromFormat('Y-m-d\TH:i:s', $entity->field_date_time->value)->format('H:i:s'),
          'end' => \DateTime::createFromFormat('Y-m-d\TH:i:s', $entity->field_date_time->value)->format('H:i:s'),
        ];
        if (!empty($entity->get('event_recurrence')->referencedEntities())) {
          $rules = $entity->get('event_recurrence')->referencedEntities()[0]->field_event_rrule->rrule;
          $dtStartString = $entity->get('event_recurrence')->referencedEntities()[0]->field_event_rrule->value;
          $timezone = new \DateTimeZone('America/New_York');
          $rules = $rules . ';DTSTART=' . \DateTime::createFromFormat('Y-m-d\TH:i:s', $dtStartString, $timezone)->format('Y-m-d');
          $rrule = new RRule($rules);
          foreach ($rrule as $occurrence) {
            // Skip past occurrences.
            $timestamp = $occurrence->getTimestamp();
            if ($occurrence->getTimestamp() < time()) {
              continue;
            }
            $logistics['dates'][] = $occurrence->format('Y-m-d');
          }
        }
        break;

      default:
        // If the $entity isn't a node, it's BulkRoomReservation.
        $timezone = new \DateTimeZone('UTC');
        $logistics['times'] = [
          'start' => \DateTime::createFromFormat('Y-m-d\TH:i:s', $entity->field_date_time->value, $timezone)->format('H:i:s'),
          'end' => \DateTime::createFromFormat('Y-m-d\TH:i:s', $entity->field_date_time->end_value, $timezone)->format('H:i:s'),
        ];
        foreach ($entity->field_date_time->occurrences as $occurrence) {
          $logistics['dates'][] = $occurrence->getStart()->format('Y-m-d');
        }

    }

    return $logistics;
  }

  /**
   * {@inheritDoc}
   */
  private function getLogisticsFromFormState(FormStateInterface $form_state) {
    $logistics = [
      'dates' => [],
      'times' => [],
    ];
    $input = $form_state->getUserInput();
    $values = $form_state->getValues();

    // Begin with assumption that there are no $values yet; override if so.
    $dateField = $input['field_date_time'][0];
    if (!empty($values)) {
      $timezone = new \DateTimeZone('America/New_York');
      $dateField = $values['field_date_time'][0];
      // Massage this array: ajax caused field validation, so $values has
      // DateTime elements for 'start' and 'end', not arrays.
      $allowed_keys = [
        'start',
        'end',
        'value',
        'end_value',
      ];
      $keys = array_intersect(array_keys($dateField), $allowed_keys);
      $dateField['start'] = [
        'date' => $dateField[$keys[0]]->setTimezone($timezone)->format('Y-m-d'),
        'time' => $dateField[$keys[0]]->setTimezone($timezone)->format('H:i'),
      ];
      $dateField['end'] = [
        'date' => $dateField[$keys[1]]->setTimezone($timezone)->format('Y-m-d'),
        'time' => $dateField[$keys[1]]->setTimezone($timezone)->format('H:i'),
      ];
    }

    // Calculate start and end times.
    $timezone = new \DateTimeZone('America/New_York');

    // Massage the $dateField array: ajax caused field validation, so $values
    // has DateTime elements for 'start' and 'end', not arrays.
    $allowed_keys = [
      'start',
      'end',
      'value',
      'end_value',
    ];
    $tempDateField = [];
    foreach ($dateField as $key => $value) {
      if (!in_array($key, $allowed_keys)) {
        continue;
      }
      $tempDateField[$key] = $value;
    }
    // Recurrence mode can either be in the $dateField (in the case of
    // BulkRoomReservations) or in $values['event_recurrence'].
    $entity = $form_state->getFormObject()->getEntity();
    $iefData = [];
    if ($entity->getEntityTypeId() == 'node') {
      // Our $dateField doesn't contain recurrence info; add it from IEF.
      if (array_key_exists('event_recurrence', $values)) {
        foreach (current($values['event_recurrence'])['inline_entity_form']['field_event_rrule'][0] as $key => $value) {
          if (in_array($key, $allowed_keys)) {
            continue;
          }
          $iefData[$key] = $value;
        }
      }
    }

    $dateField = !empty($iefData) ? $tempDateField + $iefData : $dateField;
    $mode = (array_key_exists('mode', $dateField)) ? $dateField['mode'] : NULL;
    $keys = array_keys($dateField);

    switch ($entity->getEntityTypeId()) {
      case 'bulk_room_reservation':
        $logistics['times']['start'] = new \DateTime('@' . strtotime($dateField['start']['time']));
        $logistics['times']['start'] = $logistics['times']['start']->format('H:i:s');

        $logistics['times']['end'] = new \DateTime('@' . strtotime($dateField['end']['time']));
        $logistics['times']['end'] = $logistics['times']['end']->format('H:i:s');
        break;

      default:
        if (is_array(current($dateField))) {
          $logistics['times']['start'] = new \DateTime('@' . strtotime(current($dateField)['time']));
          $logistics['times']['start'] = $logistics['times']['start']->format('H:i:s');

          $logistics['times']['end'] = new \DateTime('@' . strtotime(next($dateField)['time']));
          $logistics['times']['end'] = $logistics['times']['end']->format('H:i:s');
          break;
        }

        $logistics['times']['start'] = current($dateField)->setTimezone(new \DateTimeZone('UTC'))->format('H:i:s');
        $logistics['times']['end'] = next($dateField)->setTimezone(new \DateTimeZone('UTC'))->format('H:i:s');
    }

    if (!array_key_exists('mode', $dateField)) {
      // This is not a recurring event / room reservation.
      switch (is_array(reset($dateField))) {
        case TRUE;
          $logistics['dates'][] = $dateField[$keys[0]]['date'];
          break;

        default:
          $logistics['dates'][] = current($dateField)->format('Y-m-d');
      }

      return $logistics;
    }

    switch ($mode) {
      case 'once':
        $logistics['dates'][] = $dateField['start']['date'];
        break;

      // @todo add logic for the other modes.
      case 'multiday':
        $daysToCheck = $dateField['daily_count'];
        for ($day = 0; $day <= $daysToCheck; $day++) {
          $increment = (string) $day . ' days';
          $logistics['dates'][] = date('Y-m-d', strtotime($dateField['start']['date'] . $increment));
        }
        break;

      case 'weekly':
        $daysToCheck = $this->getDaysToCheck($dateField);

        $days = $dateField['weekdays'];
        for ($day = 0; $day <= $daysToCheck; $day++) {
          $increment = (string) ($day * 7) . ' days';
          $dateToCheck = date('Y-m-d', strtotime($dateField['start']['date'] . $increment));
          $dayOfWeek = $this->getDayOfWeek($dateToCheck);
          if (in_array($dayOfWeek, $days, TRUE)) {
            $logistics['dates'][] = $dateToCheck;
          }
        }
        break;

      case 'fortnightly':
        $daysToCheck = $this->getDaysToCheck($dateField);
        $days = array_filter($dateField['weekdays']);

        // To determine 'every other week', determine which week we're in.
        for ($day = 0; $day <= $daysToCheck; $day++) {
          $dayInFortnight = fmod($day, 14);
          if ($dayInFortnight > 6) {
            // This day is in the off week.
            continue;
          }
          $increment = (string) $day . ' days';
          $dateToCheck = date('Y-m-d', strtotime($dateField['start']['date'] . $increment));
          $dayOfWeek = $this->getDayOfWeek($dateToCheck);
          if (in_array($dayOfWeek, $days)) {
            $logistics['dates'][] = $dateToCheck;
          }
        }
        break;

      case 'monthly':
        $daysToCheck = $this->getDaysToCheck($dateField);
        $days = $dateField['weekdays'];
        for ($day = 0; $day <= $daysToCheck; $day++) {
          $increment = (string) $day . ' days';
          $dateToCheck = date('Y-m-d', strtotime($dateField['start']['date'] . $increment));
          $dayOfWeek = $this->getDayOfWeek($dateToCheck);
          if (in_array($dayOfWeek, $days)) {
            // Include if this day of the week matches any of the ordinals.
            $startOfMonth = $this->getStartOfMonth($dateField);
            $weekOfMonth = $this->weekOfMonth(strtotime($dateField['start']['date'] . $increment));
            if (in_array($weekOfMonth, $dateField['ordinals'])) {
              $logistics['dates'][] = $dateToCheck;
            }
          }
        }

    }

    return $logistics;
  }

}
