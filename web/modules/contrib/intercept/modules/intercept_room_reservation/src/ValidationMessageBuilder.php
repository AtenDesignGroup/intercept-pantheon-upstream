<?php

namespace Drupal\intercept_room_reservation;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\intercept_core\Utility\Dates;
use Drupal\intercept_core\ReservationManager;
use Drupal\intercept_ils\ILSManager;
use Drupal\node\Entity\Node;

/**
 * ValidationMessageBuilder service.
 */
class ValidationMessageBuilder {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The Intercept dates utility.
   *
   * @var \Drupal\intercept_core\Utility\Dates
   */
  protected $dateUtility;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The intercept_core.reservation.manager service.
   *
   * @var \Drupal\intercept_core\ReservationManager
   */
  protected $reservationManager;

  /**
   * ILS client object.
   *
   * @var object
   */
  private $client;

  /**
   * ILS plugin object.
   *
   * @var object
   */
  protected $interceptILSPlugin;

  /**
   * Constructs a ValidationMessageBuilder object.
   *
   * @param \Drupal\example\ExampleInterface $intercept_core_reservation_manager
   *   The intercept_core.reservation.manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\intercept_core\Utility\Dates $intercept_core_utility_dates
   *   The intercept_core.utility.dates service.
   * @param \Drupal\intercept_core\Utility\Dates $dateUtility
   *   The Intercept dates utility.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\intercept_ils\ILSManager $ils_manager
   *   The intercept_ils ILS manager service.
   */
  public function __construct(ReservationManager $reservationManager, EntityTypeManagerInterface $entityTypeManager, Dates $dateUtility, AccountProxy $currentUser, ConfigFactoryInterface $config_factory, ILSManager $ils_manager) {
    $this->reservationManager = $reservationManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->dateUtility = $dateUtility;
    $this->currentUser = $currentUser;
    $settings = $config_factory->get('intercept_ils.settings');
    $intercept_ils_plugin = $settings->get('intercept_ils_plugin', '');
    if ($intercept_ils_plugin) {
      $this->interceptILSPlugin = $ils_manager->createInstance($intercept_ils_plugin);
      $this->client = $this->interceptILSPlugin->getClient();
    }
  }

  /**
   * Runs availability validation and triggers an update event in the browser.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An array of Ajax commands.
   */
  public function availabilityCallback(array &$form, FormStateInterface $form_state) {
    $messages = [];
    $response = new AjaxResponse();
    $reservationParams = $this->getReservationParams($form_state);
    $reservationParams['entity'] = $form_state->getFormObject()->getEntity();

    $markup = NULL;
    $validationMessages = $this->checkAvailability($reservationParams);
    if (!empty($validationMessages)) {
      $messages = [];
      foreach (array_filter($validationMessages) as $key => $validationMessage) {
        if (!empty($validationMessage)) {
          $messages[] = $validationMessage;
        }
      }
    }
    $markup = $this->wrapAvailabilityValidationError($messages);
    $command = new ReplaceCommand('[id^="edit-field-dates-0-message"]', $markup);
    $response->addCommand($command);

    // Trigger the intercept:updateRoomReservation event.
    if (isset($reservationParams['start']) && isset($reservationParams['end'])) {
      $reservation = $reservationParams['entity'];
      $reservationParams['id'] = $reservation->uuid();
      $reservationParams['start'] = $reservationParams['start']->format(\DateTime::RFC3339);
      $reservationParams['end'] = $reservationParams['end']->format(\DateTime::RFC3339);

      if ($reservation->hasField('field_room') && !$reservation->field_room->isEmpty() && $room = $form_state->getValue('field_room')[0]) {
        $reservation->set('field_room', $room['target_id']);
        $reservationParams['room'] = $reservation->field_room->entity->uuid();
      }
      $command = new InvokeCommand('html', 'trigger', [
        'intercept:updateRoomReservation', $reservationParams,
      ]);
      $response->addCommand($command);
    }

    return $response;
  }

