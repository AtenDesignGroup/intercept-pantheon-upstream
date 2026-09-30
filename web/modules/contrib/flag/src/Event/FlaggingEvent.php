<?php

namespace Drupal\flag\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\flag\FlaggingInterface;

/**
 * Event for when a flagging is created.
 */
class FlaggingEvent extends Event {

  public function __construct(
    protected FlaggingInterface $flagging,
  ) {
  }

  /**
   * Returns the flagging associated with the Event.
   *
   * @return \Drupal\flag\FlaggingInterface
   *   The flagging.
   */
  public function getFlagging() {
    return $this->flagging;
  }

}
