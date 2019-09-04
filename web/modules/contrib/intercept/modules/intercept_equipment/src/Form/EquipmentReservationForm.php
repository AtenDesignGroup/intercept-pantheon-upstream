<?php

namespace Drupal\intercept_equipment\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Form controller for Equipment reservation edit forms.
 *
 * @ingroup intercept_equipment_reservation
 */
class EquipmentReservationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\intercept_equipment\Entity\EquipmentReservation */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }

    // Use javascript to show a section on the form with all of the current reservations for the equipment item that they picked in step 1.
    // See: https://www.drupal.org/docs/8/api/javascript-api/ajax-forms
    // See also: equipmentAvailabilityView() function below.
    $form['check'] = array(
      '#type' => 'button',
      '#value' => $this->t('Check Availability'),
      '#limit_validation_errors' => [], // Hides unecessary validation errors from the view output.
      '#ajax' => [
        'callback' => 'Drupal\intercept_equipment\Form\EquipmentReservationForm::equipmentAvailabilityView',
        'event' => 'click',
        'wrapper' => 'edit-output',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Checking availability...'),
        ],
      ],
    );
    // Container for output of equipment AJAX availability view.
    $form['container']['output'] = [
      '#markup' => '<h2 id="edit-output"></h2>',
    ];

    // Pre-fill the user field with the current user's information.
    if (empty($form['field_user']['widget'][0]['target_id']['#default_value'])) {
      $current_user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
      $form['field_user']['widget'][0]['target_id']['#default_value'] = $current_user;
    }
    // Pre-fill the equipment field if it's in the query string params.
    $equipment_nid = \Drupal::request()->query->get('id');
    if (!empty($equipment_nid) && empty($form['field_equipment']['widget'][0]['target_id']['#default_value'])) {
      $form['field_equipment']['widget'][0]['target_id']['#default_value'] = Node::load($equipment_nid);
    }

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    // DEBUG
    //$form_state->setErrorByName('field_equipment', t('DEBUG'));
    
    // Reservation Fields:
    // field_dates, field_equipment, field_event, field_location, field_room
    $reservation_dates = $form_state->getValue('field_dates');
    $reservation_start = new DrupalDateTime($reservation_dates[0]['value']);
    $reservation_end = new DrupalDateTime($reservation_dates[0]['end_value']);
    
    $interval = $reservation_start->diff($reservation_end);
    $requested_reservation_period = $interval->format('%d:%h');
    
    $equipment_node = Node::load($form_state->getValue(['field_equipment', 0, 'target_id']));
    $event_title = $this->getTitle($form_state->getValue(['field_event', 0, 'target_id']));
    $location_title = $this->getTitle($form_state->getValue(['field_location', 0, 'target_id']));
    $room_title = $this->getTitle($form_state->getValue(['field_room', 0, 'target_id']));

    // Equipment Fields:
    // field_text_content, field_equipment_type, field_duration_min, image_primary
    $minimum_reservation = $equipment_node->get('field_duration_min')->getValue();
    if (!empty($minimum_reservation)) {
      $minimum_reservation = new \DateInterval($minimum_reservation[0]['value']);
      // Set it up like 0:1 meaning "0 days:1 hour"
      $minimum_reservation = $minimum_reservation->format('%d') . ':' . $minimum_reservation->format('%h');
      $equipment_type = $equipment_node->get('field_equipment_type')->getValue();
      $equipment_type = $equipment_type[0]['target_id'];
      $equipment_term = Term::load($equipment_type);
      $email_addresses = $equipment_term->get('field_email')->getValue();

      // Reservation period must be at least as long as the largest minimum reservation period of any item in the cart
      if (!$this->timeCheck($requested_reservation_period, $minimum_reservation)) {
        $explodies = explode(':', $minimum_reservation);
        $form_state->setErrorByName('field_dates', t('The minimum reservation on this piece of equipment is ' . $explodies[0] . ' day(s) and ' . $explodies[1] . ' hour(s). Please make a reservation for at least that long.'));
      }
    }
    // Items in the cart must be available during the reservation period
    // Get other reservations at same time. No two people can have the same thing checked out at the same time.
    if ($this->conflictCheck($reservation_dates[0]['value'], $reservation_dates[0]['end_value'], $equipment_node)) {
      $form_state->setErrorByName('field_dates', t('This piece of equipment is reserved during the chosen period. Please check availability and select another date/time.'));
    }
    // Location must be selected - DONE (by virtue of required field)
    // Make sure reservation isn't in the past.
    if (new DrupalDateTime() > $reservation_start) {
      $form_state->setErrorByName('field_dates', t('Reservations cannot be made in the past.'));
    }
    if ($reservation_start > $reservation_end) {
      $form_state->setErrorByName('field_dates', t('The reservation end date/time must be after the start date/time.'));
    }

    // Also do normal validation.
    parent::validateForm($form, $form_state);
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
      $entity->setRevisionCreationTime(REQUEST_TIME);
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    /*switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Equipment reservation.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Equipment reservation.', [
          '%label' => $entity->label(),
        ]));
    }*/

    drupal_set_message('Your equipment was successfully reserved.');
    // Redirect to the staff member's reservation screen on the site. (e.g., /user/6/room-reservations)
    //$form_state->setRedirect('entity.equipment_reservation.canonical', ['equipment_reservation' => $entity->id()]);
    $form_state->setRedirect('intercept_equipment.account.equipment_reservations');
  }

  // Gets the title of a specified node id.
  public function getTitle($nid = NULL) {
    if (empty($nid)) {
      return NULL;
    }
    $node = Node::load($nid);
    $title = $node->getTitle();
    return $title;
  }

  /**
   *  Callback for AJAX form element. Shows the results from a view dynamically.
   */
  public function equipmentAvailabilityView(array &$form, FormStateInterface $form_state) : array {
    // Get value of field_equipment and pass that as $nid to the view.
    $nid = $form_state->getValue(['field_equipment', 0, 'target_id']);
    // In some cases this field value shows up looking like:
    // Portable Projector (2655)
    // We only need the nid though.
    if (!is_numeric($nid)) {
      $nid = str_replace('"', '', $nid);
      $pattern = '/\(([^\)]*?)\)$/'; // text between () excluding ().
      preg_match($pattern, $nid, $matches);
      $nid = $matches[1];
    }

    // Display the view embed with the node id of the piece of equipment.
    $output = '<div id="edit-output">';
    $output .= '<h2>Upcoming Reservations</h2>';
    $availability_view = views_embed_view('intercept_equipment_reservations_availability', 'embed', $nid);
    $output .= \Drupal::service('renderer')->render($availability_view);
    $output .= '</div>';
    return ['#markup' => $output];
  }

  /**
   *  Compares the requested reservation time to the minimum reservation time.
   */
  public function timeCheck($requested_reservation_period, $minimum_reservation) {
    $explodies = explode(':', $minimum_reservation);
    $minimum_reservation_days = $explodies[0];
    $minimum_reservation_hours = $explodies[1];
    $explodies = explode(':', $requested_reservation_period);
    $requested_reservation_period_days = $explodies[0];
    $requested_reservation_period_hours = $explodies[1];
    if ($requested_reservation_period_days > $minimum_reservation_days) {
      return TRUE;
    }
    else if ($requested_reservation_period_days == $minimum_reservation_days && $requested_reservation_period_hours >= $minimum_reservation_hours) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check for conflicting reservations.
   */
  public function conflictCheck($reservation_start, $reservation_end, $equipment_node) {
    // Check for this particular equipment node.
    $nid = $equipment_node->id();
    // Find all of the reservations.
    $query = \Drupal::entityQuery('equipment_reservation')
      ->condition('status', 1)
      ->condition('field_equipment', $nid);
    $er_ids = $query->execute();
    if (empty($er_ids)) {
      return FALSE;
    }

    // Requested reservation timestamps
    $dateTime = new DrupalDateTime($reservation_start);
    $reservation_start = $dateTime->getTimestamp();
    $dateTime = new DrupalDateTime($reservation_end);
    $reservation_end = $dateTime->getTimestamp();
    // Get existing reservation timestamps
    foreach ($er_ids as $er_id) {
      // Don't check it against itself for conflicts.
      if ($er_id != $this->entity->id()) {
        // Get the reservation dates.
        $entity_manager = \Drupal::entityTypeManager();
        $equipment_reservation = $entity_manager->getStorage('equipment_reservation')->load($er_id);
        $dates = $equipment_reservation->get('field_dates')->getValue();
        $existing_reservation_start = $dates[0]['value'];
        $existing_reservation_end = $dates[0]['end_value'];
        // Change everything to timestamps.
        $dateTime = new DrupalDateTime($existing_reservation_start, 'UTC');
        $existing_reservation_start = $dateTime->getTimestamp();
        $dateTime = new DrupalDateTime($existing_reservation_end, 'UTC');
        $existing_reservation_end = $dateTime->getTimestamp();

        // Setup is done. Check for actual overlap.
        //if ((StartDate1 <= EndDate2) and (EndDate1 >= StartDate2)) {
        if ($reservation_start <= $existing_reservation_end && $reservation_end >= $existing_reservation_start) {
          // Allow "start touching" and "end touching" type reservations.
          if ($reservation_start != $existing_reservation_end && $reservation_end != $existing_reservation_start) {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

}
