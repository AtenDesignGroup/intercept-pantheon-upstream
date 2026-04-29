<?php

namespace Drupal\Tests\existing_values_autocomplete_widget;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;

/**
 * Test trait that creates a content type, field and autocomplete test data.
 */
trait TestContentTrait {

  /**
   * Creates a content type, field and autocomplete test data.
   *
   * @return \Drupal\node\NodeInterface
   *   A generated article content to check against with the controller.
   */
  protected function createTestContent(): NodeInterface {
    $this->createContentType(['type' => 'article'])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_text',
      'entity_type' => 'node',
      'type' => 'text',
    ])->save();
    FieldConfig::create([
      'label' => 'Text field',
      'field_name' => 'field_text',
      'entity_type' => 'node',
      'bundle' => 'article',
      'settings' => [],
    ])->save();
    $displayRepository = \Drupal::service('entity_display.repository');
    $displayRepository->getFormDisplay('node', 'article')
      ->setComponent('field_text', [
        'type' => 'existing_autocomplete_field_widget',
      ])->save();
    $testArticle = $this->createNode([
      'type' => 'article',
      'id' => 1,
      'title' => 'Test article',
      'field_text' => 'abc',
    ]);
    $testArticle->save();
    $this->createNode([
      'type' => 'article',
      'id' => 2,
      'title' => 'Another article',
      'field_text' => 'another value',
    ])->save();
    return $testArticle;
  }

}
