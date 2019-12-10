<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for Event Attendance edit forms.
 *
 * @ingroup intercept_event
 */
class EventAttendanceScanForm extends EventAttendanceScanFormBase {

  /**
   * {@inheritdoc}
   */
  protected function instructionsHeaderText() {
    return $this->t('Scan your library card or enter your username');
  }

  /**
   * {@inheritdoc}
   */
  protected function instructionsText() {
    return $this->t('Scanning your library card will connect this event to your account. This helps us provide you with recommendations.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\intercept_event\Entity\EventAttendance */
    $form = parent::buildForm($form, $form_state);
    $event = $this->entity->field_event->entity;

    $form['barcode'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => $this->t('Card Number or Username'),
        'autofocus' => TRUE,
      ],
      '#required' => TRUE,
    ];
    $form['instructions_footer'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['class' => ['instructions-footer']],
      '#value' => $this->t("If you don't know your account number or username, but want to get recommendations, please talk with library staff."),
    ];

    $form['guest'] = [
      '#type' => 'link',
      '#title' => $this->t("Don't have an account? Attend as a guest"),
      '#url' => Url::fromRoute('entity.node.scan_guest', [
        'node' => $event->id(),
      ]),
    ];

    $form['lookup'] = [
      '#type' => 'link',
      '#title' => $this->t("Don't have your library card? Scan in by name or email."),
      '#url' => Url::fromRoute('entity.node.scan_lookup', [
        'node' => $event->id(),
      ]),
    ];

    $form['#attached']['library'][] = 'intercept_event/eventCheckin';
    $form['#attached']['drupalSettings']['eventCheckinMessage'] = $this->t(self::SUCCESS_MESSAGE);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $user = $this->createAttendee($form_state->getValue('barcode'));

    if (!$user) {
      $this->setBarcodeError('Invalid barcode or username.', $form, $form_state);
    }
    elseif ($this->attendanceExists($user->id())) {
      $this->setBarcodeError('You\'ve already scanned in.', $form, $form_state);
    }
    else {
      $this->populateAttendance($form, $form_state, $user);
    }

    return parent::validateForm($form, $form_state);
  }

}
