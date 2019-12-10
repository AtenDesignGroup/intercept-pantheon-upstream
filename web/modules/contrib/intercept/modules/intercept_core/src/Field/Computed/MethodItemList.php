<?php

namespace Drupal\intercept_core\Field\Computed;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Represents a list of method items.
 */
class MethodItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Computes the values for an item list.
   */
  protected function computeValue() {
    if (!$method = $this->getSetting('method')) {
      return FALSE;
    }
    if (!method_exists($this->getEntity(), $method)) {
      return FALSE;
    }
    $this->setValue($this->getEntity()->{$method}());
  }

}
