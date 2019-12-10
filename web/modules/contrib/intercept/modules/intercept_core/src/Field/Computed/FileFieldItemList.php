<?php

namespace Drupal\intercept_core\Field\Computed;

use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList as CoreFileFieldItemList;

/**
 * Extends the core FileFieldItemList.
 */
class FileFieldItemList extends CoreFileFieldItemList {

  use ComputedItemListTrait;
  use ItemTraverseTrait;

}
