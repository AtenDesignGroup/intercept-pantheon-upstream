<?php

namespace Drupal\flag\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event for when a flagging is deleted.
 */
class UnflaggingEvent extends Event {

  public function __construct(
    protected array $flaggings,
  ) {
  }

  /**
   * Returns the flagging associated with the Event.
   *
   * @return \Drupal\flag\FlaggingInterface[]
   *   The flaggings.
   */
  public function getFlaggings() {
    return $this->flaggings;
  }

}
