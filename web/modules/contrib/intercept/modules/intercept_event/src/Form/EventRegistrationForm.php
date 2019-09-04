<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Event Registration edit forms.
 *
 * @ingroup intercept_event
 */
class EventRegistrationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\intercept_event\Entity\EventRegistration */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Event Registration.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Event Registration.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.event_registration.canonical', ['event_registration' => $entity->id()]);
  }

}
