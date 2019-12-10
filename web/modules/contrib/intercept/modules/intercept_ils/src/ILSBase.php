<?php

namespace Drupal\intercept_ils;

use Drupal\Component\Plugin\PluginBase;

/**
 * Defines a base class for Intercept ILS plugins.
 */
class ILSBase extends PluginBase implements ILSInterface {

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * Gets the ILS client object.
   *
   * Should return a $client object with required methods including:
   * $client->organization->getAll()
   * $client->organization->getById($id)
   * $client->organization->getByNode($entity)
   * $client->patron->authenticate($username, $password)
   * $client->patron->barcode()
   * $client->patron->basicData()
   * $client->patron->get($barcode)
   * $client->patron->getByUser($user)
   * $client->patron->getUserByBarcode($barcode)
   * $client->patron->searchAnd($query)
   * $client->patron->searchBasic($parameters)
   * $client->patron->validate($username)
   */
  public function getClient() {
    return new stdClass();
  }

}
