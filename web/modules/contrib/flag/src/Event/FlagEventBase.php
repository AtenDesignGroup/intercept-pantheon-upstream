<?php

namespace Drupal\flag\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\flag\FlagInterface;

/**
 * Base Event from which other flag event are defined.
 */
abstract class FlagEventBase extends Event {

  public function __construct(
    protected FlagInterface $flag,
  ) {
  }

  /**
   * Get the flag entity related to the event.
   *
   * @return \Drupal\flag\FlagInterface
   *   The flag related to the event.
   */
  public function getFlag() {
    return $this->flag;
  }

}
