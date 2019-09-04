<?php

/**
 * @file
 * Provides Drupal\intercept_ils\ILSInterface
 */

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
   */
  public function getId();

  /**
   * Return the name of the Intercept ILS.
   *
   * @return string
   */
  public function getName();

  /**
   * Return a client object for interacting with the ILS.
   *
   * @return object
   */
  public function getClient();  
  
}
