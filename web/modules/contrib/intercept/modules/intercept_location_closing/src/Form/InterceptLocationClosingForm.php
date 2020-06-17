<?php

namespace Drupal\intercept_location_closing\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Location Closing edit forms.
 *
 * @ingroup intercept_location_closing
 */
class InterceptLocationClosingForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // On closing add form, check all locations by default.
    if ($this->entity->isNew()) {
      $location_options = array_keys($form['location']['widget']['#options']);
      $form['location']['widget']['#default_value'] = $location_options;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Location Closing.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Location Closing.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.intercept_location_closing.canonical', ['intercept_location_closing' => $entity->id()]);
  }

}
