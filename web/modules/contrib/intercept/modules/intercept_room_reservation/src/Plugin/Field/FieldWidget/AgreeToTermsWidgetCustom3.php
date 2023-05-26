<?php

namespace Drupal\intercept_room_reservation\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\BooleanCheckboxWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'intercept_room_reservation_agree_to_terms_custom3' field widget.
 *
 * @FieldWidget(
 *   id = "intercept_room_reservation_agree_to_terms_custom3",
 *   label = @Translation("Agree to Terms - Custom 3"),
 *   field_types = {
 *     "boolean"
 *   },
 *   multiple_values = FALSE
 * )
 */
class AgreeToTermsWidgetCustom3 extends BooleanCheckboxWidget {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $user = \Drupal::currentUser();
    $roles = $user->getRoles();
    return (in_array('intercept_registered_customer', $roles) || in_array('intercept_staff', $roles)) && $field_definition->getTargetEntityTypeId() === 'room_reservation' && $field_definition->getName() === 'field_agreement_custom3';
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#required'] = TRUE;
    $element['value']['#title'] = t('Delivery of direct, hands-on healthcare and wellness <br>services, including examinations, hands-on demos, <br>or treatments');
    $element['value']['#required'] = TRUE;

    return $element;
  }

}
