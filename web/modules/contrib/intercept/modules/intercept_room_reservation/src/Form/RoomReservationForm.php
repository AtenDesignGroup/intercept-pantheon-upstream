<?php

namespace Drupal\intercept_room_reservation\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Room reservation edit forms.
 *
 * @ingroup intercept_room_reservation
 */
class RoomReservationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\intercept_room_reservation\Entity\RoomReservationInterface */
    $entity = $this->entity;
    if ($entity->isNew() && ($room = $this->getRequest()->query->get('room'))) {
      $entity->set('field_room', $room);
    }
    $form = parent::buildForm($form, $form_state);

    $form['#attached'] = [
      'library' => [
        'intercept_room_reservation/room-reservations',
        'intercept_core/delay_keyup',
      ],
    ];

    $form['field_room']['widget'][0]['target_id']['#ajax'] = [
      'callback' => '::checkAvailability',
      'event' => 'autocompleteclose',
      'wrapper' => 'edit-field-dates-0-message',
      'progress' => [
        'type' => 'throbber',
        'message' => t('Verifying reservation dates...'),
      ],
    ];

    $form['field_dates']['widget'][0]['message'] = [
      '#type' => 'item',
    ];
    // Add an ajax callback validating the room reservation availability.
    $form['field_dates']['widget'][0]['value']['#ajax'] = [
      'callback' => '::checkAvailability',
      'disable-refocus' => TRUE,
      'event' => 'blur delayed_keyup',
      'wrapper' => 'edit-field-dates-0-message',
      'progress' => [
        'type' => 'throbber',
        'message' => $this->t('Verifying reservation dates...'),
      ],
    ];
    $form['field_dates']['widget'][0]['end_value']['#ajax'] = [
      'callback' => '::checkAvailability',
      'disable-refocus' => TRUE,
      'event' => 'blur delayed_keyup',
      'wrapper' => 'edit-field-dates-0-message',
      'progress' => [
        'type' => 'throbber',
        'message' => $this->t('Verifying reservation dates...'),
      ],
    ];
    $form['field_dates']['widget'][0]['value']['#attributes']['class'] = [
      'delayed-keyup',
    ];
    $form['field_dates']['widget'][0]['end_value']['#attributes']['class'] = [
      'delayed-keyup',
    ];

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Room reservation.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Room reservation.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.room_reservation.canonical', ['room_reservation' => $entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailability(array &$form, FormStateInterface $form_state) {
    $field_dates = $form_state->getValue('field_dates');
    $field_room = $form_state->getValue('field_room');
    if (($start_date = $field_dates[0]['value']['date']) && ($start_time = $field_dates[0]['value']['time']) && ($end_date = $field_dates[0]['end_value']['date']) && ($end_time = $field_dates[0]['end_value']['time'])) {
      $date_utility = \Drupal::service('intercept_core.utility.dates');
      $start = $date_utility->convertDate($start_date . 'T' . $start_time);
      $end = $date_utility->convertDate($end_date . 'T' . $end_time);
      $reservation = $form_state->getFormObject()->getEntity();
      $reservation_params = [
        'start' => $start->format('Y-m-d\TH:i:s'),
        'end' => $end->format('Y-m-d\TH:i:s'),
        'rooms' => [$field_room[0]['target_id']],
      ];
      if ($reservation->id()) {
        $reservation_params['exclude'] = [$reservation->id()];
      }
      $reservation_manager = \Drupal::service('intercept_core.reservation.manager');
      if ($availability = $reservation_manager->availability($reservation_params)) {
        foreach ($availability as $room_availability) {
          if ($room_availability['has_reservation_conflict']) {
            return ['#markup' => '<div id="edit-field-dates-0-message" style="color:red;">WARNING: It looks like the time that you\'re picking already has a room reservation. Are you sure you want to proceed?</div>'];
          }
        }
      }
    }
    $markup = '<div id="edit-field-dates-0-message"></div>';
    return ['#markup' => $markup];
  }

}