  /**
   * Runs user validation and triggers an update event in the browser.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An array of Ajax commands.
   */
  public function userCallback(array &$form, FormStateInterface $form_state) {
    $messages = [];
    $response = new AjaxResponse();
    $markup = NULL;
    $validationMessages = [];

    $field_user = $form_state->getValue('field_user');
    if (isset($field_user[0]['target_id'])) {
      $uid = $field_user[0]['target_id'];
      $validationMessages = $this->checkUser($uid);
    }
    if (!empty($validationMessages)) {
      $messages = [];
      foreach (array_filter($validationMessages) as $key => $validationMessage) {
        if (!empty($validationMessage)) {
          $messages[] = $validationMessage;
        }
      }
    }
    $markup = $this->wrapUserValidationError($messages);
    $command = new ReplaceCommand('[id^="edit-field-user-0-message"]', $markup);
    $response->addCommand($command);

    return $response;
  }

  /**
   * Runs attendee count validation and triggers an update event in the browser.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An array of Ajax commands.
   */
  public function attendeeCountCallback(array &$form, FormStateInterface $form_state) {
    $messages = [];
    $response = new AjaxResponse();
    $markup = NULL;
    $validationMessages = [];

    $field_attendee_count = $form_state->getValue('field_attendee_count');
    if (isset($field_attendee_count[0]['value'])) {
      $attendee_count = $field_attendee_count[0]['value'];

      // Get the room value from the form_state.
      $field_room = $form_state->getValue('field_room');
      if (isset($field_room[0]['target_id'])) {
        // Get the min and max capacity values from the room node.
        $room_node = Node::load($field_room[0]['target_id']);
        $field_capacity_min = $room_node->get('field_capacity_min')->getString();
        $field_capacity_max = $room_node->get('field_capacity_max')->getString();
        $validationMessages = $this->checkAttendeeCount($attendee_count, $field_capacity_min, $field_capacity_max);
      }
    }
    if (!empty($validationMessages)) {
      $messages = [];
      foreach (array_filter($validationMessages) as $key => $validationMessage) {
        if (!empty($validationMessage)) {
          $messages[] = $validationMessage;
        }
      }
    }
    $markup = $this->wrapAttendeeCountValidationError($messages);
    $command = new ReplaceCommand('[id^="edit-field-attendee-count-0-message"]', $markup);
    $response->addCommand($command);

    return $response;
  }

  /**
   * Checks to see if the given resource is available at the current time.
   *
   * @param array $reservationParams
   *   Contains DrupalDateTime $start, DrupalDateTime $end, Node $room.
   *
   * @return array
   *   Array of zero or more validation errors to be inserted into render array.
   */
  public function checkAvailability(array $reservationParams) {
    if (empty($reservationParams['room'])) {
      return [];
    }

    $messages = [];
    $reservationParams['start'] = $reservationParams['start']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $reservationParams['end'] = $reservationParams['end']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $reservation = $reservationParams['entity'];
    // Don't evaluate conflicts between this $reservation and itself.
    if ($reservation->id()) {
      $reservationParams['exclude'] = [$reservation->id()];
    }

    if ($availability = $this->reservationManager->availability($reservationParams)) {
      // Customize open hours conflict message based on the current user's roles.
      $openHoursConflictMessage = ($this->currentUser->hasPermission('bypass room reservation open hours constraints'))
        ? 'WARNING: You are reserving a closed space. Please verify dates and times before saving.'
        : 'WARNING: You are reserving a closed space. You will not be allowed to proceed until you update it to an available date and time.';

      // Customize max duration message based on the current user's roles.
      $maxDurationConflictMessage = ($this->currentUser->hasPermission('bypass room reservation maximum duration constraints'))
        ? 'WARNING: Your desired reservation exceeds this room\'s maximum reservation duration. Please verify dates and times before saving.'
        : 'WARNING: Your desired reservation exceeds this room\'s maximum reservation duration. You will not be allowed to proceed until you update it to an available date and time.';

      $conflictTypes = [
        'has_reservation_conflict' => 'WARNING: It looks like the time that you\'re picking already has a room reservation. You will not be allowed to proceed until you update it to an available date and time.',
        'has_open_hours_conflict' => $openHoursConflictMessage,
        'has_max_duration_conflict' => $maxDurationConflictMessage,
      ];

      foreach ($availability as $roomAvailability) {
        foreach ($conflictTypes as $conflictType => $message) {
          $messages[$conflictType] = ($roomAvailability[$conflictType])
            ? $message : '';
        }
      }
    }

    return $messages;
  }

