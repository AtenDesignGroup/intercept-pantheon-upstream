<?php

namespace Drupal\intercept_core\Element;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a datetime element.
 *
 * @FormElement("datetime_select")
 */
class DatetimeSelect extends Datetime {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#date_time_element'] = 'time_select';
    $info['#date_time_format'] = 'g:i a';

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDatetime(&$element, FormStateInterface $form_state, &$complete_form) {
    $format_settings = [];
    // The value callback has populated the #value array.
    $date = !empty($element['#value']['object']) ? $element['#value']['object'] : NULL;

    $element['#tree'] = TRUE;

    if ($element['#date_date_element'] != 'none') {

      $date_format = $element['#date_date_element'] != 'none' ? static::getHtml5DateFormat($element) : '';
      $date_value = !empty($date) ? $date->format($date_format, $format_settings) : $element['#value']['date'];

      // Creating format examples on every individual date item is messy, and
      // placeholders are invalid for HTML5 date and datetime, so an example
      // format is appended to the title to appear in tooltips.
      $extra_attributes = [
        'title' => new TranslatableMarkup('Date (e.g. @format)', ['@format' => static::formatExample($date_format)]),
        'type' => $element['#date_date_element'],
      ];

      // Adds the HTML5 date attributes.
      if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
        $html5_min = clone($date);
        $range = static::datetimeRangeYears($element['#date_year_range'], $date);
        $html5_min->setDate($range[0], 1, 1)->setTime(0, 0, 0);
        $html5_max = clone($date);
        $html5_max->setDate($range[1], 12, 31)->setTime(23, 59, 59);

        $extra_attributes += [
          'min' => $html5_min->format($date_format, $format_settings),
          'max' => $html5_max->format($date_format, $format_settings),
        ];
      }

      $element['date'] = [
        '#type' => 'date',
        '#title' => new TranslatableMarkup('Date'),
        '#title_display' => 'invisible',
        '#value' => $date_value,
        '#attributes' => $element['#attributes'] + $extra_attributes,
        '#required' => $element['#required'],
        '#size' => max(12, strlen($element['#value']['date'])),
        '#error_no_message' => TRUE,
        '#date_date_format' => $element['#date_date_format'],
      ];

      // Allows custom callbacks to alter the element.
      if (!empty($element['#date_date_callbacks'])) {
        foreach ($element['#date_date_callbacks'] as $callback) {
          if (is_callable($callback)) {
            $callback($element, $form_state, $date);
          }
        }
      }
    }

    if ($element['#date_time_element'] != 'none') {

      $time_format = $element['#date_time_element'] != 'none' ? static::getHtml5TimeFormat($element) : '';
      $time_value = !empty($date) ? $date->format($time_format, $format_settings) : $element['#value']['time'];

      $element['time'] = [
        '#type' => 'time_select',
        '#title' => new TranslatableMarkup('Time'),
        '#title_display' => 'invisible',
        '#value' => $time_value,
        '#format' => $element['#date_time_element'] != 'none' ? $element['#date_time_format'] : '',
        '#attributes' => $element['#attributes'] + $extra_attributes,
        '#required' => $element['#required'],
        '#multiple' => FALSE,
        '#error_no_message' => TRUE,
      ];

      // Allows custom callbacks to alter the element.
      if (!empty($element['#date_time_callbacks'])) {
        foreach ($element['#date_time_callbacks'] as $callback) {
          if (function_exists($callback)) {
            $callback($element, $form_state, $date);
          }
        }
      }
    }

    return $element;
  }

}
