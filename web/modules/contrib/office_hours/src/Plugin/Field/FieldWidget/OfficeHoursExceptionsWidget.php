<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\FocusFirstCommand;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;

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
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // In D8, we have a (deliberate) anomaly in the widget.
    // We prepare 1 widget for the whole week,
    // but the field has unlimited cardinality.
    // So with $delta = 0, we already show ALL values.
    if ($delta > 0) {
      return [];
    }

    // Make form_state not cached since we will update it in ajax callback.
    $form_state->setCached(FALSE);
    // Avoid adding delete and add buttons.
    // $form_state->setProgrammed(TRUE);

    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
    $filtered_items = $items->getExceptionItems();
    $element = parent::formElement($filtered_items, $delta, $element, $form, $form_state);

    // Copied from WidgetBase::formMultipleElements();
    $parents = $form['#parents'];
    $field_settings = $this->getFieldSettings();
    $widget_settings = $this->getSettings();
    $field_name = $this->fieldDefinition->getName();
    $field_state = static::getWidgetState($parents, $field_name, $form_state);

    if (!isset($field_state["$field_name-exceptions_count"])) {
      $field_state["$field_name-exceptions_count"] ??= $items->countExceptionDays();
      $field_state['collapsed_exceptions'] ??= $widget_settings['collapsed_exceptions'];
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }

    $count_days = $field_state["$field_name-exceptions_count"];
    // Keep aligned in ExceptionsWidget and OfficeHoursTable element.
    // Add empty days if we clicked "AddMore: Add exception".
    // We cannot do this in valueCallback, in case a date value is changed.
    for ($i = $filtered_items->countExceptionDays(); $i < max(0, $count_days); $i++) {
      $filtered_items->appendItem(['day' => OfficeHoursDateHelper::EXCEPTION_DAY_MIN]);
    }

    // @todo Set better '#parents', so addMoreAjax() can adhere to core.
    $element = [
      '#type' => 'office_hours_table',
      '#field_settings' => $field_settings,
      '#widget_settings' => $widget_settings,
      '#field_type' => 'office_hours_exceptions',
      '#default_value' => $filtered_items->getValue(),
      '#tableselect' => FALSE,
      '#tabledrag' => FALSE,
      // '#attributes' => ['id' => 'exceptions-container'],
      '#empty' => $this->t('No exception day maintained, yet.'),
    ];

    $element = [
      // Wrap the table in a collapsible fieldset, which is the only way(?)
      // to show the 'required' asterisk and the help text.
      // The help text is now shown above the table, as requested by some users.
      // N.B. For some reason, the title is shown in Capitals.
      '#type' => 'details',
      // Controls the HTML5 'open' attribute. Defaults to FALSE.
      // Make sure the 'details' element keeps open after 'Add exception' button.
      '#open' => !$field_state['collapsed_exceptions'],
      '#title' => \Drupal::translation()->formatPlural($count_days, '1 exception', '@count exceptions'),
      // Add the time slot table as a sub-element.
      'value' => $element,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Change core behaviour, added after formElement().
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    $element = parent::afterBuild($element, $form_state);

    // Remove the 'drag-n-drop reordering' element.
    $element['#cardinality_multiple'] = FALSE;
    // Remove the little draggable 'Weight for row n' box.
    unset($element['_weight']);
    // Remove the 'Remove' action button.
    unset($element['_actions']);
    $element['add_more']['#value'] = t('Add exception');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    parent::extractFormValues($items, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    $parents = $element['#field_parents'];
    $field_name = $element['#field_name'];
    $field_state = static::getWidgetState($parents, $field_name, $form_state);

    // Increment the items count.
    $field_state["$field_name-exceptions_count"]++;
    $field_state['items_count']++;
    // Make sure the 'details' element keeps open after 'Add exception' button.
    $field_state['collapsed_exceptions'] = FALSE;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   *
   * Contains the following changes compared with parent:
   * - determine the $delta differently;
   * - use 'value' subelement to set prefix/suffix.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    // return parent::addMoreAjax($form, $form_state);
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $element['#max_delta'];
    // Start: Override the $delta by incrementing the items count.
    // @todo Update $element['#max_delta'] in other location, to avoid this override.
    $parents = $element['#field_parents'];
    $field_name = $element['#field_name'];
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $exceptions_count = $field_state["$field_name-exceptions_count"];

    // Taking into account the office_hours cardinality.
    $cardinality_per_day = $element['value']['#field_settings']['cardinality_per_day'];
    $delta = ($exceptions_count - 1) * $cardinality_per_day;
    // $element['#max_delta'] = $delta;
    // End: Override the $delta by incrementing the items count.

    // @todo Set better '#parents', so addMoreAjax() can adhere to core.
    // Construct an attribute to add to div for use as selector to set the focus on.
    $button_parent = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $focus_attribute = 'data-drupal-selector="field-' . $button_parent['#field_name'] . '-more-focus-target"';
    $element['value'][$delta]['#prefix'] = '<div class="ajax-new-content" ' . $focus_attribute . '>' . ($element[$delta]['#prefix'] ?? '');
    $element['value'][$delta]['#suffix'] = ($element[$delta]['#suffix'] ?? '') . '</div>';

    // Turn render array into response with AJAX commands.
    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand(NULL, $element));
    // Add command to set the focus on first focusable element within the div.
    $response->addCommand(new FocusFirstCommand("[$focus_attribute]"));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return parent::massageFormValues($values, $form, $form_state);
  }

}
