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
//      '#input' => TRUE,
//      '#tree' => TRUE,
      '#process' => [
        [$class, 'processOfficeHours'],
      ],
      '#element_validate' => [
        [$class, 'validateOfficeHours'],
      ],

      // @see Drupal\Core\Datetime\Element\Datetime.
      '#date_date_element' => 'none', // {'none'|'date'}
      '#date_date_format' => 'none',
      //'#date_date_callbacks' => [],
      '#date_time_element' => 'time', // {'none'|'time'|'text'}
      //'#date_time_format' => 'time', // see format_date()
      //'#date_time_callbacks' => [], // Can be used to add a jQuery time picker or an 'All day' checkbox.
      //'#date_year_range' => '1900:2050',
      // @see Drupal\Core\Datetime\Element\DateElementBase.
      '#date_timezone' => '+0000', // new \DateTimezone(DATETIME_STORAGE_TIMEZONE),
    ];

    // #process: bottom-up.
    $info['#process'] = array_merge($parent_info['#process'], $info['#process']);
    // #validate: first OH, then Datetime.
    //$info['#element_validate'] = array_merge($parent_info['#element_validate'], $info['#element_validate']);
    //$info['#element_validate'] = array_merge($info['#element_validate'], $parent_info['#element_validate']);

    return $info + $parent_info;
  }

  /**
   * Callback for office_hours_select element.
   *
   * @param array $element
   * @param mixed $input
   * @param FormStateInterface $form_state
   * @return array|mixed|null
   *
   * Takes the #default_value and dissects it in hours, minutes and ampm indicator.
   * Mimics the date_parse() function.
   *   g = 12-hour format of an hour without leading zeros 1 through 12
   *   G = 24-hour format of an hour without leading zeros 0 through 23
   *   h = 12-hour format of an hour with leading zeros    01 through 12
   *   H = 24-hour format of an hour with leading zeros    00 through 23
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
   * @param FormStateInterface $form_state
   * @param $complete_form
   * @return array
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
   * @param FormStateInterface $form_state
   * @param $complete_form
   */
  public static function validateOfficeHours(&$element, FormStateInterface $form_state, &$complete_form) {
    $input_exists = FALSE;

    // @todo Call validateDatetime().
    // Get the 'time' sub-array.
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
    // Generate the 'object' sub-array.
    $input = parent::valueCallback($element, $input, $form_state);
    if ($input_exists) {
      //if (!empty($input['time']) && !empty($input['object'])) {
      //  parent::validateDatetime($element, $form_state, $complete_form);
      //}
    }
  }

  /**
   * Mimic Core/TypedData/ComplexDataInterface
   */

  /**
   * @todo Use Core/TypedData/ComplexDataInterface.
   *  There are too many similar functions:
   *   - OfficeHoursWidgetBase::massageFormValues();
   *   - OfficeHoursItem, which requires an object;
   *   - this function.
   *
   * @param mixed $element
   *   A string or array for time.
   * @param string $format
   *   Required time format.
   * @return string
   *   Return value.
   */
  public static function get($element, $format = 'Hi') {
    $value = '';
    // Be prepared for Datetime and Numeric input.
    // Numeric input is set in OfficeHoursDateList/Datetime::validateOfficeHours()
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
    if ($element === '-1') { // 24:00
      return TRUE;
    }
    if (isset($element['time']) && $element['time'] === '') {
      return TRUE;
    }
    return FALSE;
  }

}
