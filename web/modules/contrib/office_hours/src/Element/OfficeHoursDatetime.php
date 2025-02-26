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
      // For HTML5, only 'H:i' is supported.
      // Hence, field setting 'time_format' / '#date_time_format' is discarded.
      /* @see https://www.drupal.org/project/drupal/issues/2723159 */
      /* @see https://www.drupal.org/project/drupal/issues/2841297 */
      '#date_time_format' => 'H:i',
      // @todo Add Timezone.
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
    $time_format = $element['#date_time_format'];
    $time = OfficeHoursDateHelper::format($element['#default_value'], $time_format);

    // $input = parent::valueCallback($element, $input, $form_state);
    $input = [
      // Date is not applicable.
      'date'   => '',
      // Overwrite time, for problems with added seconds.
      'time'   => $time,
      // Remove object, for problems with widget after 'Add exception'.
      'object' => NULL,
    ];
    return $input;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDatetime(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processDatetime($element, $form_state, $complete_form);

    // Fix the convention: convert minutes into seconds.
    $increment = $element['#field_settings']['increment'];
    $element['time']['#attributes']['step'] = $increment * 60;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateDatetime(&$element, FormStateInterface $form_state, &$complete_form) {
    // This function must exist, but can remain empty,
    // since no additional validations are needed.
  }

  /**
   * Mimic Core/TypedData/ComplexDataInterface.
   */

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
