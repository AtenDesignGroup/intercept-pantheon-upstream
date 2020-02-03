<?php

namespace Drupal\intercept_room_reservation;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\intercept_core\Plugin\Field\FieldType\ComputedItemList;

/**
 * Provides a computed event registration field.
 */
class RoomReservationValidationField extends ComputedItemList implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $this->getEntity()->addCacheableDependency($this->setValue([
      'warnings' => $this->getWarnings(),
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
   * Get total related event_registration entities.
   *
   * @return int
   *   The total related event_registration entities
   */
  protected function getWarnings() {
    $warnings = [];
    $reservation = $this->getEntity();
    $violations = $reservation->validationWarnings();

    foreach ($violations->getEntityViolations() as $violation) {
      $warnings[] = $violation->getMessage();
    }
    return $warnings;
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
