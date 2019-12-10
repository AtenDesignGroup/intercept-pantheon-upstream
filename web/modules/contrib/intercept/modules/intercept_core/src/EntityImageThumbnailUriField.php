<?php

namespace Drupal\intercept_core;

use Drupal\intercept_core\Plugin\Field\FieldType\ComputedItemList;

/**
 * Defines the image thumbnail uri entity field type.
 */
class EntityImageThumbnailUriField extends ComputedItemList {

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    if ($this->getEntity()->isNew()) {
      return [
        'thumbnail' => NULL,
      ];
    }
    $this->setValue([
      'thumbnail' => $this->getThumbnailUri(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);
    return $this;
  }

  /**
   * Calculates the value of the field and sets it.
   */
  protected function getThumbnailUri() {
    $entity = $this->getEntity();
    if (!$entity->isNew() && $image_file = intercept_core_get_primary_image_file($this->getEntity())) {
      $thumbnail_style = \Drupal::service('entity_type.manager')->getStorage('image_style')->load('4to3_740x556');
      $image_uri = $image_file->getFileUri();
      $derivative_uri = $thumbnail_style->buildUri($image_uri);
      // Create derivative if necessary.
      if (!file_exists($derivative_uri)) {
        $thumbnail_style->createDerivative($image_uri, $derivative_uri);
      }
      return file_create_url($derivative_uri);
    }
  }

}
