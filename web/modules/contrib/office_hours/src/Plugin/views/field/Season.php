<?php

namespace Drupal\office_hours\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;

/**
 * Displays the season/exception date.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("office_hours_season")
 */
class Season extends FieldBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    return parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::addOptionsFormWarning($form, $form_state, $this->options['label']);
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
    $field_name = $this->configuration['field_name'];

    $entity = $values->_entity;
    $items = $entity->{$field_name};
    $index = $values->index;
    $item = is_null($items) ? NULL : $items->get($index);
    switch (TRUE) {
      case $item === NULL:
        $result = NULL;
        break;

      case $item->isExceptionDay():
        // Get the formatter settings of the main 'office_hours' field,
        // re-using the time slot formatter settings 7 times.
        $formatter_settings = $this->getFieldSettings($field_name);
        $result = $item->label($formatter_settings);
        break;

      // case $item->isSeasonHeader():
      // case $item->isSeasonDay():
      // case $item->isWeekDay():
      default:
        $season = $item->getSeason();
        $result = $season->label();
    }
    return $this->sanitizeValue($result);
  }

}
