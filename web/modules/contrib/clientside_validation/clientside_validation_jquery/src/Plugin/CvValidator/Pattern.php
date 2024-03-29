<?php

namespace Drupal\clientside_validation_jquery\Plugin\CvValidator;

use Drupal\clientside_validation\CvValidatorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'pattern' validator.
 *
 * @CvValidator(
 *   id = "pattern",
 *   name = @Translation("Pattern"),
 *   supports = {
 *     "attributes" = {"pattern"}
 *   },
 *   attachments = {
 *     "library" = {"clientside_validation_jquery/cv.pattern.method"}
 *   }
 * )
 */
class Pattern extends CvValidatorBase {

  /**
   * {@inheritdoc}
   */
  protected function getRules($element, FormStateInterface $form_state) {
    $message = $element['#pattern_error'] ??
      $this->t('@title does not meet the requirements.', [
        '@title' => $this->getElementTitle($element),
      ]);

    return [
      'messages' => [
        'pattern' => $message,
      ],
    ];
  }

}
