<?php

namespace Drupal\intercept_core\Field\Computed;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList as CoreFileFieldItemList;

class FileFieldItemList extends CoreFileFieldItemList {

  use ComputedItemListTrait;
  use ItemTraverseTrait;

}
