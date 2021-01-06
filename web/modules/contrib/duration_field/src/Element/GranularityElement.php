<?php

namespace Drupal\duration_field\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides the Granularity form element.
 *
 * This form element takes a granularity string as its input, and outputs a
 * granularity string upon submission. See GranularityStringData for information
 * about granularity strings.
 *
 * @FormElement("granularity")
 *
 * @see \Drupal\Plugin\DataType\GranularityStringData
 */
class GranularityElement extends FormElement {

  use ProcessStatesTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#element_validate' => [
        [$class, 'validateElement'],
      ],
      '#pre_render' => [
        [$class, 'preRenderElement'],
      ],
      '#process' => [
        'Drupal\Core\Render\Element\RenderElement::processAjaxForm',
        [$class, 'processElement'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {

    if ($input !== FALSE && !is_null($input)) {
      return \Drupal::service('duration_field.granularity.service')->convertGranularityArrayToGranularityString($input);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function processElement(&$element, FormStateInterface $form_state, &$complete_form) {

    $value = FALSE;
    if (isset($element['#value']) && $element['#value']) {
      $value = $element['#value'];
    }
    elseif (isset($element['#default_value']) && $element['#default_value']) {
      $value = $element['#default_value'];
    }
    $granularities = explode(':', $value);

    $time_mappings = [
      'y' => t('Years'),
      'm' => t('Months'),
      'd' => t('Days'),
      'h' => t('Hours'),
      'i' => t('Minutes'),
      's' => t('Seconds'),
    ];

    // Create a wrapper for the elements. This is done in this manner
    // rather than nesting the elements in a wrapper with #prefix and #suffix
    // so as to not end up with [wrapper] as part of the name attribute
    // of the elements.
    $div = '<div';
    $classes = ['granularity-inner-wrapper'];
    if (!empty($element['#states'])) {
      self::processStates($element);
    }
    foreach ($element['#attributes'] as $attribute => $attribute_value) {
      if (is_string($attribute_value)) {
        $div .= ' ' . $attribute . "='" . $attribute_value . "'";
      }
      elseif ($attribute == 'class') {
        $classes = array_merge($classes, $attribute_value);
      }
    }
    $div .= ' class="' . implode(' ', $classes) . '"';
    $div .= '>';

    $element['wrapper_open'] = [
      '#markup' => $div,
      '#weight' => -1,
    ];

    foreach ($time_mappings as $key => $label) {
      $element[$key] = [
        '#id' => $element['#id'] . '-' . $key,
        '#type' => 'checkbox',
        '#title' => $label,
        // Elements included in the #value or #default_value will be checked.
        '#default_value' => in_array($key, $granularities),
        '#weight' => 0,
        '#min' => 0,
      ];

      if (!empty($element['#ajax'])) {
        $element[$key]['#ajax'] = $element['#ajax'];
      }
    }

    $element['wrapper_close'] = [
      '#markup' => '</div>',
      '#weight' => 1,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderElement(array $element) {

    // Set the wrapper as a container to the inner values.
    $element['#attributes']['type'] = 'container';

    Element::setAttributes($element, ['id', 'name', 'value']);
    static::setAttributes($element, ['form-granularity']);

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Converts the submitted values array to a granularity string, and sets the
   * string as the value of the form element. All handlers after this point will
   * receive the string as the value for the form element.
   */
  public static function validateElement(&$element, FormStateInterface $form_state, $form) {
    $form_state->setValueForElement($element, \Drupal::service('duration_field.granularity.service')->convertGranularityArrayToGranularityString($form_state->getValue($element['#parents'])));
  }

}
