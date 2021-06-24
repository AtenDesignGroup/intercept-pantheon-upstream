<?php

namespace Drupal\office_hours\Element;

use Drupal\Component\Utility\NestedArray;
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
    $class = get_class($this);
    $parent_info = parent::getInfo();

    $info = [
      '#process' => [
        [$class, 'processOfficeHours'],
      ],
      '#element_validate' => [
        [$class, 'validateOfficeHours'],
      ],

      // @see Drupal\Core\Datetime\Element\Datetime.
      '#date_date_element' => 'none', // {'none'|'date'}
      '#date_date_format' => 'none',
      '#date_time_element' => 'time', // {'none'|'time'|'text'}
      // @see Drupal\Core\Datetime\Element\DateElementBase.
      '#date_timezone' => '+0000', // New \DateTimezone(DATETIME_STORAGE_TIMEZONE),
    ];

    // #process: bottom-up.
    $info['#process'] = array_merge($parent_info['#process'], $info['#process']);

    return $info + $parent_info;
  }

  /**
   * Callback for office_hours_select element.
   *
   * Takes #default_value and dissects it in hours, minutes and ampm indicator.
   * Mimics the date_parse() function.
   * - g = 12-hour format of an hour without leading zeros 1 through 12
   * - G = 24-hour format of an hour without leading zeros 0 through 23
   * - h = 12-hour format of an hour with leading zeros    01 through 12
   * - H = 24-hour format of an hour with leading zeros    00 through 23
   *
   * @param array $element
   * @param mixed $input
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array|mixed|null
   *   The value, as entered by the user.
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {

    $input['time'] = OfficeHoursDatetime::get($element['#default_value'], 'H:i');

    $input = parent::valueCallback($element, $input, $form_state);
    $element['#default_value'] = $input;

    return $input;
  }

  /**
   * Process the office_hours_select element before showing it.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $complete_form
   *
   * @return array
   *   The processed element.
   */
  public static function processOfficeHours(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processDatetime($element, $form_state, $complete_form);

    // @todo Use $element['#date_time_callbacks'], do not use this function.
    // Adds the HTML5 attributes.
    $element['time']['#attributes'] = [
      // @todo Set a proper from/to title.
      // 'title' => $this->t('Time (e.g. @format)', ['@format' => static::formatExample($time_format)]),
      // Fix the convention: minutes vs. seconds.
      'step' => $element['#date_increment'] * 60,
    ] + $element['time']['#attributes'];

    return $element;
  }

  /**
   * Validate the hours selector element.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $complete_form
   */
  public static function validateOfficeHours(&$element, FormStateInterface $form_state, &$complete_form) {
    $input_exists = FALSE;

    // @todo Call validateDatetime().
    // Get the 'time' sub-array.
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
    // Generate the 'object' sub-array.
    parent::valueCallback($element, $input, $form_state);
  }

  /**
   * Mimic Core/TypedData/ComplexDataInterface.
   */

  /**
   * Returns the data from a widget.
   *
   * There are too many similar functions:
   *  - OfficeHoursWidgetBase::massageFormValues();
   *  - OfficeHoursItem, which requires an object;
   *  - OfficeHoursDateTime::get() (this function).
   *
   * @todo Use Core/TypedData/ComplexDataInterface.
   *
   * @param mixed $element
   *   A string or array for time.
   * @param string $format
   *   Required time format.
   *
   * @return string
   *   Return value.
   */
  public static function get($element, $format = 'Hi') {
    $value = '';
    // Be prepared for Datetime and Numeric input.
    // Numeric input set in OfficeHoursDateList/Datetime::validateOfficeHours().
    if (!isset($element)) {
      return $value;
    }

    if (isset($element['time'])) {
      // Return NULL or time string.
      $value = OfficeHoursDateHelper::format($element['time'], $format);
    }
    elseif (!empty($element['hour'])) {
      $value = OfficeHoursDateHelper::format($element['hour'] * 100 + $element['minute'], $format);
    }
    elseif (!isset($element['hour'])) {
      $value = OfficeHoursDateHelper::format($element, $format);
    }
    return $value;
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
    if ($element === NULL) {
      return TRUE;
    }
    if ($element === '') {
      return TRUE;
    }
    if ($element === '-1') {
      // Empty hours/minutes, but comment enabled.
      return TRUE;
    }
    if (is_array($element)) {
      if (isset($element['time']) && $element['time'] === '') {
        return TRUE;
      }
      if (!isset($element['day']) && !isset($element['time'])) {
        return TRUE;
      }
      // Check normal element.
      if ((isset($element['day']) && (7 > (int) $element['day']))
        && (isset($element['starthours']) && OfficeHoursDatetime::isEmpty($element['starthours']))
        && (isset($element['endhours']) && OfficeHoursDatetime::isEmpty($element['endhours']))
        && (!isset($element['comment']) || empty($element['comment']))
      ) {
        return TRUE;
      }
      // Check HTML5 datetime element.
      if ((isset($element['starthours']['time']) && OfficeHoursDatetime::isEmpty($element['starthours']['time']))
        && (isset($element['endhours']['time']) && OfficeHoursDatetime::isEmpty($element['endhours']['time']))
        && (!isset($element['comment']) || empty($element['comment']))
      ) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
