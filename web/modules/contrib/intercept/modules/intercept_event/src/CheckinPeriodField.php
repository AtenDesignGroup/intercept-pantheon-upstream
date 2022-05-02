<?php

namespace Drupal\intercept_event;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\intercept_core\Plugin\Field\FieldType\ComputedItemList;

/**
 * Provides a computed event registration field.
 */
class CheckinPeriodField extends ComputedItemList implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    if ($this->getEntity()->isNew()) {
      return [
        'start' => NULL,
        'end' => NULL,
        'status' => NULL,
        'checkin_url' => NULL,
      ];
    }

    $checkinDate = $this->checkinDate();
    return $this->getEntity()->addCacheableDependency($this->setValue([
      'start' => $checkinDate->start->format('c'),
      'end' => $checkinDate->end->format('c'),
      'status' => $this->getStatus(),
      'checkin_url' => $this->getCheckinUrl(),
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

    $config = \Drupal::config('intercept_event.checkin')->get();

    // If check-in is disabled, return closed.
    if (!$config['enable']) {
      return 'closed';
    }

    // If there is no date set, skip further checks.
    if (!$this->eventDate()) {
      // TODO: This might need to reflect an error.
      return 'closed';
    }

    // Checkin date has ended.
    if ($this->checkinEnded()) {
      return 'expired';
    }

    // Checkin date has not started.
    if ($this->checkinPending()) {
      return 'open_pending';
    }

    return $default_status;
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
   * Get event checkin period date value and end_value array.
   *
   * @return bool|object
   *   An object with the start and end date.
   */
  protected function checkinDate() {
    $date = $this->eventDate();
    if (!$date) {
      return FALSE;
    }

    // @todo: Use dependency injection once https://www.drupal.org/node/2053415 lands.
    $config = \Drupal::config('intercept_event.checkin')->get();
    $durationFieldService = \Drupal::service('duration_field.service');

    $checkinStart = $date->start->getPhpDateTime();
    $checkinEnd = $date->end->getPhpDateTime();

    return (object) [
      'start' => $checkinStart->sub($durationFieldService->getDateIntervalFromDurationString($config['checkin_start'])),
      'end' => $checkinEnd->add($durationFieldService->getDateIntervalFromDurationString($config['checkin_end'])),
    ];
  }

  /**
   * Checks to see if the checkin period this event was in the past.
   *
   * @return bool
   *   Whether the event's check-in period start date is after the current date.
   */
  protected function checkinEnded() {
    if ($checkinDate = $this->checkinDate()) {
      $now = \Drupal::service('datetime.time')->getRequestTime();
      return $checkinDate->end->getTimeStamp() < $now;
    }
    return FALSE;
  }

  /**
   * Checks to see if the checkin period for this event is in the future.
   *
   * @return bool
   *   Whether the event's check-in period end date is before than current date.
   */
  protected function checkinPending() {
    if ($checkinDate = $this->checkinDate()) {
      $now = \Drupal::service('datetime.time')->getRequestTime();
      return $checkinDate->start->getTimeStamp() > $now;
    }
    return FALSE;
  }

  /**
   * Current date is between registration start and end dates.
   *
   * @return bool
   *   Whether the current date is between registration start and end dates.
   */
  protected function checkinInProcess() {
    return !$this->checkinPending() && !$this->checkinEnded();
  }

  /**
   * {@inheritdoc}
   */
  protected function getCheckinUrl() {
    return Url::fromRoute('entity.node.checkin', ['node' => $this->getEntity()->id()])->toString();
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
    return ['node:' . $this->getEntity()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
