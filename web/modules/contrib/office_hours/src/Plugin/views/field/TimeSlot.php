<?php

namespace Drupal\office_hours\Plugin\views\field;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;
use Drupal\views\ResultRow;

/**
 * Displays the time slot per weekday/exception date.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("office_hours_timeslot")
 *
 * Tested cases: Entities with no/empty/filled office hours field.
 * @todo Entities with empty office_hours do not show 'closed' text.
 * @todo Exception days on invisible Weekdays are still displayed.
 */
class TimeSlot extends FieldBase {

  /**
   * A static array to avoid double calculations.
   *
   * @var array
   */
  protected static $renderIndex = NULL;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // Add the Weekday option.
    $options['day'] = ['default' => 0];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // From OfficeHoursFormatterBase::settingsForm().
    $form['day'] = [
      '#title' => $this->t('Day'),
      '#type' => 'select',
      '#options' => $this->getWeekDays(),
      '#default_value' => $this->options['day'],
      '#description' => $this->t('The weekdays for this column. Create a column for each weekday.'),
    ];
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
   * Returns Season item or Exception day item.
   */
  public function getValue(ResultRow $values, $field = NULL) {
    return parent::getValue($values, $field);
  }

  /**
   * Remove excessive rows from the result.
   *
   * This field is to be used as 'day' column.
   *
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    /** @var \Drupal\views\ResultRow[] $values */
    $field_name = $this->configuration['field_name'];

    // Calculate only once.
    if (!is_null(static::$renderIndex)) {
      return;
    }

    // Fail if office_hours field is not set. We need its formatter settings.
    if (!isset($this->view->field[$field_name])) {
      $form = [];
      $form_state = new FormState();
      $this->addOptionsFormWarning($form, $form_state, $this->options['label']);
      // Set value to not return here again.
      static::$renderIndex = [];
      // Clear all render output. View is incorrect.
      $values = [];

      return;
    }

    // Get the formatter settings of the main 'office_hours' field,
    // re-using the time slot formatter settings 7 times.
    $formatter_settings = $this->getFieldSettings($field_name);
    // @todo no need to get $widget_settings from $items.
    // $widget_settings = $items->getFieldDefinition()->getSettings();
    // @todo Fetch third_party_settings.
    $third_party_settings = [];

    $entity_id = NULL;
    foreach ($values as $key => $resultRow) {
      // Initialize some data, per entity.
      $entity = $resultRow->_entity;
      // Note: $values may not be sorted per $entity_id.
      if ($entity_id !== $entity->id()) {
        $entity_id = $entity->id();

        // Get Items. Usage of the static, since should be faster.
        /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $items */
        /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
        if ($entity->hasField($field_name)) {
          $items ??= $entity->get($field_name);
          // @todo no need to get $widget_settings from $items.
          $widget_settings = $items->getFieldDefinition()->getSettings();
          // Cache data to avoid re-calc from entity later per row.
          static::$renderIndex[$entity_id]['items'] ??= $items;
          static::$renderIndex[$entity_id]['seasons'] ??= $items->getSeasons(TRUE);
          static::$renderIndex[$entity_id]['office_hours'] ??= $items->getRows($formatter_settings, $widget_settings, $third_party_settings);
        }
      }

      // Fetch from variable, for shorter code later.
      // Note: Seasons updated via reference to remove duplicate rows.
      // Careful: also entities without office_hours exist.
      $seasons = &static::$renderIndex[$entity_id]['seasons'] ?? [];
      $office_hours = static::$renderIndex[$entity_id]['office_hours'] ?? [];

      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
      $item = $this->getValue($resultRow, $field_name);
      $id = 0;
      switch (TRUE) {
        case is_null($item):
          // Entity without office_hours. Just keep in the list.
          break;

        case $item->isExceptionDay():
          $id = $item->day;
          if (!isset($office_hours[$id])) {
            // $office_hours contains a 'horizon' of Exception days.
            // Remove excessive Exception days.
            unset($values[$key]);
          }
          break;

        default:
          $id = $item->getSeasonId();
          break;
      }
      if (($seasons[$id] ?? FALSE) == 'found') {
        // Keep 1 row per season (incl. normal hours).
        // Keep 1 row per Exception, removing 'day_delta' > 0.
        // Note: mis-using season array for exception days, too.
        // Each weekday column will fill from this entity row.
        unset($values[$key]);
      }
      $seasons[$id] = 'found';
      // End foreach.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $item = $this->getValue($values);

    $entity = $this->getEntity($values);
    $value = $this->getTimeSlot($entity, $item);
    return $this->sanitizeValue($value);
  }

  /**
   * Returns formatted operating hours.
   */
  private function getTimeSlot($entity, OfficeHoursItem|NULL $item) {

    if (!$item) {
      return NULL;
    }

    // Get the weekday for this column.
    $weekday = (int) $this->options['day'];

    $entity_id = $entity->id();
    $office_hours = static::$renderIndex[$entity_id]['office_hours'];
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $items */
    switch (TRUE) {
      case is_null($item):
        return '';

      case $item->isExceptionDay():
        // Fill the Exception in the correct Weekday column.
        // @todo What if the Weekday is not on view (e.g., Fri-Sun)?
        if ($weekday !== $item->getWeekday()) {
          return NULL;
        }
        $day = $office_hours[$item->day] ?? NULL;
        return $day ? $day['formatted_slots'] : NULL;

      // case $item->isSeasonHeader():
      // case $item->isSeasonDay():
      // case $item->isWeekDay():
      default:
        $season_id = $item->getSeasonId();
        $day = $office_hours[$season_id + $weekday] ?? NULL;
        return $day ? $day['formatted_slots'] : NULL;
    }

  }

  /**
   * Returns array of Weekdays.
   */
  private function getWeekDays() {
    return OfficeHoursDateHelper::weekDaysByFormat('long');
  }

}
