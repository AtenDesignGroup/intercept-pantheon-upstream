<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Plugin implementation of the 'office_hours_exceptions' widget.
 *
 * @FieldWidget(
 *   id = "office_hours_exceptions_only",
 *   label = @Translation("Office hours exceptions (list)"),
 *   description = @Translation("An internal subwidget for exception dates."),
 *   field_types = {
 *     "office_hours_exceptions",
 *   },
 *   multiple_values = FALSE,
 * )
 */
class OfficeHoursExceptionsWidget extends OfficeHoursWidgetBase {

  /**
   * {@inheritdoc}
   *
   * Manipulate the default element data.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {

    // As per www.drupal.org/project/drupal/issues/1038316,
    // setProgrammed() adds 'add' and 'delete' buttons to the widget.
    $form_state->setProgrammed(FALSE);
    $element = parent::formMultipleElements($items, $form, $form_state);
    // Remove 'delete' button. (Somehow, the 'add' button is 1 level higher.)
    unset($element[0]['_actions']);

    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];
    $cardinality_per_day = $this->getFieldSetting('cardinality_per_day');

    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    // @todo Test and remove redundant $field_state value $count_days vs. $max.
    $count_days = $field_state["$field_name-exceptions_count"];
    $max = $field_state['items_count'];

    // Remove the 'field_multiple_value_form' theme,
    // since it interferes with our table layout.
    unset($element['#theme']);
    unset($element['#field_name']);
    // unset($element['#cardinality']);
    // unset($element['#cardinality_multiple']);
    unset($element['#required']);
    // unset($element['#title']);
    // unset($element['#description']);

    // Set/unset data for 'Add more' button.
    // unset($element['#max_delta']);
    $element['#max_delta'] = $max = $count_days;
    // unset($element['#prefix']);
    // unset($element['#suffix']);
    // unset($element['add_more']);
    $element['#cardinality_per_day'] = $cardinality_per_day;

    // Build multi element widget. Copy the description, etc. into the table.
    // Use the more complex 'data' construct,
    // to allow ExceptionsWeekWidget to add a 'colspan':
    $header = OfficeHoursItem::getPropertyLabels('data', $this->getFieldSettings());
    // Change header 'Day' to 'Date'.
    $header['day'] = $this->t('Date');

    $table = [
      '#type' => 'office_hours_table',
      '#header' => $header,
      '#tableselect' => FALSE,
      '#tabledrag' => FALSE,
      // '#attributes' => ['id' => 'exceptions-container'],
      '#empty' => $this->t('No exception day maintained, yet.'),
    ]
    + ($element[0] ?? []);
    // Remove the draggable feature from the table.
    unset($table['_weight']);
    // Remove the above added data.
    unset($element[0]);

    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    // Make sure the 'details' element keeps open after 'add_more' button.
    $collapsed = $field_state['collapsed'] ?? $this->getSetting('collapsed');
    $element = [
      // Wrap the table in a collapsible fieldset, which is the only way(?)
      // to show the 'required' asterisk and the help text.
      // The help text is now shown above the table, as requested by some users.
      // N.B. For some reason, the title is shown in Capitals.
      '#type' => 'details',
      // Controls the HTML5 'open' attribute. Defaults to FALSE.
      '#open' => !$collapsed,
      '#title' => $this->formatPlural($count_days, '1 exception', '@count exceptions'),
      // Add the time slot table as a sub-element.
      'value' => $table,
    ] + $element;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // In D8, we have a (deliberate) anomaly in the widget.
    // We prepare 1 widget for the whole week,
    // but the field has unlimited cardinality.
    // So with $delta = 0, we already show ALL values.
    if ($delta > 0) {
      return [];
    }

    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->getFieldSetting('cardinality_per_day');
    $parents = $form['#parents'];

    // Make form_state not cached since we will update it in ajax callback.
    $form_state->setCached(FALSE);

    // Create an indexed two level array of time slots:
    // - First level are day numbers.
    // - Second level contains field values arranged by $day_delta.
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
    $indexed_items = $items->getExceptionDays();
    $indexed_items = OfficeHoursDateHelper::weekDaysOrdered($indexed_items);

    // Add empty days if we clicked "Add exception".
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    // @todo Test and remove redundant $field_state value $count_days vs. $max.
    $count_days = $field_state["$field_name-exceptions_count"] ??= count($indexed_items);
    $max = $field_state['items_count'];
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    // Set a minimum of 1 Exception, to get the add_more button from WidgetBase.
    for ($i = count($indexed_items); $i < max(1, $count_days); $i++) {
      $day_delta = 0;
      $indexed_items[][$day_delta] = NULL;
    }

    // Build elements, sorted by day number/timestamp.
    $elements = [];
    $day_index = 0;
    foreach ($indexed_items as $day => $indexed_item) {
      $day_index++;

      for ($day_delta = 0; $day_delta < $cardinality; $day_delta++) {
        // Add a new empty item if it doesn't exist yet at this delta.
        $item = $indexed_items[$day][$day_delta]
        ?? $items->appendItem([
          'day' => OfficeHoursItem::EXCEPTION_DAY,
        ]);

        $elements[] = [
          '#type' => 'office_hours_exceptions_slot',
          '#default_value' => $item,
          '#day_index' => $day_index,
          '#day_delta' => $day_delta,
          // Add field settings, for usage in each Element.
          '#field_settings' => $this->getFieldSettings(),
          '#date_element_type' => $this->getSetting('date_element_type'),
          '#title' => '',
          '#title_display' => 'invisible',
          '#description' => '',
        ];

      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    return parent::afterBuild($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    // @todo Test and remove redundant $field_state value $count_days vs. $max.
    $field_state["$field_name-exceptions_count"]++;
    $field_state['items_count']++;
    // Make sure the 'details' element keeps open after 'add_more' button.
    $field_state['collapsed'] = FALSE;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    // Ensure the widget allows adding additional items.
    if ($element['#cardinality'] != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      return;
    }

    // Add a DIV around the delta receiving the Ajax effect.
    // Taking into account the office_hours cardinality.
    $cardinality_per_day = $element['#cardinality_per_day'];
    $delta = ($element['#max_delta'] - 1) * $cardinality_per_day;
    for ($i = 0; $i < $cardinality_per_day; $i++) {
      $element['value'][$delta + $i]['#prefix'] = '<div class="ajax-new-content">' . ($element['value'][$delta + $i]['#prefix'] ?? '');
      $element['value'][$delta + $i]['#suffix'] = ($element['value'][$delta + $i]['#suffix'] ?? '') . '</div>';
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return parent::massageFormValues($values, $form, $form_state);
  }

}
