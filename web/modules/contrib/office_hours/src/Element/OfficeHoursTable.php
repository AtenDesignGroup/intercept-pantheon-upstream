<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Table;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Provides a render element for a table.
 *
 * Usage:
 * @code
 * namespace Drupal\example\Form;
 *
 * use Drupal\Core\Form\FormBase;
 * use Drupal\Core\Form\FormStateInterface;
 *
 * final class ExampleForm extends FormBase {
 *
 *   public function getFormId(): string {
 *     return 'example_example';
 *   }
 *
 *   public function buildForm(array $form, FormStateInterface $form_state): array {
 *     $config = \Drupal::configFactory()->get('example.settings');
 *     $field_settings = OfficeHoursItem::defaultStorageSettings();
 *     $widget_settings = OfficeHoursWeekWidget::defaultSettings();
 *     $office_hours = $config->get('office_hours') ?? [];
 *
 *     $form['office_hours'] = [
 *       '#type' => 'office_hours_table',
 *       '#title' => $this->t('Office hours'),
 *       '#default_value' => $office_hours,
 *
 *       '#field_settings' => $field_settings,
 *       '#widget_settings' => $widget_settings,
 *       '#field_type' => $week_season_id = 0,
 *     ];
 *
 *     $form['actions'] = [
 *       '#type' => 'actions',
 *       'submit' => [
 *         '#type' => 'submit',
 *         '#value' => $this->t('Send'),
 *       ],
 *     ];
 *
 *     return $form;
 *   }
 *
 *   public function submitForm(array &$form, FormStateInterface $form_state): void {
 *     $config = \Drupal::configFactory()->getEditable('example.settings');
 *     $config->set('office_hours', $form_state->getValue('office_hours'));
 *     $config->save();
 *   }
 *
 * }
 * @endcode
 *
 * @FormElement("office_hours_table")
 */
class OfficeHoursTable extends Table {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();

    $info += [
      '#attached' => [
        'library' => [
          'office_hours/office_hours_widget',
        ],
      ],
    ];
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $indexed_items = [];

    $default_values = $element['#default_value'];
    $default_values = is_object($default_values) ? $default_values->getValue() : $default_values;

    if (!$input) {
      // Use seasonal, or normal Weekdays (empty season) to select items.
      // Create an indexed two level array of time slots:
      // - First level are day numbers.
      // - Second level contains items arranged by $day_delta.
      switch ($element['#field_type']) {

        case 'office_hours_exceptions':
          $day = 0;
          foreach ($default_values as $value) {
            switch ($value['day']) {
              case 'exception_day_delta':
              case $day:
                break;

              default:
                $day = $value['day'];
                break;
            }
            $indexed_items[$day][] = $value;
          }
          break;

        default:
          $indexed_items = array_fill_keys(range(0, 6), []);
          $season = $element['#field_type'];
          $week_season_id = is_object($season) ? $season->id() : 0;
          foreach ($default_values as $value) {
            $day = $value['day'];
            $season_id = OfficeHoursDateHelper::getSeasonId($day);
            if ($season_id == $week_season_id) {
              if (!OfficeHoursDateHelper::isExceptionDay($day)) {
                $weekday = OfficeHoursDateHelper::getWeekday($day);
                $value['day'] = $weekday;
                $indexed_items[$weekday][] = $value;
              }
            }
          }
          break;

      }

    }
    else {
      // Input exists.
      switch ($element['#field_type']) {

        case 'office_hours_exceptions':
          $new_empty_exception = '';
          $date = 0;
          $day_delta = 0;
          $previous_day = 0;
          foreach ($input as $key => $value) {
            switch ($value['day']) {
              case 'exception_day_delta':
              case $new_empty_exception:
              case $previous_day:
                $day_delta++;
                break;

              default:
                $day = $value['day'];
                $date = ($day == '') ? $previous_day++ : strtotime($day);
                $day_delta = 0;
                break;
            }
            $previous_day = $value['day'];
            $value['day_delta'] = $day_delta;
            $indexed_items[$date][] = $value;
          }

          // Keep aligned in ExceptionsWidget and OfficeHoursTable element.
          // Add empty days if we clicked "AddMore: Add exception".
          foreach ($default_values as $value) {
            if ($value['day'] == $new_empty_exception) {
              $date++;
              $indexed_items[$date][] = $value;
            }
          }
          break;

        default:
          foreach ($input as $key => $value) {
            $day = $value['day'];
            $indexed_items[$day][] = $value;
          }
          break;

      }
    }

    return $indexed_items;
  }

  /**
   * Generate an element.
   *
   * This function is referenced in the Annotation for this class.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The form.
   *
   * @return array
   *   The element.
   */
  public static function processTable(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processTable($element, $form_state, $complete_form);

    $field_settings = $element['#field_settings'];
    $widget_settings = $element['#widget_settings'];
    $cardinality = $field_settings['cardinality_per_day'];

    // The valueCallback() has populated the #value array.
    $default_items = $element['#default_value'];
    $indexed_items = $element['#value'];

    // Build multi element widget. Copy the description, etc. into the table.
    // Use the more complex 'data' construct for obsolete reasons.
    $header = OfficeHoursItem::getPropertyLabels('data', $field_settings);
    $element['#header'] = $header;

    switch ($element['#field_type']) {

      case 'office_hours_exceptions':
        $element['#header']['day']['data'] = t('Date');

        // Build elements, sorted by day number/timestamp.
        $elements = [];
        $element_type = 'office_hours_exceptions_slot';
        $day_index = 0;
        $days = array_keys($indexed_items);
        foreach ($days as $day) {
          $day_index++;

          for ($day_delta = 0; $day_delta < $cardinality; $day_delta++) {
            // Add a new empty item if it doesn't exist yet at this delta.
            $value = $indexed_items[$day][$day_delta]
            ?? OfficeHoursItem::format(['day' => OfficeHoursDateHelper::EXCEPTION_DAY_MIN]);

            $elements[] = [
              '#type' => $element_type,
              '#default_value' => $value,
              '#day_index' => $day_index,
              '#day_delta' => $day_delta,
              // Add field settings, for usage in each Element.
              '#field_settings' => $field_settings,
              '#title' => '',
              '#title_display' => 'invisible',
              '#description' => '',
            ];
          }
        }
        break;

      default:
        // Build elements, sorted by first_day_of_week.
        $elements = [];
        $element_type = 'office_hours_slot';
        $days = OfficeHoursDateHelper::weekDaysOrdered(range(0, 6));
        foreach ($days as $day) {
          // Add a helper for JS links (e.g., copy-link previousSelector) in widget.
          $day_index = $day;

          for ($day_delta = 0; $day_delta < $cardinality; $day_delta++) {
            // Add a new empty item if it doesn't exist yet at this delta.
            $value = $indexed_items[$day][$day_delta]
            ?? OfficeHoursItem::format(['day' => $day]);

            $elements[] = [
              '#type' => $element_type,
              '#default_value' => $value,
              '#day_index' => $day_index,
              '#day_delta' => $day_delta,
              // Add field settings, for usage in each Element.
              '#field_settings' => $field_settings,
              '#title' => '',
              '#title_display' => 'invisible',
              '#description' => '',
            ];
          }
        }

    }
    return $element + $elements;
  }

  /**
   * Render API callback: Validates the element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateTable(&$element, FormStateInterface $form_state, &$complete_form) {
    return parent::validateTable($element, $form_state, $complete_form);
  }

}
