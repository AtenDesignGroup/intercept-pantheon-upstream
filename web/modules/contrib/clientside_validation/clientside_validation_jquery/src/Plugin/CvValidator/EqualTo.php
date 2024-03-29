<?php

namespace Drupal\clientside_validation_jquery\Plugin\CvValidator;

use Drupal\clientside_validation\CvValidatorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'equalTo' validator.
 *
 * @CvValidator(
 *   id = "equal_to",
 *   name = @Translation("Equal To"),
 *   supports = {
 *     "attributes" = {"equal_to"}
 *   }
 * )
 */
class EqualTo extends CvValidatorBase {

  /**
   * {@inheritdoc}
   */
  protected function getRules($element, FormStateInterface $form_state) {
    $message = $element['#equal_to_error'] ??
      $this->t('Value in @field does not match.', [
        '@field' => $this->getElementTitle($element),
      ]);

    return [
      'rules' => [
        'equalTo' => '[name="' . $element['#equal_to'] . '"]',
      ],
      'messages' => [
        'equalTo' => $message,
      ],
    ];
  }

}
