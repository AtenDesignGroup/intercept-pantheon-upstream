<?php

namespace Drupal\intercept_event\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\externalauth\ExternalAuth;
use Drupal\user\UserStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Event Attendance edit forms.
 *
 * @ingroup intercept_event
 */
class EventAttendanceScanGuestForm extends EventAttendanceScanFormBase {

  /**
   * {@inheritdoc}
   */
  protected function instructionsHeaderText() {
    return $this->t('Enter your zip code to mark your attendance');
  }

  /**
   * {@inheritdoc}
   */
  protected function instructionsText() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $event = $this->entity->field_event->entity;
    $form['field_guest_zip_code']['widget'][0]['value']['#title_display'] = 'attribute';
    $form['cancel'] = $this->cancelButton();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->redirectToBaseForm($form_state);
  }
}
