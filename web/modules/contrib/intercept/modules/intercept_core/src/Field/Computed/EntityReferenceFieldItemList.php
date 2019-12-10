<?php

namespace Drupal\intercept_core\Field\Computed;

use Drupal\Core\Field\EntityReferenceFieldItemList as CoreEntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Extends the core EntityReferenceFieldItemList.
 */
class EntityReferenceFieldItemList extends CoreEntityReferenceFieldItemList {

  use ComputedItemListTrait;
  use ItemTraverseTrait;

}
