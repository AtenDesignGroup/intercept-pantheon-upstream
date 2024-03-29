<?php

namespace Drupal\clientside_validation\Plugin\CvValidator;

use Drupal\clientside_validation\CvValidatorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'required' validator.
 *
 * @CvValidator(
 *   id = "required",
 *   name = @Translation("Required"),
 *   supports = {
 *     "attributes" = {"required", "states"}
 *   }
 * )
 */
class Required extends CvValidatorBase {

  /**
   * An array of conditionally required states.
   *
   * @var array
   */
  protected $states = [
    'required' => 'required',
    'optional' => 'optional',
    '!required' => '!required',
    '!optional' => '!optional',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getRules($element, FormStateInterface $form_state) {
    $is_required = $this->getAttributeValue($element, 'required');

    $states = $this->getAttributeValue($element, 'states') ?: [];

    $is_conditionally_required = FALSE;
    if (is_array($states) && !empty($states)) {
      $is_conditionally_required = array_intersect_key($this->states, $states);
    }

    // Drupal already adds the required attribute, so we don't need to set the
    // required rule.
    if ($is_required || $is_conditionally_required) {
      $message = $element['#required_error'] ??
        $this->t('@title field is required.', [
          '@title' => $this->getElementTitle($element),
        ]);

      return [
        'messages' => [
          'required' => $message,
        ],
      ];
    }
  }

}
