<?php

namespace Drupal\intercept_ils\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an ILS item annotation object.
 *
 * Plugin Namespace: Plugin\intercept_ils\ils.
 *
 * @see \Drupal\intercept_ils\ILSManager
 * @see plugin_api
 *
 * @Annotation
 */
class ILS extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the ILS.
   *
   * @var string
   */
  public $name;

  /**
   * A client object for interactions with the ILS.
   *
   * @var object
   */
  public $client;

}
