<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_event\CustomerSearchFormTrait;

/**
 * Form controller for Event Registration edit forms.
 *
 * @ingroup intercept_event
 */
class EventRegistrationEventForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($event = $this->getRouteMatch()->getParameter('node')) {
      $this->entity->field_event->setValue($event);
    }
    /** @var \Drupal\intercept_event\Entity\EventRegistration $entity */
    $form = parent::buildForm($form, $form_state);

    $form['#theme'] = 'event_registration_event_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    $values = $form_state->cleanValues()->getValues();
    // Set the field_user value from the ILS mapping before constraint validation.
    if (!empty($values['results'])) {
      $user = \Drupal::service('intercept_ils.association_manager')->loadByBarcode($values['results']);
      if ($user) {
        $entity->field_user->setValue($user->id());
      }
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label Event Registration.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Event Registration.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.event_registration.canonical', ['event_registration' => $entity->id()]);
  }

}
