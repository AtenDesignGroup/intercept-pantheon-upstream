<?php

namespace Drupal\intercept_room_reservation\Controller;

use Drupal\intercept_core\Controller\ManagementControllerBase;
use Drupal\intercept_room_reservation\Form\RoomReservationSettingsForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

class ManagementController extends ManagementControllerBase {

  public function alter(array &$build, $page_name) {
    if ($page_name == 'system_configuration') {
      $build['sections']['main']['#actions']['room_reservations'] = [
        '#link' => $this->getManagementButton('Room Reservations', 'room_reservation_configuration'),
        '#weight' => 15,
      ];
    }
    if ($page_name == 'default') {
      $build['sections']['main']['#actions']['room'] = [
        '#link' => $this->getButton('Reserve a Room', 'intercept_room_reservation.reserve_room'),
        '#weight' => -20,
      ];
    }
  }

  public function viewRoomReservations(AccountInterface $user, Request $request) {
    return [
      '#type' => 'view',
      '#name' => 'intercept_room_reservations',
      '#display_id' => 'embed',
    ];
  }

  public function viewRoomReservationConfiguration(AccountInterface $user, Request $request) {
    // This can also be moved into a separate function, for example:
    // 'reservation_canceled' - viewRoomReservationConfigurationReservationCanceled()
    if ($form_key = $request->query->get('view')) {
      $form_object = $this->classResolver
        ->getInstanceFromDefinition(RoomReservationSettingsForm::class)
        ->addAlter(self::class . '::alterEmailReservationSettingsForm');
      $form_state = new \Drupal\Core\Form\FormState();
      $form_state->set('show_form_key', $form_key);
      $form = $this->formBuilder()->buildForm($form_object, $form_state);
      return $form;
    }

    $build['title'] = $this->title('Room Reservations');

    $table = $this->table();
    $table->row($this->getButtonSubpage('reservation_limit', 'Customer Reservation Limit'), 'Set the number of active room reservations a customer may have at any given time.');
    $table->row($this->getButtonSubpage('advanced_reservation_limit', 'Advanced Reservation Limit'), 'Limit how far in advance customers may reserve rooms.');

    $build['sections']['general'] = [
      '#content' => $table->toRenderable(),
    ];

    $build['sections']['taxonomies'] = $this->getTaxonomyVocabularyTable(['meeting_purpose']);

    $table = $this->table();
    $emails = \Drupal\intercept_core\ReservationManager::emails();
    foreach ($emails as $key => $name) {
      $table->row($this->getButtonSubpage($key, $name), '');
    }

    $build['sections']['emails'] = [
      '#title' => $this->t('Reservation emails'),
      '#content' => $table->toRenderable(),
    ];

    return $build;
  }

  public static function alterEmailReservationSettingsForm(array &$form, FormStateInterface $form_state) {
    $show = $form_state->get('show_form_key');
    $children = \Drupal\Core\Render\Element::children($form);
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
