<?php

namespace Drupal\intercept_room_reservation\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\BooleanCheckboxWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'intercept_room_reservation_agree_to_terms_custom1' field widget.
 *
 * @FieldWidget(
 *   id = "intercept_room_reservation_agree_to_terms_custom1",
 *   label = @Translation("Agree to Terms - Custom 1"),
 *   field_types = {
 *     "boolean"
 *   },
 *   multiple_values = FALSE
 * )
 */
class AgreeToTermsWidgetCustom1 extends BooleanCheckboxWidget {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'room_reservation' && $field_definition->getName() === 'field_agreement_custom1';
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

    $element['#prefix'] = '<h5>' . t('Terms of Service') . '</h5>' .
    '<p><b>' . t('Please note all meetings are open to the public. Rooms will only be held for 30 minutes after the reservation start-time and then will be released for use by other customers.') . '</b></p>' .
    '<p>' . t('I have read and agree to the <a class="use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800}" href="/room-reservation/terms">Terms of Service</a>, including but not limited to Library spaces not being used for:') . '</p>';
    $element['#allowed_tags'] = ['p', 'span', 'legend', 'fieldset'];
    $element['#required'] = TRUE;
    $element['value']['#title'] = t('Groups soliciting, selling, charging admission or <br>asking for donations');
    $element['value']['#required'] = TRUE;

    return $element;
  }

}
