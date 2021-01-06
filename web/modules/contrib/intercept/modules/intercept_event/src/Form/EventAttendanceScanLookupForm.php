<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\intercept_event\CustomerSearchFormTrait;

/**
 * Form controller for Event Attendance edit forms.
 *
 * @ingroup intercept_event
 */
class EventAttendanceScanLookupForm extends EventAttendanceScanFormBase {

  use CustomerSearchFormTrait;

  /**
   * {@inheritdoc}
   */
  protected function instructionsHeaderText() {
    return $this->t('Library card lookup.');
  }

  /**
   * {@inheritdoc}
   */
  protected function instructionsText() {
    return $this->t('Enter your last name or email to find your library card.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['barcode']['#access'] = FALSE;

    $results = $form_state->getTemporaryValue('results');
    if (isset($results)) {
      $this->buildResultsForm($form, $form_state);
    }
    else {
      $this->buildSearchForm($form, $form_state);
    }
    return $form;
  }

  /**
   * Builds the search form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function buildSearchForm(array &$form, FormStateInterface $form_state) {
    $form['actions']['#access'] = FALSE;

    $form['first_name'] = [
      '#title' => $this->t('First name'),
      '#type' => 'textfield',
    ];

    $form['middle_name'] = [
      '#title' => $this->t('Middle name'),
      '#type' => 'textfield',
    ];

    $form['last_name'] = [
      '#title' => $this->t('Last name'),
      '#type' => 'textfield',
    ];

    $form['email'] = [
      '#title' => $this->t('Email'),
      '#type' => 'textfield',
    ];

    $form['search'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#validate' => ['::search'],
    ];

    $form['cancel'] = $this->cancelButton();
  }

  /**
   * Validation callback for the search form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function search(array $form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    $form_state->setTemporaryValue('results', []);
    if (empty($values['last_name']) && empty($values['email'])) {
      $form_state->setErrorByName('last_name', $this->t('Please provide either a last name or email.'));
      return;
    }
    if ($results = $this->searchQuery($values)) {
      $form_state->setTemporaryValue('results', $results);
    }
    $form_state->setRebuild();
  }

  /**
   * Builds the results form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function buildResultsForm(array &$form, FormStateInterface $form_state) {
    $results = $form_state->getTemporaryValue('results');
    $form['results'] = $this->buildTableElement($results);

    $form['actions']['submit']['#value'] = $this->t('Sign me in');

    $form['cancel'] = $this->cancelButton();

    $form['retry'] = [
      '#type' => 'link',
      '#title' => $this->t('Try another search'),
      '#url' => Url::fromRoute('entity.node.scan_lookup', [
        'node' => $this->event()->id(),
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    if (empty($values['results'])) {
      $form_state->setErrorByName('results', $this->t('You must select an account to sign in.'));
      return;
    }
    $form_state->setValue('barcode', $values['results']);

    $user = $this->createAttendee($values['results']);
    if ($this->attendanceExists($user->id())) {
      $this->setBarcodeError(static::ATTENDANCE_EXISTS_MESSAGE, $form, $form_state);
    }
    else {
      $this->populateAttendance($form, $form_state, $user);
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->redirectToBaseForm($form_state);
  }

}
