<?php

namespace Drupal\clientside_validation\Plugin\CvValidator;

use Drupal\clientside_validation\CvValidatorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'email' validator.
 *
 * @CvValidator(
 *   id = "email",
 *   name = @Translation("Email"),
 *   supports = {
 *     "types" = {
 *       "email"
 *     }
 *   }
 * )
 */
class Email extends CvValidatorBase {

  /**
   * {@inheritdoc}
   */
  protected function getRules($element, FormStateInterface $form_state) {
    $message = $element['#email_error'] ??
      $this->t('@title does not contain a valid email.', [
        '@title' => $this->getElementTitle($element),
      ]);

    return [
      'messages' => [
        'email' => $message,
      ],
    ];
  }

}
