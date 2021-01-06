<?php

namespace Drupal\intercept_core\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides a form element for a drop-down menu or scrolling selection box.
 *
 * Properties:
 * - #min: (optional) The minimum time to begin the select options. Defaults to '0000'.
 * - #max: (optional) The maximum time to end the select options. Defaults to '2400'.
 * - #step: (optional) The intervals in minutes between options. Defaults to 15.
 * - #empty_option: (optional) The label to show for the first default option.
 *   By default, the label is automatically set to "- Select -" for a required
 *   field and "- None -" for an optional field.
 * - #format: The PHP Date format for the option labels. Defaults to 'g:i a',
 * - #empty_value: (optional) The value for the first default option, which is
 *   used to determine whether the user submitted a value or not.
 *   - If #required is TRUE, this defaults to '' (an empty string).
 *   - If #required is not TRUE and this value isn't set, then no extra option
 *     is added to the select control, leaving the control in a slightly
 *     illogical state, because there's no way for the user to select nothing,
 *     since all user agents automatically preselect the first available
 *     option. But people are used to this being the behavior of select
 *     controls.
 *     - @todo Address the above issue in Drupal 8.
 *   - If #required is not TRUE and this value is set (most commonly to an
 *     empty string), then an extra option (see #empty_option above)
 *     representing a "non-selection" is added with this as its value.
 * - #multiple: (optional) Indicates whether one or more options can be
 *   selected. Defaults to FALSE.
 * - #default_value: Must be NULL or not set in case there is no value for the
 *   element yet, in which case a first default option is inserted by default.
 *   Whether this first option is a valid option depends on whether the field
 *   is #required or not.
 * - #required: (optional) Whether the user needs to select an option (TRUE)
 *   or not (FALSE). Defaults to FALSE.
 *
 * Usage example:
 * @code
 * $form['example_time_select'] = [
 *   '#type' => 'time_select',
 *   '#title' => $this->t('Time Select element'),
 *   '#min' => '1000',
 *   '#max' => '1600',
 *   '#step' => 30
 * ];
 * @endcode
 *
 * @FormElement("time_select")
 */
class TimeSelect extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#sort_options' => FALSE,
      '#sort_start' => NULL,
      '#min' => '0000',
      '#max' => '2400',
      '#format' => 'g:i a',
      '#step' => 15,
      '#process' => [
        [$class, 'processTimeSelect'],
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderSelect'],
      ],
      '#theme' => 'select',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Gets the dropdown label for a time.
   */
  private static function getLabel(string $hours, string $minutes, string $format = 'g:i a') {
    $date = new DrupalDateTime();
    $date->setTime($hours, $minutes);

    return $date->format($format);
  }

  /**
   * Returns the select options.
   */
  private static function getOptions(string $format, string $min = '0000', string $max = '2400', int $step = 15) {
    $options = [];

    // Abort if the min time is after the max time to avoid an infinite loop.
    if ($min >= $max) {
      return $options;
    }

    $min_hour = substr($min, 0, 2);
    $min_minute = substr($min, -2);

    if ($max === '2400') {
      $max_hour = 23;
      $max_minute = 59;
    }
    else {
      $max_hour = substr($max, 0, 2);
      $max_minute = substr($max, -2);
    }

    $hour = $min_hour;
    $minute = $min_minute;

    while ($max_hour >= $hour) {
      $minute_limit = $max_hour == $hour ? $max_minute : 59;

      while ($minute_limit >= $minute) {
        $options[DrupalDateTime::datePad($hour) . ':' . DrupalDateTime::datePad($minute) . ':00'] = self::getLabel($hour, $minute, $format);
        $minute += $step;
      }

      $minute = 0;
      $hour++;
    }

    return $options;
  }

  /**
   * Processes a select list form element.
   *
   * This process callback is mandatory for select fields, since all user agents
   * automatically preselect the first available option of single (non-multiple)
   * select lists.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   *
   * @see _form_validate()
   */
  public static function processTimeSelect(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['#options'] = self::getOptions($element['#format'], $element['#min'], $element['#max'], $element['#step']);
    parent::processSelect($element, $form_state, $complete_form);
    return $element;
  }

}
