<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursSeason;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Plugin implementation of an office_hours widget.
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

    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
    $season = $this->getSeason();
    $season_id = $season->id();
    $filtered_items = $items->getSeasonItems($season_id);

    // Add placeholder to make sure that season header is before season items.
    $element += parent::formElement($filtered_items, $delta, $element, $form, $form_state);

    $field_settings = $this->getFieldSettings();
    $widget_settings = $this->getSettings();

    $name = $season->getName();
    $label = $season->label();
    // Build multi element widget. Copy the description, etc. into the table.
    // Use the more complex 'data' construct for obsolete reasons.
    $header = OfficeHoursItem::getPropertyLabels('data', $widget_settings);
    $title = $this->formatSeasonTitle($items, $delta, $element, $form, $form_state);
    $season_element = $this->getSeasonHeader($items, $delta, $element, $form, $form_state);

    // @todo In following line is $element added twice. Remove redundant data.
    // @todo Perhaps provide extra details following elements.
    // details #description;
    // container #description;
    // container #prefix;
    // container #title;
    // name #prefix;
    $element = [
      '#type' => 'details',
      // Controls the HTML5 'open' attribute. Defaults to FALSE.
      '#open' => (!$season_id),
      '#title' => $season_id ? "<i>$label</i> $title" : $element['#title'],
      'season' => $season_element,
    ] + $element;

    return $element;
  }

  /**
   * Returns the form element for the season header.
   *
   * {@inheritdoc}
   */
  public function getSeasonHeader(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [];

    $season = $this->getSeason();
    $season_id = $season->id();

    if (!$season_id) {
      return $element;
    }

    // Add extra level, needed for 'container-inline'.
    $element = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
      '#access' => (bool) $season_id,
    ];
    $element['header'] = [
      '#type' => 'office_hours_season_header',
      '#default_value' => $season,
      // Add field settings, for usage in each Element.
      '#field_settings' => $this->getFieldSettings(),
      // Add a label/header/title for accessibility (a11y) screen readers.
      // '#title' => "$label (#title)",
      // '#title_display' => 'before', // {'before' | invisible'}.
      // '#description' => "$label (container #description)",
      // '#prefix' => "<b>$label (container #prefix)</b>",
      // .
    ];
    return $element;
  }

  /**
   * Returns the form element for the season title.
   *
   * {@inheritdoc}
   */
  private function formatSeasonTitle(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $season = $this->getSeason();
    // @todo Use proper date format from field settings.
    $season_date_format = 'd-M-Y';
    // Get default column labels.
    $labels = OfficeHoursItem::getPropertyLabels('data');
    // Compose title.
    $title = $season->isEmpty() ? '' :
      $labels['from']['data'] . ' ' . $season->getFromDate($season_date_format) . ' '
      . $labels['to']['data'] . ' ' . $season->getToDate($season_date_format);

    return $title;
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
    $delete_season ??= $values['value']['season']['header']['operations']['data']['delete'] ?? NULL;

    $values = parent::massageFormValues($values, $form, $form_state);

    // @todo Validate if empty season has non-empty days and v.v.
    if ($season->id()) {
      $this->setSeason($season);
      unset($values['season']);
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
