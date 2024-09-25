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
    // @todo Fix Warning: Undefined array key "translation" in EntityDisplayFormBase->copyFormValuesToEntity()
    // @todo Fix Warning: Trying to access array offset on value of type null in Drupal\field_ui\Form\EntityDisplayFormBase->copyFormValuesToEntity() (line 628 of EntityDisplayFormBase.php).

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

    // @todo Parent data are lost.
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $items->filterEmptyItems();

    $field_settings = $this->getFieldSettings();
    $widget_settings = $this->getSettings();

    $element = [
      '#type' => 'office_hours_table',
      '#field_settings' => $field_settings,
      '#widget_settings' => $widget_settings,
      // Use seasonal, or normal Weekdays (empty season) to select items.
      '#field_type' => $this->getSeason(),
      '#default_value' => $items->getValue(),
    ];

    /*
    // @todo Add Message.
    if (Weekwidget && contains exceptions or season) {
      // Add a user message, in case normal weekday widget is used.
      // In complex widget, this message is removed, again.
      $this->addMessage($item);
    }
     */

     return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    $element = parent::afterBuild($element, $form_state);
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
      // Deduction of weekday/season/exception values.
      $values = $values['value'] ?? $values;
    }
    else {
      // Below lines should work fine with Annotation: multiple_values = FALSE.
      $values = reset($values)['value'];
    }
    $values = parent::massageFormValues($values, $form, $form_state);

    // @todo Correct $element['#parents'], since FormBuilder's
    // $form_state->setValueForElement() and  $form_state->setUserInput($input)
    // set too much/wrong data, complicating massageFormValues().
    $new_values = [];
    $cardinality = $this->getFieldSetting('cardinality_per_day');
    foreach ($values as $value) {
      for ($day_delta = 0; $day_delta < $cardinality; $day_delta++) {
        if (isset($value[$day_delta]['day'])) {
          if ($day_delta == 0) {
            $date = $value[$day_delta]['day'];
          }
          $value[$day_delta]['day'] = $date;
          $new_values[] = $value[$day_delta];
        }
      }
    }
    return $new_values;
  }

}
