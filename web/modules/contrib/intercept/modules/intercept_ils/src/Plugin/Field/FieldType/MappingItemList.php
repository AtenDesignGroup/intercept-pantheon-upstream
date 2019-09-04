<?php

namespace Drupal\intercept_ils\Plugin\Field\FieldType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Represents a configurable entity path field.
 */
class MappingItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $value = [
      'data' => [],
    ];

    $entity = $this->getEntity();
    if (!$entity->isNew()) {
      $mapping = \Drupal::service('intercept_ils.mapping_manager')
        ->loadByEntity($entity);
      $value['data'] = $mapping ? $mapping->data() : [];
      $value['id'] = $mapping ? $mapping->id() : FALSE;
    }
    $this->list[0] = $this->createItem(0, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultAccess($operation = 'view', AccountInterface $account = NULL) {
    if ($operation == 'view') {
      return AccessResult::allowed();
    }
    return AccessResult::allowedIfHasPermissions($account, ['create url aliases', 'administer url aliases'], 'OR')->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Delete all aliases associated with this entity in the current language.
    $entity = $this->getEntity();
    $conditions = [
      'source' => '/' . $entity->toUrl()->getInternalPath(),
      'langcode' => $entity->language()->getId(),
    ];
    \Drupal::service('path.alias_storage')->delete($conditions);
  }

}