  /**
   * Checks to see details of the customer being selected for the reservation.
   *
   * @param string $uid
   *   Contains the user id to check.
   *
   * @return array
   *   Array of zero or more validation errors to be inserted into render array.
   */
  public function checkUser($uid) {
    $messages = [];
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    // Get current reservations for this user.
    if ($reservations = $this->reservationManager->getUserReservations($user)) {
      if (!empty($reservations)) {
        $messages['has_existing_reservation'] = 'WARNING: This customer has an existing room reservation.';
      }
    }
    if ($this->client && $patron = $this->client->patron->getByUser($user)) {
      // Get notes for this user.
      $notes = $patron->getNotes();
      if (isset($notes->NonBlockingStatusNotes) && !empty($notes->NonBlockingStatusNotes)) {
        $messages['notes_non_blocking'] = 'Notes: ' . $this->nl2br2($notes->NonBlockingStatusNotes);
      }
      if (isset($notes->BlockingStatusNotes) && !empty($notes->BlockingStatusNotes)) {
        $messages['notes_blocking'] = 'BLOCKING NOTES: ' . $this->nl2br2($notes->BlockingStatusNotes);
      }
      // Check expiration date.
      if (method_exists($patron, 'circulateBlocksGet')) {
        $circulation_blocks = $patron->circulateBlocksGet();
        preg_match('/\/Date\((-*)(\d+)(-\d+)\)\//', $circulation_blocks->ExpirationDate, $date);
        $expiration_date = $date[1] . $date[2]/1000;
        if ($expiration_date < time()) { // This card is expired.
          $messages['expired_account'] = "WARNING: This customer's account has expired.";
        }
        // For debugging:
        // elseif ($expiration_date) {
        //   $messages['debug'] = "This customer's account will expire on " . date('m-d-Y', $expiration_date) . ".";
        // }
      }
    }

    return $messages;
  }

  /**
   * Replaces all linebreaks with <br>
   * 
   * @param str $string
   *   The string to be replaced.
   */
  public function nl2br2($string) {
    $string = str_replace(["\r\n", "\r", "\n"], "<br>", $string);
    return $string;
  }

  /**
   * Checks to see if the attendee count requested for the reservation is in
   * line with the specified min/max attendees of the room.
   *
   * @param string $attendee_count
   *   Contains the user id to check.
   * @param string $field_capacity_min
   *   The minimum capacity of the selected room.
   * @param string $field_capacity_max
   *   The maximum capacity of the selected room.
   *
   * @return array
   *   Array of zero or more validation errors to be inserted into render array.
   */
  public function checkAttendeeCount($attendee_count, $field_capacity_min, $field_capacity_max) {
    $messages = [];

    if ($field_capacity_min == 1 && $field_capacity_max == 1 && $attendee_count != 1) {
      $messages['attendee_count'] = 'WARNING: This room is designated for only 1 person at a time.';
    }
    elseif ($attendee_count < $field_capacity_min || $attendee_count > $field_capacity_max) {
      $messages['attendee_count'] = "WARNING: This room is designated for groups of $field_capacity_min-$field_capacity_max people.";
    }
    // else {
    //   $messages['attendee_count'] = 'This is a good number of attendees for capacity.';
    // }
    
    return $messages;
  }

