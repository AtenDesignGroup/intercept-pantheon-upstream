<?php

namespace Drupal\intercept_event;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\intercept_core\Plugin\Field\FieldType\ComputedItemList;

/**
 * Provides a computed event registration field.
 */
class EventRegistrationField extends ComputedItemList implements CacheableDependencyInterface {

  /**
   * The event registration storage manager.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  private $registrationManager;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    if ($this->getEntity()->isNew()) {
      return [
        'total' => NULL,
        'total_waitlist' => NULL,
        'remaining_registration' => NULL,
        'remaining_waitlist' => NULL,
        'status' => NULL,
      ];
    }
    $this->getEntity()->addCacheableDependency($this->setValue([
      'total' => $this->getTotal(),
      'total_waitlist' => $this->getTotalWaitlist(),
      'remaining_registration' => $this->getRemainingRegistrationCapacity(),
      'remaining_waitlist' => $this->getRemainingWaitlist(),
      'status' => $this->getStatus(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);
    return $this;
  }

  /**
   * Get current event status machine name.
   *
   * @return string
   *   The current event status machine name.
   */
  protected function getStatus() {
    $default_status = 'open';

    // If there is no date set, skip further checks.
    if (!$this->eventDate()) {
      // TODO: This might need to reflect an error.
      return $default_status;
    }

    // We work backwards now, starting with if it has ended.
    if ($this->eventEnded()) {
      return 'expired';
    }

    // Skip further dates if there is no registration date,
    // or if it's not required.
    if (!$this->mustRegister() || !$this->regDate()) {
      return $default_status;
    }

    // Registration date has ended.
    if ($this->regEnded()) {
      return 'closed';
    }

    // Has a capacity and it's filled.
    if ($this->regInProcess() && $this->capacityFull()) {
      // Has a waiting list and it's not full.
      if (!$this->waitlistFull()) {
        return 'waitlist';
      }
      return 'full';
    }

    // Registration date has not started.
    if ($this->regPending()) {
      return 'open_pending';
    }

    return $default_status;
  }

  /**
   * Get total related event_registration entities.
   *
   * @return int
   *   The total related event_registration entities
   */
  protected function getTotal() {
    $node = $this->getEntity();

    $ids = $this->getStorage()->getQuery()
      ->condition('field_event', $node->id(), '=')
      ->condition('status', 'active', '=')
      ->execute();

    $registrations = $this->getStorage()->loadMultiple($ids);
    $total = 0;
    foreach ($registrations as $registration) {
      $total += (int) $registration->total();
    }
    return $total;
  }

  /**
   * Event has waitlist field is enabled.
   *
   * @return bool
   *   Whether the waitlist field is enabled.
   */
  protected function hasWaitlist() {
    $field = $this->getEntity()->get('field_has_waitlist');
    return !empty($field->getString());
  }

  /**
   * Gets the waitlist maximum capacity.
   *
   * @return int
   *   Whether the waitlist field is enabled.
   */
  protected function waitlistCapacity() {
    return (int) $this->getEntity()->get('field_waitlist_max')->getString();
  }

  /**
   * Number of waitlisted registrations is more than the limit.
   *
   * @return bool
   *   Whether the number of waitlisted registrations is more than the limit.
   */
  protected function waitlistFull() {
    $has_waitlist = $this->hasWaitlist();
    $waitlist_max = $this->waitlistCapacity();

    // We treat an event with no waitlist as being full.
    if (!$has_waitlist) {
      return TRUE;
    }

    // We are treating 0 and NULL the same as no waitlist maximum.
    if (empty($waitlist_max)) {
      return FALSE;
    }
    return $waitlist_max <= $this->getTotalWaitlist();
  }

  /**
   * Gets the waitlist maximum capacity.
   *
   * @return int
   *   Whether the waitlist field is enabled.
   */
  protected function registrationCapacity() {
    return $this->getEntity()->get('field_capacity_max')->value;
  }

  /**
   * Number of registrations is more than the limit.
   *
   * @return bool
   *   Whether the number of registrations is more than the limit.
   */
  protected function capacityFull() {
    $capacity_max = $this->registrationCapacity();
    if (is_null($capacity_max)) {
      return FALSE;
    }
    return $capacity_max <= $this->getTotal();
  }

  /**
   * Get event date value and end_value array.
   *
   * @return bool|object
   *   An object with the start and end date.
   */
  protected function eventDate() {
    $date = $this->getEntity()->get('field_date_time');
    if (!$date->start_date || !$date->end_date) {
      return FALSE;
    }
    return (object) [
      'start' => $date->start_date,
      'end' => $date->end_date,
    ];
  }

