<?php

namespace Drupal\office_hours\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\office_hours\Plugin\Field\FieldFormatter\OfficeHoursFormatterBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Helper class for Office Hours Views FieldsFilter.
 */
class FieldBase extends FieldPluginBase {

  /**
   * Implements hook_field_views_data().
   *
   * Note: When using pager on a view, less results might be displayed.
   */
  public static function viewsFieldData(FieldStorageConfigInterface $field_storage, array $data) {
    // The following was called earlier:
    // $data = views_field_default_views_data($field_storage);
    $field_name = $field_storage->getName();

    $columns = [
      'season' => 'office_hours_season',
      'timeslot' => 'office_hours_timeslot',
    ];

    foreach ($data as $table_name => $table_data) {

      foreach ($columns as $column => $plugin_id) {
        $data[$table_name][$field_name . '_' . $column]['field'] = [
          'id' => $plugin_id,
          'field_name' => $field_name,
          'property' => $column,
          'table' => $table_name,
        ];
      }

      if (isset($data[$table_name][$field_name])) {
        $field_label = $data[$table_name][$field_name]['title short'];

        // Extend 'timeslot'.
        // @todo Why is 'timeslot' not filled already, when set in properties?
        $column = 'timeslot';
        $label = $field_label . ' - Time slot';
        $real_field = $field_name;
        $title = t('@label (@name:@column)',
          ['@label' => $label, '@name' => $field_name, '@column' => $column]);
        $title_short = t('@label:@column',
          ['@label' => $label, '@column' => $column]);

        // @todo Do not take over all field attributes.
        $field_data = &$data[$table_name][$field_name . "_$column"];
        // Use ?? to avoid TypeError: Unsupported operand types: array + null.
        // This may have side effects, since data should exist. @see #3421574.
        $field_data += $data[$table_name][$field_name] ?? [];
        $field_data['field'] += $data[$table_name][$real_field]['field'] ?? [];
        $field_data['field']['real field'] = $data[$table_name][$real_field]['field']['field_name'];
        // Set a type, to get the 'office_hours' formatter field in Views Field.
        $field_data['field']['type'] = 'office_hours';
        $field_data['title'] = $title;
        $field_data['title short'] = $title_short;
        unset($field_data['argument']);
        unset($field_data['filter']);
        unset($field_data['sort']);

        // Extend 'season'.
        $column = 'season';
        $label = $field_label . ' - Season';
        $real_field = 'delta';
        $title = t('@label (@name:@column)',
          ['@label' => $label, '@name' => $field_name, '@column' => $column]
        );
        $title_short = t('@label:@column',
          ['@label' => $label, '@column' => $column]);

        // @todo Do not take over all field attributes.
        $field_data = &$data[$table_name][$field_name . "_$column"];
        // Use ?? to avoid TypeError: Unsupported operand types: array + null.
        // This may have side effects, since data should exist. @see #3421574.
        $field_data += $data[$table_name][$field_name] ?? [];
        $field_data['field'] += $data[$table_name][$real_field]['field'] ?? [];
        $field_data['field']['real field'] = $real_field;
        $field_data['field']['property'] = $real_field;
        $field_data['title'] = $title;
        $field_data['title short'] = $title_short;
        unset($field_data['argument']);
        unset($field_data['filter']);
        unset($field_data['sort']);

      }
    }

    return $data;
  }

  /**
   * Reusable optionsForm, also used to display messages during view render.
   */
  public function addOptionsFormWarning(&$form, FormStateInterface $form_state, $field_label) {
    $field_name = $this->configuration['field_name'];
    $previous = $this->getPreviousFieldLabels();
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
      '#markup' => '<p>' . $warning . '</p>',
    ];
    $instructions = $this->t(
      'The %office_hours field will be used to contain the formatting settings
        of each %timeslot field. You may add 1 to 7 %timeslot fields,
        one for each weekday.', $parameters);
    $form['additional_fields_comment'][] = [
      '#markup' => '<p>' . $instructions . '</p>',
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
      '#markup' => '<p>' . $instructions . '</p>',
    ];

    if (!array_key_exists($field_name, $previous)) {
      \Drupal::messenger()->addWarning($warning);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $item = NULL;

    $table = $this->configuration['table'];
    $field_name = $this->configuration['field_name'];

    $entity = $this->getEntity($values);
    // Entities with no / empty office_hours will have delta = NULL.
    $delta = $values->{$table . '_delta'} ?? NULL;
    // So, no need to check for $entity->hasField($field_name).
    if (!is_null($delta)) {
      $items = $entity->get($field_name);
      $item = $items->get($delta);
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
