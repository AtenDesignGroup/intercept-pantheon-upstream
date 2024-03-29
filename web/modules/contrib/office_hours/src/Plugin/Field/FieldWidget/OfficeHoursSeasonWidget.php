<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursSeason;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Plugin implementation of the 'office_hours_default' widget.
 *
 * @FieldWidget(
 *   id = "office_hours_season_only",
 *   label = @Translation("internal - do not select(season)"),
 *   description = @Translation("A subwidget for seasons."),
 *   field_types = {
 *     "office_hours_season_header",
 *     "office_hours_season_item",
 *   },
 *   multiple_values = TRUE,
 * )
 */
class OfficeHoursSeasonWidget extends OfficeHoursWeekWidget {

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

    // Add placeholder to make sure that season header is before season items.
    $element['season'] = [];
    $element += parent::formElement($items, $delta, $element, $form, $form_state);

    $season = $this->getSeason();
    $season_id = $season->id();
    if (!$season_id) {
      // Regular Weekdays. Just return after having processed parent.
      // Remainder of this function is for Seasons.
      return $element;
    }

    // @todo Use proper date format from field settings.
    $season_date_format = 'd-M-Y';
    // @todo Perhaps provide extra details following elements.
    // details #description;
    // container #description;
    // container #prefix;
    // container #title;
    // name #prefix;
    $name = $season->getName();
    $label = $season->label();
    $from = $season->getFromDate($season_date_format);
    $to = $season->getToDate($season_date_format);
    // Get default column labels.
    $labels = OfficeHoursItem::getPropertyLabels('data');
    $title = $season->isEmpty()
    ? ''
    : $labels['from']['data'] . " $from "
      . $labels['to']['data'] . " $to";
    $element = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => "<i>$label</i> " . $title,
      // '#description' => $label . ' (details #description)',
    ] + $element;

    // @todo Remove extra level, now needed for 'container-inline'.
    $element['season'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $element['season']['header'] = [
      '#type' => 'office_hours_season_header',
      '#default_value' => $season,
      // Add field settings, for usage in each Element.
      '#field_settings' => $this->getFieldSettings(),
      // Add a label/header/title for accessibility (a11y) screen readers.
      // '#title' => $label . ' (#title)',
      // '#title_display' => 'before', // {'before' | invisible'}.
      // '#description' => $label . ' (container #description)',
      // '#prefix' => "<b>$label (container #prefix)</b>",
      // .
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    // Rescue Season first, since it will be removed by parent function.
    $season = new OfficeHoursSeason($values['season']['header'] ?? 0);
    $this->setSeason($season);
    // Set in OfficeHoursSeasonHeader, parsed in OfficeHoursSeasonWidget.
    $delete_season = $values['season']['header']['operations']['data']['delete'] ?? NULL;

    $values = parent::massageFormValues($values, $form, $form_state);

    // @todo Validate if empty season has non-empty days and v.v.
    if ($season->id()) {
      $this->setSeason($season);

      if ($delete_season || $season->isEmpty()) {
        $values = [];
        return $values;
      }

      // Handle seasonal day nr., e.g., 4 --> 104.
      foreach ($values as $key => &$value) {
        $value['day'] += $season->id();
      }

      // Add season header to weekdays, to be saved in database.
      $values[] = $season->getValues();
    }

    return $values;
  }

}
