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

    // TODO: Move to intercept_base theme.
    $form['customer_first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer First Name'),
    ];
    $form['customer_last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer Last Name'),
    ];
    $form['customer_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer Email'),
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
          'message' => t('Searching ILS...'),
        ],
      ],
    ];
    $values = $form_state->getUserInput();
    $results = $this->searchQuery($this->mapValues($values));
    $form['results'] = $this->buildTableElement($results);
    $form['results']['#attributes'] = ['id' => ['edit-results']];

    return $form;
  }

  public static function searchAjax(array &$form, FormStateInterface $form_state) {
    return $form['results'];
  }

  protected function map() {
    return [
      'customer_first_name' => 'first_name',
      'customer_last_name' => 'last_name',
      'customer_email' => 'email',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $values = $form_state->cleanValues()->getValues();
    if (!empty($values['results'])) {
      $user = \Drupal::service('intercept_ils.mapping_manager')->loadByBarcode($values['results']);
      if ($user) {
        $entity->field_user->setValue($user->id());
      }
    }
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
