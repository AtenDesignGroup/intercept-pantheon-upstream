<?php

namespace Drupal\intercept_ils;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Intercept ILS plugins.
 */
interface ILSInterface extends PluginInspectionInterface {

  /**
   * Return the id of the Intercept ILS.
   *
   * @return string
   *   The Intercept ILS ID.
   */
  public function getId();

  /**
   * Return the name of the Intercept ILS.
   *
   * @return string
   *   The Intercept ILS name.
   */
  public function getName();

  /**
   * Return a client object for interacting with the ILS.
   *
   * @return object
   *   The client object.
   */
  public function getClient();

}
