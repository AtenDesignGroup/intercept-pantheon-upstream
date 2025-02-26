<?php

namespace Drupal\office_hours\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursStatus;
use Drupal\views\ResultRow;

/**
 * Computed field to display the open/closed status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("office_hours_status")
 *
 * @see https://www.drupal.org/docs/drupal-apis/entity-api/dynamicvirtual-field-values-using-computed-field-property-classes
 */
class Status extends FieldBase {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::addOptionsFormWarning($form, $form_state, $this->options['label']);
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Called to add the field to a query.
   */
  public function query() {
    // Do not add the computed subfield to the query.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $values->_entity;
    $field_name = $this->configuration['field_name'];
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
    /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
    $result = '';
    if ($entity->hasField($field_name)) {
      $items = $entity->get($field_name);

      // Get the value.
      $property_name = $this->definition['property'];
      $status = $items->{$property_name} ?? OfficeHoursStatus::NEVER;

      // Get the formatter settings.
      // Re-use the formatter settings of the main 'office_hours' field.
      $formatter_settings = $this->getFieldSettings($field_name) ?? [];
      $options = OfficeHoursStatus::getOptions(NULL, $formatter_settings);

      // Format the value.
      $result = $options[$status];
    }
    return $result;
  }

}
