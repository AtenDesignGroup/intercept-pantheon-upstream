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

  use CustomerSearchFormTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($event = $this->getRouteMatch()->getParameter('node')) {
      $this->entity->field_event->setValue($event);
    }
    /* @var $entity \Drupal\intercept_event\Entity\EventRegistration */
    $form = parent::buildForm($form, $form_state);

    $form['#theme'] = 'event_registration_event_form';

    $form['customer_barcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Number'),
      '#weight' => '-1',
    ];
    $form['customer_first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer First Name'),
      '#weight' => '-1',
    ];
    $form['customer_last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer Last Name'),
      '#weight' => '-1',
    ];
    $form['customer_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer Email'),
      '#weight' => '-1',
    ];
    $form['lookup'] = [
      '#value' => $this->t('Search customer'),
      '#type' => 'button',
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => static::class . '::searchAjax',
        'event' => 'click',
        'wrapper' => 'edit-results',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Searching ILS...'),
        ],
      ],
      '#weight' => '-1',
    ];
    $values = $form_state->getUserInput();
    $results = $this->searchQuery($this->mapValues($values));
    $form['results'] = $this->buildTableElement($results);
    $form['results']['#attributes'] = ['id' => ['edit-results']];
    $form['results']['#weight'] = '-1';

    return $form;
  }

  /**
   * Returns the results table element.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The results table element.
   */
  public static function searchAjax(array &$form, FormStateInterface $form_state) {
    return $form['results'];
  }

  /**
   * Maps customer values.
   *
   * @return array
   *   The customer value array.
   */
  protected function map() {
    return [
      'customer_first_name' => 'first_name',
      'customer_last_name' => 'last_name',
      'customer_email' => 'email',
      'customer_barcode' => 'barcode',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    $values = $form_state->cleanValues()->getValues();
    // Set the field_user value from the ILS mapping before constraint validation.
    if (!empty($values['results'])) {
      $user = \Drupal::service('intercept_ils.mapping_manager')->loadByBarcode($values['results']);
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
