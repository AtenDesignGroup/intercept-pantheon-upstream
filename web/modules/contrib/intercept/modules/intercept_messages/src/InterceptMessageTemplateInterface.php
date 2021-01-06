<?php

namespace Drupal\intercept_messages;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for Intercept message template plugins.
 */
interface InterceptMessageTemplateInterface extends PluginInspectionInterface, PluginFormInterface, ConfigurableInterface {

  /**
   * Plugin instance summary.
   *
   * Returns a render array summarizing the configuration of the notification.
   *
   * @return array
   *   A render array.
   */
  public function getSummary();

  /**
   * Returns a label for this notification.
   *
   * @return string
   *   The Intercept Notification label.
   */
  public function label();

}
