<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Plugin implementation of the 'office_hours_default' widget.
 *
 * @FieldWidget(
 *   id = "office_hours_default",
 *   label = @Translation("Office hours (week)"),
 *   description = @Translation("A widget for weekdays."),
 *   field_types = {
 *     "office_hours",
 *   },
 *   multiple_values = TRUE,
 * )
 */
class OfficeHoursWeekWidget extends OfficeHoursWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'collapsed' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // @todo jvo Fix Warning: Undefined array key "translation" in Drupal\field_ui\Form\EntityDisplayFormBase->copyFormValuesToEntity()
    // @todo jvo Fix Warning: Trying to access array offset on value of type null in Drupal\field_ui\Form\EntityDisplayFormBase->copyFormValuesToEntity() (line 628 of C:\damp\xampp\htdocs\drupal-10\core\modules\field_ui\src\Form\EntityDisplayFormBase.php).

    $form['collapsed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapse exceptions sub-widget'),
      '#default_value' => $this->getSetting('collapsed'),
    ];

    // "In order to get proper UX, check User interface translation page
    // "for the strings From and To in Context 'A point in time'.
    OfficeHoursItem::addMessage();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Collapsed exceptions: @collapsed', ['@collapsed' => $this->getSetting('collapsed') ? $this->t('Yes') : $this->t('No')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * Note: This is never called, since Annotation: multiple_values = TRUE.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $this->fieldDefinition->getFieldStorageDefinition()
        ->setCardinality($this->getFieldSetting('cardinality_per_day') * OfficeHoursDateHelper::DAYS_PER_WEEK);
    }

    $elements = parent::formMultipleElements($items, $form, $form_state);

    // Remove the 'drag-n-drop reordering' element.
    $elements['#cardinality_multiple'] = FALSE;
    // Remove the little 'Weight for row n' box.
    unset($elements[0]['_weight']);

    return $elements;
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

    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $items->filterEmptyItems();

    // Use seasonal, or normal Weekdays (empty season).
    $season_id = $this->getSeason()->id();
    // Create an indexed two level array of time slots:
    // - First level are day numbers.
    // - Second level contains items arranged by $day_delta.
    $indexed_items = array_fill_keys(range(0, 6), []);
    foreach ($items as $item) {
      // Only add relevant Weekdays/Season days.
      $value = $item->getValue();
      $day = $value['day'];
      if ($item->getSeasonId() == $season_id && !$item->isExceptionDay()) {
        $day = $item->getWeekday();
        $value['day'] = $day;
        $item->setValue($value);
        $indexed_items[$day][] = $item;
      }
      else {
        // Add a user message, in case normal weekday widget is used.
        // In complex widget, this message is removed, again.
        $this->addMessage($item);
      }
    }

    // Build elements, sorted by first_day_of_week.
    $elements = [];
    $days = OfficeHoursDateHelper::weekDaysOrdered(range(0, 6));
    $cardinality = $this->getFieldSetting('cardinality_per_day');
    foreach ($days as $day) {
      // Add a helper for JS links (e.g., copy-link previousSelector) in widget.
      $day_index = $day;

      for ($day_delta = 0; $day_delta < $cardinality; $day_delta++) {
        $item = $indexed_items[$day][$day_delta] ?? $items->appendItem(['day' => $day]);
        $elements[] = [
          '#type' => 'office_hours_slot',
          '#default_value' => $item,
          '#day_index' => $day_index,
          '#day_delta' => $day_delta,
          // Add field settings, for usage in each Element.
          '#field_settings' => $this->getFieldSettings(),
          '#date_element_type' => $this->getSetting('date_element_type'),
        ];
      }
    }

    // Wrap the table in a collapsible fieldset, which is the only way(?)
    // to show the 'required' asterisk and the help text.
    // The help text is now shown above the table, as requested by some users.
    // N.B. For some reason, the title is shown in Capitals.
    $element['#type'] = 'details';
    // Controls the HTML5 'open' attribute. Defaults to FALSE.
    // @todo Add such field setting also for Weekday, not only for exceptions.
    // $element['#open'] = !$this->getSetting('collapsed_weekday'); .
    // Note: this setting is applied in another spot.
    $element['#open'] = TRUE;

    // Build multi element widget. Copy the description, etc. into the table.
    // Use the more complex 'data' construct for obsolete reasons.
    $header = OfficeHoursItem::getPropertyLabels('data', $this->getFieldSettings());
    $element['value'] = [
      '#type' => 'office_hours_table',
      '#header' => $header,
      '#tableselect' => FALSE,
      '#tabledrag' => FALSE,
    ] + $element['value'] + $elements;

    return $element;
  }

  /**
   * This function repairs the anomaly we mentioned before.
   *
   * Reformat the $values, before passing to database.
   *
   * @see formElement(),formMultipleElements()
   *
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    if ($this->handlesMultipleValues()) {
      // Below line works fine with Annotation: multiple_values = TRUE.
      $values = $values['value'];
    }
    else {
      // Below lines should work fine with Annotation: multiple_values = FALSE.
      $values = reset($values)['value'];
    }
    $values = parent::massageFormValues($values, $form, $form_state);

    return $values;
  }

}
