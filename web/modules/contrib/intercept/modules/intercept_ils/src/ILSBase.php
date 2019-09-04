<?php
/**
 * @file
 * Provides Drupal\intercept_ils\ILSBase.
 */

namespace Drupal\intercept_ils;

use Drupal\Component\Plugin\PluginBase;

class ILSBase extends PluginBase implements ILSInterface {

  public function getId() {
    return $this->pluginDefinition['id'];
  }

  public function getName() {
    return $this->pluginDefinition['name'];
  }

  public function getClient() {
    // Should return a $client object with required methods including:
    // $client->organization->getAll()
    // $client->organization->getById($id)
    // $client->organization->getByNode($entity)
    // $client->patron->authenticate($username, $password)
    // $client->patron->barcode()
    // $client->patron->basicData()
    // $client->patron->get($barcode)
    // $client->patron->getByUser($user)
    // $client->patron->getUserByBarcode($barcode)
    // $client->patron->searchAnd($query)
    // $client->patron->searchBasic($parameters)
    // $client->patron->validate($username)
    return new stdClass();
  }
}
