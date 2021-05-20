<?php

namespace Drupal\intercept_guest\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the content_entity_example entity edit forms.
 *
 * @ingroup content_entity_example
 */
class InterceptGuestForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\intercept_guest\Entity\InterceptGuest $entity */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    // Set and hide the user_id (owner) field.
    $form_state->setValue('user_id', $this->currentUser()->Id());
    $form['user_id']['#access'] = FALSE;

    $form['langcode'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.intercept_guest.collection');
    $entity = $this->getEntity();
    $entity->save();
  }

}
