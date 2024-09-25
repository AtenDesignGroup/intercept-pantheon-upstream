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
   * Called to add the field to a query.
   */
  public function query() {
    // Do add this field to the query.
    parent::query();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    return parent::getValue($values, $field);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $field_name = $this->configuration['field_name'];

    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $item = $this->getValue($values);

    switch (TRUE) {
      case is_null($item):
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
