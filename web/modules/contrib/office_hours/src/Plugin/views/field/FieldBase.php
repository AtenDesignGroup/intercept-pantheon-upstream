<?php

namespace Drupal\office_hours\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\Plugin\Field\FieldFormatter\OfficeHoursFormatterBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Helper class for Office Hours Views FieldsFilter.
 */
class FieldBase extends FieldPluginBase {

  /**
   * Reusable optionsForm, also used to display messages during view render.
   */
  public function addOptionsFormWarning(&$form, FormStateInterface $form_state, $field_label) {
    $field_name = $this->configuration['field_name'];
    $previous = $this->getPreviousFieldLabels();

    if (array_key_exists($field_name, $previous)) {
      // No message needed.
      return;
    }

    $parameters = [
      '%field' => $field_label,
      '%office_hours' => $previous[$field_name] ?? 'Office hours',
      '%timeslot' => 'Time slot',
      '%season' => 'Season',
    ];

    $warning = $this->t(
      'You must add some additional fields (%office_hours, %timeslot, %season)
        to this display before using the field %field. These fields may be
        marked as <em>Exclude from display</em> if you prefer.
        Note that due to rendering order,
        you cannot use fields that come after this field;
        if you need a field not listed here, rearrange your fields.', $parameters);
    $form['additional_fields_comment'][] = [
      '#markup' => "<p>$warning</p>",
    ];
    $instructions = $this->t(
      'The %office_hours field will be used to contain the formatting settings
        of each %timeslot field. You may add 1 to 7 %timeslot fields,
        one for each weekday.', $parameters);
    $form['additional_fields_comment'][] = [
      '#markup' => "<p>$instructions</p>",
    ];
    $instructions = $this->t(
      'The %season field is optional and is used as a <em>table column</em>
        to also display seasonal <em>%office_hours</em> and
        <em>Exception dates</em> to the table rows.
        Season labels cannot be influenced.
        Exceptions can be removed and formatted,
        also using the <em>%office_hours</em> field settings,
        section <em>Exception days</em>.',
      $parameters);
    $form['additional_fields_comment'][] = [
      '#markup' => "<p>$instructions</p>",
    ];

    \Drupal::messenger()->addWarning($warning);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $item = NULL;
    $field_name = $field ?? $this->configuration['field_name'];

    if ($values->_entity->hasField($field_name)) {
      // Entities with no / empty office_hours will have delta = NULL.
      // $delta = $values->{"{$table}_delta"} ?? NULL;
      $delta = $values->{"index"} ?? NULL;
      // So, no need to check for $entity->hasField($field_name).
      if (!is_null($delta)) {
        $entity = $this->getEntity($values);
        $items = $entity->get($field_name);
        $item = $items->get($delta);
      }
    }
    return $item;
  }

  /**
   * Returns the array of settings, including defaults for missing settings.
   *
   * @todo Use officeHoursFormatterBase::getSettings() instead.
   *
   * @parameter string $field_name
   *   The field name.
   *
   * @return array
   *   The array of settings.
   */
  protected function getFieldSettings(string $field_name) : array {
    $default_settings = OfficeHoursFormatterBase::defaultSettings();

    $settings = $this->view->field[$field_name]->options['settings'];
    $settings += $default_settings;
    $settings['exceptions'] += $default_settings['exceptions'];
    $settings['schema'] += $default_settings['schema'];
    return $settings;
  }

}