  /**
   * Get event registration date value and end_value array.
   *
   * @return bool|object
   *   An object with the start and end date.
   */
  protected function regDate() {
    $date = $this->getEntity()->get('field_event_register_period');
    if (!$date->start_date || !$date->end_date) {
      return FALSE;
    }
    return (object) [
      'start' => $date->start_date,
      'end' => $date->end_date,
    ];
  }

  /**
   * Event end date is later than current date.
   *
   * @return bool|int
   *   Whether the event end date is later than current date.
   */
  protected function eventEnded() {
    if ($this->eventDate()) {
      $date = new DrupalDateTime();
      return $date->diff($this->eventDate()->end)->invert;
    }
    return FALSE;
  }

  /**
   * Current date is between registration start and end dates.
   *
   * @return bool
   *   Whether the current date is between registration start and end dates.
   */
  protected function regInProcess() {
    return !$this->regPending() && !$this->regEnded();
  }

  /**
   * Current date is after registration end date.
   *
   * @return bool|int
   *   Whether the current date is after registration end date.
   */
  protected function regEnded() {
    if ($this->regDate()) {
      $date = new DrupalDateTime();
      return $date->diff($this->regDate()->end)->invert;
    }
    return FALSE;
  }

  /**
   * Current date is before registration start date.
   *
   * @return bool
   *   Whether the current date is before registration start date.
   */
  protected function regPending() {
    if ($this->regDate()) {
      $date = new DrupalDateTime();
      return !$date->diff($this->regDate()->start)->invert;
    }
    return FALSE;
  }

  /**
   * Number of related waitlisted event registration entities.
   *
   * @return int
   *   The number of related waitlisted event registration entities.
   */
  protected function getTotalWaitlist() {
    $node = $this->getEntity();

    $ids = $this->getStorage()->getQuery()
      ->condition('field_event', $node->id(), '=')
      ->condition('status', 'waitlist', '=')
      ->execute();

    $registrations = $this->getStorage()->loadMultiple($ids);
    $waitlist = 0;
    foreach ($registrations as $registration) {
      $waitlist += (int) $registration->total();
    }
    return $waitlist;
  }

  /**
   * Number of remaining waitlisted event registration spots.
   *
   * @return int
   *   The number of remaining waitlisted event registration spots.
   */
  protected function getRemainingRegistrationCapacity() {
    $registered = $this->getTotal();
    $registration_capacity = $this->registrationCapacity();

    return $registration_capacity - $registered;
  }

  /**
   * Number of remaining waitlisted event registration spots.
   *
   * @return int
   *   The number of remaining waitlisted event registration spots.
   */
  protected function getRemainingWaitlist() {
    $waitlisted = $this->getTotalWaitlist();
    $waitlist_capacity = $this->waitlistCapacity();

    return $waitlist_capacity - $waitlisted;
  }

  /**
   * Field must register is enabled.
   *
   * @return bool
   *   Whether the field must register is enabled.
   */
  private function mustRegister() {
    return !empty($this->getEntity()->get('field_must_register')->value);
  }

  /**
   * Entity type manager helper function.
   *
   * @return \Drupal\node\NodeStorageInterface
   *   The Node storage manager.
   */
  private function getStorage() {
    if (!isset($this->registrationManager)) {
      $this->registrationManager = \Drupal::service('entity_type.manager')->getStorage('event_registration');
    }
    return $this->registrationManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // TODO: Possibly add the current registration tags in here, but it depends.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Do not cache if there is an invalid event date.
    if (!$this->eventDate()) {
      return 0;
    }
    $date = new DrupalDateTime();

    // First if they don't have to register and the event hasn't happened.
    if (!$this->mustRegister()) {
      return $this->eventEnded() ? Cache::PERMANENT : $this->eventDate()->end->format('U') - $date->format('U');
    }

    // Do not cache if registration is required but
    // for some reason there is no date.
    if (!$this->regDate()) {
      return 0;
    }

    // Amount of seconds until the registration opens.
    if ($this->regPending()) {
      return $this->regDate()->start->format('U') - $date->format('U');
    }
    // Amount of seconds until the registration closes.
    if ($this->regInProcess()) {
      return $this->regDate()->end->format('U') - $date->format('U');
    }

    if (!$this->eventEnded()) {
      return $this->eventDate()->end->format('U') - $date->format('U');
    }
    return Cache::PERMANENT;
  }

}
