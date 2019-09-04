<?php

namespace Drupal\intercept_core\Field\Computed;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

class MethodItemList extends FieldItemList {

  use ComputedItemListTrait;

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
