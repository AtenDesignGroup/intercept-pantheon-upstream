<?php

namespace Drupal\intercept_room_reservation\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\BooleanCheckboxWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'intercept_room_reservation_agree_to_terms_custom2' field widget.
 *
 * @FieldWidget(
 *   id = "intercept_room_reservation_agree_to_terms_custom2",
 *   label = @Translation("Agree to Terms - Custom 2"),
 *   field_types = {
 *     "boolean"
 *   },
 *   multiple_values = FALSE
 * )
 */
class AgreeToTermsWidgetCustom2 extends BooleanCheckboxWidget {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'room_reservation' && $field_definition->getName() === 'field_agreement_custom2';
  }

  /**
   * Determine if the user must agree to the terms.
   */
  private function mustAgree() {
    $user = \Drupal::currentUser();
    $roles = $user->getRoles();
    return in_array('intercept_registered_customer', $roles);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if (!$this->mustAgree()) {
      $element['#access'] = FALSE;
      return $element;
    }

    $element['#required'] = TRUE;
    $element['value']['#title'] = t('Conducting open call interviews, auditions or <br>rehearsals');
    $element['value']['#required'] = TRUE;

    return $element;
  }

}
