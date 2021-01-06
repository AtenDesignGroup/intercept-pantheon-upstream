<?php

namespace Drupal\intercept_room_reservation\Controller;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\intercept_core\Controller\ManagementControllerBase;
use Drupal\intercept_core\ReservationManager;
use Drupal\intercept_room_reservation\Form\RoomReservationSettingsForm;
use Symfony\Component\HttpFoundation\Request;

/**
 * The management controller for intercept_room_reservation.
 */
class ManagementController extends ManagementControllerBase {

  /**
   * {@inheritdoc}
   */
  public function alter(array &$build, $page_name) {
    if ($page_name == 'system_configuration') {
      $build['sections']['main']['#actions']['room_reservations'] = [
        '#link' => $this->getManagementButton('Room Reservations', 'room_reservation_configuration'),
        '#weight' => 15,
      ];
    }
  }

  /**
   * Subpage of viewSettings.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return array
   *   The build render array.
   */
  public function viewRoomReservationConfiguration(AccountInterface $user, Request $request) {
    if ($form_key = $request->query->get('view')) {
      $form_object = $this->classResolver
        ->getInstanceFromDefinition(RoomReservationSettingsForm::class)
        ->addAlter(self::class . '::alterEmailReservationSettingsForm');
      $form_state = new FormState();
      $form_state->set('show_form_key', $form_key);
      $form = $this->formBuilder()->buildForm($form_object, $form_state);
      return $form;
    }

    $build['title'] = $this->title('Room Reservations');

    $table = $this->table();
    $table->row($this->getButtonSubpage('reservation_limit', 'Customer Reservation Limit'), 'Set the number of active room reservations a customer may have at any given time.');
    $table->row($this->getButtonSubpage('advanced_reservation_limit', 'Advanced Reservation Limit'), 'Limit how far in advance customers may reserve rooms.');
    $table->row($this->getButtonSubpage('last_reservation_before_closing', 'Last Reservation Before Closing'), 'Set the number of minutes before location closing when room reservations are no longer allowed.');
    $table->row($this->getButtonSubpage('reservation_barred_text', 'Reservation Barred Message'), 'If a customer is barred, this determines the message that he/she will see.');

    $build['sections']['general'] = [
      '#content' => $table->toRenderable(),
    ];

    $build['sections']['taxonomies'] = $this->getTaxonomyVocabularyTable(['meeting_purpose']);

    $table = $this->table();
    $emails = ReservationManager::emails();
    foreach ($emails as $key => $name) {
      $table->row($this->getButtonSubpage($key, $name), '');
    }

    $build['sections']['emails'] = [
      '#title' => $this->t('Reservation emails'),
      '#content' => $table->toRenderable(),
    ];

    return $build;
  }

  /**
   * Alters the email reservation settings form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function alterEmailReservationSettingsForm(array &$form, FormStateInterface $form_state) {
    $show = $form_state->get('show_form_key');
    $children = Element::children($form);
    $skip = ['actions', 'email'];

    $form['email']['#type'] = 'container';
    foreach ($children as $name) {
      if (in_array($name, $skip)) {
        continue;
      }
      if ($name == $show) {
        $form[$name]['#open'] = TRUE;
        continue;
      }
      $form[$name]['#access'] = FALSE;
    }
  }

}
