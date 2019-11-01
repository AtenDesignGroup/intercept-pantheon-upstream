<?php

namespace Drupal\tally\Plugin\views\field;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers

 * @ViewsField("tally_field")
 */
class AggregateField extends EntityField {

  /**
   * Provide options for multiple value fields.
   */
  public function multiple_options_form(&$form, FormStateInterface $form_state) {
    $form['multi_type']['#options']['count'] = $this->t('Simple count');
    $form['multi_type']['#options']['individual'] = $this->t('Individual count per item');
    return parent::multiple_options_form($form, $form_state);
  }

  /**
   * Render all items in this field together.
   *
   * When using advanced render, each possible item in the list is rendered
   * individually. Then the items are all pasted together.
   */
  public function renderItems($items) {
    if (!empty($items) && $this->options['multi_type'] == 'count') {
      $items = $this->prepareItemsByDelta($items);
      $build = [
        '#markup' => $this->countItems($items),
      ];
      return $this->renderer->render($build);
    }
    return parent::renderItems($items);
  }

  /**
   * Loop through items and return sum.
   *
   * @param array $items
   *
   * @return int
   */
  protected function countItems(array $items) {
    /** @var \Drupal\views\Render\ViewsRenderPipelineMarkup[] $items */
    return array_reduce($items, function($carry, $item) {
      $int = (int) $item->__toString(); 
      $carry += $int;
      return $carry;
    }, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    $entity = $this->getEntity($values);
    $type = $this->getEntityType();
    // Retrieve the translated object.
    $translated_entity = $this->getEntityFieldRenderer()->getEntityTranslation($entity, $values);

    // Some bundles might not have a specific field, in which case the entity
    // (potentially a fake one) doesn't have it either.
    /** @var \Drupal\Core\Field\FieldItemListInterface $field_item_list */
    $field_item_list = isset($translated_entity->{$this->definition['field_name']}) ? $translated_entity->{$this->definition['field_name']} : NULL;

    if (!isset($field_item_list)) {
      // There isn't anything we can do without a valid field.
      return NULL;
    }

    $field_item_definition = $field_item_list->getFieldDefinition();

    $values = [];
    if ($type == 'node') {
      if ($this->options['delta_limit'] == 1) { // Just show 1 value as specified.
        $offset = $this->options['delta_offset'];
        foreach ($field_item_list as $index => $field_item) {
          if ($index == $offset) {
            $values = $count = $field_item->count ? $field_item->count : 0;
          }
        }
      }
      else { // Show all of the values in one column together.
        foreach ($field_item_list as $field_item) {
          $term = Term::load($field_item->target_id);
          $term_name = $term->getName();
          $count = $field_item->count ? $field_item->count : 0;
          $values[] = $term_name . ': ' . $count;
        }
        $values = implode('; ', $values);
      }
    }
    elseif ($type = 'event_attendance') {
      foreach ($field_item_list as $field_item) {
        /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
        if (empty($field_item->count)) {
          continue;
        }
        $values[] = $field_item->count;
      }
      $values = array_sum($values);
    }
    return $values;
  }

  function allowAdvancedRender() {
    return FALSE;
  }

}

