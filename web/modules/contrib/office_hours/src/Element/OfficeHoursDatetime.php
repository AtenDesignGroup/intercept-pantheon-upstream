<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Provides a one-line HTML5 time element.
 *
 * @FormElement("office_hours_datetime")
 */
class OfficeHoursDatetime extends Datetime {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $parent_info = parent::getInfo();

    $info = [
      // @see Drupal\Core\Datetime\Element\Datetime.
      '#date_date_element' => 'none', // {'none'|'date'}
      '#date_date_format' => 'none',
      '#date_time_element' => 'time', // {'none'|'time'|'text'}
      // @see Drupal\Core\Datetime\Element\DateElementBase.
      // '#date_timezone' => \DateTimezone(DATETIME_STORAGE_TIMEZONE), .
      '#date_timezone' => '+0000',
    ];

    return $info + $parent_info;
  }

  /**
   * Callback for hours element.
   *
   * {@inheritdoc}
   *
   * Takes #default_value and dissects it in hours, minutes and ampm indicator.
   * Mimics the date_parse() function.
   * - g = 12-hour format of an hour without leading zeros 1 through 12
   * - G = 24-hour format of an hour without leading zeros 0 through 23
   * - h = 12-hour format of an hour with leading zeros    01 through 12
   * - H = 24-hour format of an hour with leading zeros    00 through 23
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {

    if ($input !== FALSE) {
      $input = parent::valueCallback($element, $input, $form_state);
    }
    else {
      // Initial load from database.
      // Format the integer time into a DateTime object.
      // Avoiding PHP8.1 Deprecated function error:
      // "Automatic conversion of false to array is deprecated in [...]".
      $input = [];
      $input['time'] = OfficeHoursDateHelper::format($element['#default_value'], 'H:i');
      // Generate the 'object' sub-array.
      $input = parent::valueCallback($element, $input, $form_state);
      // $element['#default_value'] = $input; // @todo Test, also DateList.
    }
    return $input;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDatetime(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processDatetime($element, $form_state, $complete_form);

    // @todo Use $element['#date_time_callbacks'], do not use this function.
    // Adds the HTML5 attributes.
    $element['time']['#attributes'] = [
      // @todo Set a proper from/to title.
      // 'title' => $this->t('Time (e.g. @format)',
      // ['@format' => static::formatExample($time_format)]),
      // Fix the convention: minutes vs. seconds.
      'step' => $element['#date_increment'] * 60,
    ] + $element['time']['#attributes'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateDatetime(&$element, FormStateInterface $form_state, &$complete_form) {
    /*
    // Get the 'time' sub-array.
    $input_exists = FALSE;
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
    // Generate the 'object' sub-array.
    $input = static::valueCallback($element, $input, $form_state);

    // Continue with default processing.
    // parent::validateDatetime($element, $form_state, $complete_form);
     */
  }

  /**
   * Mimic Core/TypedData/ComplexDataInterface.
   */

  /**
   * Returns the data from a widget.
   *
   * @param mixed $element
   *   A string or array for time.
   * @param string $format
   *   Required time format.
   *
   * @return string
   *   Return value.
   *
   * @deprecated@see in 8.x-1.5 and replaced by OfficeHoursDateHelper::format().
   */
  public static function get($element, $format = 'Hi') {
    return OfficeHoursDateHelper::format($element, $format);
  }

  /**
   * Determines whether the data structure is empty.
   *
   * @param mixed $element
   *   A string or array for time slot.
   *   Example from HTML5 input, without comments enabled.
   *   @code
   *     array:3 [
   *       "day" => "3"
   *       "starthours" => array:1 [
   *         "time" => "19:30"
   *       ]
   *       "endhours" => array:1 [
   *         "time" => ""
   *       ]
   *     ]
   *   @endcode
   *
   * @return bool
   *   TRUE if the data structure is empty, FALSE otherwise.
   */
  public static function isEmpty($element) {
    // Note: in Week-widget, day is <> '', in List-widget, day can be ''.
    // And in Exception day, day can be ''.
    // Note: test every change with Week/List widget and Select/HTML5 element!
    if ($element === NULL) {
      return TRUE;
    }
    if ($element === '') {
      return TRUE;
    }
    if (isset($element['time'])) {
      // HTML5 datetime element.
      return ($element['time'] === '');
    }

    return FALSE;
  }

}
