<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursSeason;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Base class for the 'office_hours_*' widgets.
 */
abstract class OfficeHoursWidgetBase extends WidgetBase {

  /**
   * The season data. Can only be changed in SeasonWidget, not WeekWidget.
   *
   * @var \Drupal\office_hours\OfficeHoursSeason
   */
  protected $season;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['value'] = [
      // Add field settings, for usage in each Element.
      '#field_settings' => $this->getFieldSettings(),
      '#attached' => [
        'library' => [
          'office_hours/office_hours_widget',
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    // Only need to widget specific massaging of form values,
    // All logical changes will be done in ItemList->setValue($values),
    // where the formatValue() function will be called, also.
    $day = '';
    foreach ($values as &$value) {
      OfficeHoursItem::massageFormValue($value);
      // OfficeHoursItem::formatValue($value);.
      //
      // Process Exception days with 'more slots'.
      // This cannot be done in above form, since we parse $day over Items.
      if (OfficeHoursItem::isExceptionDay($value, TRUE)) {
        if (!OfficeHoursItem::isValueEmpty($value)) {
          // Copy Exception day number from delta 0 to 'more' slots,
          // in case the user changed the date,
          // and to avoid removing lines with day but empty hours.
          if (isset($value['day_delta']) && $value['day_delta'] == 0) {
            // Reset value, to be re-used for day_delta 1, 2, ...
            $day = $value['day'];
          }
          $value['day'] = $day;
        }
      }

    }
    return $values;
  }

  /**
   * Get the season for this Widget.
   *
   * @return \Drupal\office_hours\OfficeHoursSeason
   *   The season.
   */
  public function getSeason() {
    // Use season, or normal Weekdays (empty season).
    $this->season = $this->season ?? new OfficeHoursSeason();
    return $this->season;
  }

  /**
   * Set the season for this WeekWidget (0-6 is the regular week).
   *
   * @param \Drupal\office_hours\OfficeHoursSeason $season
   *   The season.
   *
   * @return \Drupal\office_hours\Plugin\Field\FieldWidget\OfficeHoursSeasonWidget
   *   The widget object itself.
   */
  public function setSeason(OfficeHoursSeason $season) {
    $this->season = $season;
    return $this;
  }

}
