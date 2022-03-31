<?php

namespace Drupal\intercept_room_reservation\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\BooleanCheckboxWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'intercept_room_reservation_agree_to_terms' field widget.
 *
 * @FieldWidget(
 *   id = "intercept_room_reservation_agree_to_terms",
 *   label = @Translation("Agree to Terms"),
 *   field_types = {
 *     "boolean"
 *   },
 *   multiple_values = FALSE
 * )
 */
class AgreeToTermsWidget extends BooleanCheckboxWidget {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $user = \Drupal::currentUser();
    $roles = $user->getRoles();
    return in_array('intercept_registered_customer', $roles) && $field_definition->getTargetEntityTypeId() === 'room_reservation'
           && $field_definition->getName() === 'field_agreement';
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#required'] = TRUE;
    $element['value']['#title'] = t('I agree');
    $element['value']['#required'] = TRUE;
    $element['value']['#description'] = t('I have read and agree to the <a class="use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800}" href="/room-reservation/terms">terms and conditions</a> for reserving rooms.');

    return $element;
  }

}
