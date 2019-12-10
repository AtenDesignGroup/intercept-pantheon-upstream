<?php

namespace Drupal\intercept_event\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\NumericFilter;

/**
 * Add in an entity reference select list for a numeric filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("numeric_reference")
 */
class NumericReferenceFilter extends NumericFilter {

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    $which = 'all';

    if ($form_state->get('exposed')) {

      if (empty($this->options['expose']['use_operator']) || empty($this->options['expose']['operator_id'])) {
        // Exposed and locked.
        $which = in_array($this->operator, $this->operatorValues(2)) ? 'minmax' : 'value';
      }
    }

    if ($which == 'value') {
      $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'event');
      $settings = $fields['field_location']->getSettings();
      $entity_type = $settings['target_type'];
      $bundles = $settings['handler_settings']['target_bundles'];
      $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
      $query = $storage->getQuery();
      $ids = $query->condition('type', $bundles, 'IN')
        ->sort('title')
        ->condition('status', 1)
        ->execute();
      $options = array_map(function ($entity) {
        return $entity->getTitle();
      }, $storage->loadMultiple($ids));

      $form['value']['#type'] = 'select';
      $form['value']['#options'] = $options;
      $form['value']['#empty_option'] = $this->t('Any');
      $form['value']['#empty_value'] = '';
      $form['value']['#after_build'][] = [$this, 'afterBuild'];
      unset($form['value']['#size']);
    }

  }

  /**
   * Adds an afterBuild handler.
   *
   * @param array $element
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    unset($element['#options']['All']);
    return $element;
  }

}
