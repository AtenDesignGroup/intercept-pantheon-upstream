<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Event Attendance edit forms.
 *
 * @ingroup intercept_event
 */
class EventAttendanceForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\intercept_event\Entity\EventAttendance */
    $form = parent::buildForm($form, $form_state);

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
        \Drupal::messenger()->addMessage($this->t('Created the %label Event Attendance.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Event Attendance.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.event_attendance.canonical', ['event_attendance' => $entity->id()]);
  }

}
