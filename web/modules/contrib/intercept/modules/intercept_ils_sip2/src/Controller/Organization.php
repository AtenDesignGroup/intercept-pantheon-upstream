<?php

namespace Drupal\intercept_ils_sip2\Controller;

use Drupal\Core\Entity\EntityBase;
use Drupal\node\NodeInterface;

/**
 * Defines functions specific to the library's organizations/branches/locations.
 */
class Organization extends EntityBase {

  /**
   * @return array
   */
  public function getAll($type = 'all') {
    $locations = json_decode($this->keyRepository->getKey('intercept_ils_sip2_locations')->getKeyValue(), TRUE);
    if (is_array($locatons)) {
      return $locations;
    }
    return [];
  }

  public function getById($id) {
    foreach ($this->getAll() as $name => $location_id) {
      if ($location_id == $id) {
        return $name;
      }
    }
    return NULL;
  }
  
  // Get the node title and see if it matches to one of the locations.
  public function getByNode(NodeInterface $node) {
    $title = $node->getTitle();
    foreach ($this->getAll() as $name => $location_id) {
      if ($name == $title) {
        return $location_id;
      }
    }
    return FALSE;
  }
}