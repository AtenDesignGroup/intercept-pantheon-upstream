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
   * Creates an article content type and an autocomplete text field on the type.
   */
  protected function createArticleTypeWithField(): void {
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
  }

  /**
   * Creates an article node with the given value for the autocomplete field.
   *
   * @param string $textFieldValue
   *   The autocomplete field value.
   *
   * @return \Drupal\node\NodeInterface
   *   The created article node.
   */
  protected function createArticle(string $textFieldValue): NodeInterface {
    $article = $this->createNode([
      'type' => 'article',
      'title' => 'Test article',
      'field_text' => $textFieldValue,
    ]);
    $article->save();
    return $article;
  }

}