  /**
   * Gets the base reservation parameters.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   An array of room, and date values.
   */
  public function getReservationParams(FormStateInterface $form_state) {
    $reservationParams = [];

    $field_room = $form_state->getValue('field_room');
    if (isset($field_room[0]['target_id'])) {
      $reservationParams['room'] = $field_room[0]['target_id'];
      $reservationParams['rooms'] = [$field_room[0]['target_id']];
    }

    $field_dates = $form_state->getUserInput()['field_dates'];
    if (($start_date = $field_dates[0]['value']['date']) && ($start_time = $field_dates[0]['value']['time'])) {
      $reservationParams['start'] = $this->dateUtility->convertDate($start_date . 'T' . $start_time);
    }

    if (($end_date = $field_dates[0]['end_value']['date']) && ($end_time = $field_dates[0]['end_value']['time'])) {
      $reservationParams['end'] = $this->dateUtility->convertDate($end_date . 'T' . $end_time);
    }

    return $reservationParams;
  }

  /**
   * Creates a render array from the $messages returned by checkAvailability().
   */
  public function wrapAvailabilityValidationError($messages) {
    if (empty($messages)) {
      return [
        '#type' => 'html_tag',
        '#attributes' => [
          'id' => 'edit-field-dates-0-message',
        ],
        '#tag' => 'div',
        'child' => [
          '#type' => 'intercept_field_error_message',
          '#message' => '',
        ],
      ];
    }
    if (count($messages) > 1) {
      $item = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $messages,
        '#attributes' => [
          'id' => 'edit-field-dates-0-message',
        ],
        '#wrapper_attributes' => ['class' => 'container'],
      ];
    }
    else {
      $item = [
        '#type' => 'html_tag',
        '#attributes' => [
          'id' => 'edit-field-dates-0-message',
        ],
        '#tag' => 'div',
        'child' => [
          '#type' => 'intercept_field_error_message',
          '#message' => reset($messages),
        ],
      ];

    }
    return $item;
  }

  /**
   * Creates a render array from the $messages returned by checkUser().
   */
  public function wrapUserValidationError($messages) {
    if (empty($messages)) {
      return [
        '#type' => 'html_tag',
        '#attributes' => [
          'id' => 'edit-field-user-0-message',
        ],
        '#tag' => 'div',
        'child' => [
          '#type' => 'intercept_field_error_message',
          '#message' => '',
        ],
      ];
    }
    if (count($messages) > 1) {
      $item = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $messages,
        '#attributes' => [
          'id' => 'edit-field-user-0-message',
        ],
        '#wrapper_attributes' => ['class' => 'container'],
      ];
    }
    else {
      $item = [
        '#type' => 'html_tag',
        '#attributes' => [
          'id' => 'edit-field-user-0-message',
        ],
        '#tag' => 'div',
        'child' => [
          '#type' => 'intercept_field_error_message',
          '#message' => reset($messages),
        ],
      ];
    }
    return $item;
  }

  /**
   * Creates a render array from the $messages returned by checkUser().
   */
  public function wrapAttendeeCountValidationError($messages) {
    if (empty($messages)) {
      return [
        '#type' => 'html_tag',
        '#attributes' => [
          'id' => 'edit-field-attendee-count-0-message',
        ],
        '#tag' => 'div',
        'child' => [
          '#type' => 'intercept_field_error_message',
          '#message' => '',
        ],
      ];
    }
    if (count($messages) > 1) {
      $item = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $messages,
        '#attributes' => [
          'id' => 'edit-field-attendee-count-0-message',
        ],
        '#wrapper_attributes' => ['class' => 'container'],
      ];
    }
    else {
      $item = [
        '#type' => 'html_tag',
        '#attributes' => [
          'id' => 'edit-field-attendee-count-0-message',
        ],
        '#tag' => 'div',
        'child' => [
          '#type' => 'intercept_field_error_message',
          '#message' => reset($messages),
        ],
      ];
    }
    return $item;
  }

}
