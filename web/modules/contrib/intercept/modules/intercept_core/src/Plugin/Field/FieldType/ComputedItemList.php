<?php

namespace Drupal\intercept_core\Plugin\Field\FieldType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Field list_class handler for "intercept_computed".
 */
abstract class ComputedItemList extends FieldItemList {

  use ComputedItemListTrait;

}
