<?php

namespace Drupal\flag\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Flash a message as an action link is updated.
 *
 * The client side code can be found in js/flag-action_link_flash.js.
 *
 * @ingroup flag
 */
class ActionLinkFlashCommand implements CommandInterface {

  public function __construct(
    protected string $selector,
    protected string $message,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'actionLinkFlash',
      'selector' => $this->selector,
      'message' => $this->message,
    ];
  }

}
