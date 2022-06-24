<?php

namespace Drupal\intercept_room_reservation\Form;

use Drupal\Core\Link;
use Drupal\node\Entity\Node;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_core\Utility\Dates;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Ajax\SetDialogTitleCommand;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\intercept_room_reservation\ParseAutocompleteInput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\intercept_room_reservation\ValidationMessageBuilder;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\intercept_room_reservation\RoomReservationCertificationChecker;

/**
 * Form controller for Room reservation edit forms.
 *
 * @ingroup intercept_room_reservation
 */
class RoomReservationForm extends ContentEntityForm {

  use AjaxFormHelperTrait;

  /**
   * The autocomplete parser service.
   *
   * @var \Drupal\intercept_room_reservation\ParseAutocompleteInput;
   */
  protected $autocompleteParser;

  /**
   * Drupal\intercept_room_reservation\RoomReservationCertificationChecker definition.
   *
   * @var \Drupal\intercept_room_reservation\RoomReservationCertificationChecker
   */
  protected $certificationChecker;

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
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The saved status of the entity.
   *
   * @var int
   */
  protected $savedStatus;

  /**
   * The intercept_room_reservation.validation_message_builder service.
   *
   * @var \Drupal\intercept_room_reservation\ValidationMessageBuilder
   */
  protected $validationMessageBuilder;

  /**
   * Constructs a RoomReservationForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\intercept_core\Utility\Dates $date_utility
   *   The Intercept dates utility.
   * @param \Drupal\intercept_room_reservation\RoomReservationCertificationChecker $certification_checker
   *   The certification checking service.
   * @param \Drupal\intercept_room_reservation\ParseAutocompleteInput $autocompleteParser
   *   The autocomplete parser.
   * @param \Drupal\intercept_room_reservation\ValidationMessageBuilder $validationMessageBuilder
   *   The intercept_room_reservation.validation_message_builder service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, EntityDisplayRepositoryInterface $entity_display_repository, Dates $date_utility, AccountProxy $current_user, RoomReservationCertificationChecker $certification_checker, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, ParseAutocompleteInput $autocompleteParser, ValidationMessageBuilder $validationMessageBuilder) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->entityDisplayRepository = $entity_display_repository;
    $this->dateUtility = $date_utility;
    $this->currentUser = $current_user;
    $this->certificationChecker = $certification_checker;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->autocompleteParser = $autocompleteParser;
    $this->validationMessageBuilder = $validationMessageBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('entity_display.repository'),
      $container->get('intercept_core.utility.dates'),
      $container->get('current_user'),
      $container->get('intercept_room_reservation.certification_checker'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('intercept_room_reservation.autocomplete_parser'),
      $container->get('intercept_room_reservation.validation_message_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    parent::init($form_state);
    if ($this->getRequestWrapperFormat() == 'drupal_dialog.off_canvas') {
      $config = $this->config('intercept_room_reservation.settings');
      $current_user = \Drupal::currentUser();
      $roles = $current_user->getRoles();
      if (in_array('intercept_registered_customer', $roles)) {
        $form_display = $this->entityDisplayRepository->getFormDisplay('room_reservation', 'room_reservation', 'customer_reserve');
      } else {
        $form_display = $this->entityDisplayRepository->getFormDisplay('room_reservation', 'room_reservation', 'default');
      }
      $this->setFormDisplay($form_display, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $entity */
    $entity = $this->entity;
    if ($entity->isNew() && ($room = $this->getRequest()->query->get('room'))) {
      $entity->set('field_room', $room);
    }
    // Only set the field_user value if it's not already set via clone/copy.
    $field_user = $entity->get('field_user')->getValue();
    if ($entity->isNew() && empty($field_user)) {
      $entity->set('field_user', \Drupal::currentUser()->id());
    }

    // We make multiple trips through this buildForm() method, and the $room
    // data can be in one of a couple of different places.
    $room = NULL;
    switch (array_key_exists('values', $form_state->getUserInput())) {
      case TRUE:
        $room = $form_state->getUserInput()['values']['room'];
        break;

      default:
        if (!empty($form_state->getUserInput()['field_room'])) {
          $room = $this->autocompleteParser->getIdFromAutocomplete($form_state->getUserInput()['field_room'][0]);
          break;
        }

        // On some ajax calls, the room is in the $entity.
        $room = $entity->field_room->target_id;
    }

    // If intercept_certification is installed, see if the current user is
    // certified to reserve this room.
    $moduleHandler = \Drupal::moduleHandler();
    $userIsCertified = TRUE;
    if ($moduleHandler->moduleExists('intercept_certification')) {
      $userIsCertified = (!empty($room)) ? $this->certificationChecker->userIsCertified($entity->field_user->target_id, $room) : TRUE;
    }

    if (empty($userCanReserveRoom) && !empty($room)) {
      $node = $this->entityTypeManager->getStorage('node')->load($room);
      $userCanReserveRoom = $this->userCanReserveRoom($node, $userIsCertified);
    }

    // Here's where we can show the "read only" alternate version of the form.
    // It shows more of a "detail" version of the room node.
    // We should only do this when the form is initially built if/when needed.
    // Running this code during successive rebuilds of the basic form can
    // result in errors.
    $rebuilding = $form_state->isRebuilding();
    if (!$rebuilding && $room && $userCanReserveRoom == FALSE) {
      $form['user_cannot_reserve_room'] = [
        '#type' => 'hidden',
        '#value' => TRUE,
      ];

      // Load the room node's off_canvas display into an 'item' form element.
      $node = $this->entityTypeManager->getStorage('node')->load($room);
      $view_mode = 'off_canvas';
      $build = $this->entityTypeManager->getViewBuilder('node')->view($node, $view_mode);
      $form['info'] = [
        '#type' => 'item',
        '#markup' => $this->renderer->render($build),
      ];

      return $form;
    }

    $form = parent::buildForm($form, $form_state);
    $form['#attached'] = [
      'library' => [
        'intercept_room_reservation/room-reservations',
        'intercept_core/delay_keyup',
      ],
    ];

    $form['field_room']['widget'][0]['target_id']['#ajax'] = [
      'callback' => [$this, 'availabilityCallback'],
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

    $form['field_dates']['widget'][0]['value']['#attributes']['class'] = [
      'delayed-keyup',
    ];
    $form['field_dates']['widget'][0]['end_value']['#attributes']['class'] = [
      'delayed-keyup',
    ];

    // Add information about the current revision and a link to the room
    // reservation's revisions page.
    $createdTime = $form_state->getFormObject()->getEntity()->getCreatedTime();
    $createdTime = new \DateTime('@' . $createdTime);
    $timezone = new \DateTimeZone('America/New_York');
    $createdTime->setTimezone($timezone);
    $formattedDate = $createdTime->format('Y-m-d H:i:s');
    $room_reservation = $form_state->getFormObject()->getEntity();
    if ($room_reservation->id()) {
      $revisionsLink = Link::createFromRoute($this->t('Manage revisions'), 'entity.room_reservation.version_history', [
        'room_reservation' => $room_reservation->id(),
      ]);
      $revisionsLink = $revisionsLink->toRenderable();

      $form['revision_info'] = [
        '#theme' => 'room_reservation_revision_summary',
        '#current_revision_date' => $formattedDate,
        '#revisions_link' => $revisionsLink,
        '#weight' => 15,
      ];
    }

    $form['messages'] = [
      '#markup' => '<div id="room-reservation-form__messages"></div>',
      '#weight' => -100,
    ];

    if ($this->isAjax()) {
      $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
      // @todo Remove when https://www.drupal.org/node/2897377 lands.
      $form['#id'] = Html::getId($form_state->getBuildInfo()['form_id']);
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
    } else {
      $entity->setNewRevision(FALSE);
    }

    // Ensure that notes are saved. Non-privileged users don't have access to
    // the notes field. Save notes only if 'notes' element exists in the user
    // input.
    if (array_key_exists('notes', $form_state->getUserInput())) {
      $notes = $this->t($form_state->getUserInput()['notes'][0]['value']);
      $entity->set('notes', $notes);
    }

    $entity->save();

    $this->status = parent::save($form, $form_state);

    switch ($this->status) {
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
   *
   * Repeats the ajaxSubmit function code from core, but adds messages wrapper.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ];
      $form['#sorted'] = FALSE;
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $form['#attributes']['data-drupal-selector'] . '"]', $form));
      // Wrap all messages in a container div in order to help with making
      // them sticky.
      $selector = '.messages';
      $response->addCommand(new InvokeCommand($selector, 'wrapAll', ["<div class='messages--wrapper'></div>"]));
    } else {
      $response = $this->successfulAjaxSubmit($form, $form_state);
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($this->status === SAVED_NEW) {
      $response->addCommand(new SetDialogTitleCommand('#drupal-off-canvas', 'Edit Reservation'));
    }

    $messages = ['#type' => 'status_messages'];
    $response->addCommand(new HtmlCommand('#room-reservation-form__messages', $messages));

    // Trigger the Save success event.
    $command = new InvokeCommand('html', 'trigger', [
      'intercept:saveRoomReservationSuccess',
    ]);
    $response->addCommand($command);

    // Remove any validation errors from previous submission.
    $selector = '.messages--error';
    $response->addCommand(new RemoveCommand($selector));
    $selector = '.messages--warning';
    $response->addCommand(new RemoveCommand($selector));
    // Remove the error class from any inputs.
    $selector = 'input.error';
    $response->addCommand(new InvokeCommand($selector, 'removeClass', ['error']));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Define an array of 'bypass' permissions.
    $bypassPermissions = [
      'has_max_duration_conflict' => 'bypass room reservation maximum duration constraints',
      'has_open_hours_conflict' => 'bypass room reservation open hours constraints',
      'has_reservation_conflict' => 'bypass room reservation overlap constraints'
    ];

    // Check dates
    $config = $this->config('intercept_room_reservation.settings');

    // Add customer advanced limit.
    $advanced_limit = (int) $config->get('advanced_reservation_limit', 0);
    $reservation_dates = $form_state->getValue('field_dates');
    $reservation_start = new DrupalDateTime($reservation_dates[0]['value']);
    $reservation_end = new DrupalDateTime($reservation_dates[0]['end_value']);
    $acceptableDates = $this->checkDates($advanced_limit, $reservation_start, $reservation_end);
    
    if (!$acceptableDates) {
      $advanced_text = $config->get('advanced_reservation_text');
      if ($advanced_text) {
        $form_state->setErrorByName('field_dates', $advanced_text);
      }
      else {
        $form_state->setErrorByName('field_dates', $this->t('Reservations may be made up to @limit days in advance.', ['@limit' => $advanced_limit]));
      }
    }

    // Double-check userCanReserveRoom() (duplicates some checks from buildForm above).
    $room = $form_state->getValue(['field_room', 0])['target_id'];
    $uid = $this->currentUser->id();
    if ($room && $uid) {

      // If intercept_certification is installed, see if the current user is
      // certified to reserve this room.
      $moduleHandler = \Drupal::moduleHandler();
      $userIsCertified = TRUE;
      if ($moduleHandler->moduleExists('intercept_certification')) {
        $userIsCertified = (!empty($room)) ? $this->certificationChecker->userIsCertified($uid, $room) : TRUE;
      }

      if (empty($userCanReserveRoom) && !empty($room)) {
        $node = $this->entityTypeManager->getStorage('node')->load($room);
        // Check permissions to reserve this room.
        $userCanReserveRoom = $this->userCanReserveRoom($node, $userIsCertified);

        // Check attendees vs. capacity.
        $attendees = $form_state->getValue(['field_attendee_count', 0])['value'];
        if ($attendees > 0) {
          $capacity_min = $node->get('field_capacity_min')->value;
          $capacity_max = $node->get('field_capacity_max')->value;
          if ($attendees < $capacity_min) {
            $form_state->setErrorByName('field_attendee_count', $this->t('Sorry, the minimum number of attendees for this room is @capacity.', ['@capacity' => $capacity_min]));
          }
          // elseif ($attendees > $capacity_max) { // Already handled by MaxCapacityConstraintValidator
          //   $form_state->setErrorByName('field_attendee_count', $this->t('Sorry, the maximum capacity of this room is @capacity.', ['@capacity' => $capacity_max]));
          // }
        }
      }
      if ($userCanReserveRoom == FALSE) {
        $form_state->setErrorByName('field_room', $this->t('Sorry, it doesn\'t appear that you\'re able to reserve that room.'));
      }
    }

    $validationMessages = $this->validationMessageBuilder->checkAvailability($form, $form_state);
    if (!empty(array_filter($validationMessages))) {
      foreach (array_filter($validationMessages) as $key => $validationMessage) {
        // Don't set errors if the user should be able to bypass this constraint.
        if ($this->currentUser->hasPermission($bypassPermissions[$key])) {
          continue;
        }
        $form_state->setErrorByName('field_dates', $validationMessage);
      }
    }
  }

  /**
   * Returns the ValidationMessageBuilder service's availabilityCallback.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function availabilityCallback(array &$form, FormStateInterface $form_state) {
    return $this->validationMessageBuilder->availabilityCallback($form, $form_state);
  }

  /**
   * Mimics userCanReserveRoom function from RoomReserveApp Step 1 (JS)
   */
  public function userCanReserveRoom($node, $userIsCertifiedForRoom) {
    $current_user = \Drupal::currentUser();
    $roles = $current_user->getRoles();
    $userIsManager = $userIsStaff = FALSE;
    if (in_array('intercept_event_manager', $roles) || in_array('intercept_event_organizer', $roles) || in_array('intercept_system_admin', $roles) || in_array('intercept_room_manager', $roles)) {
      $userIsManager = TRUE;
    }
    if (in_array('intercept_staff', $roles)) {
      $userIsStaff = TRUE;
    }
    // Is the room reservable online?
    $reservable = (bool) $node->get('field_reservable_online')->value;
    // Requires certification?
    $moduleHandler = \Drupal::moduleHandler();
    if ($moduleHandler->moduleExists('intercept_certification')) {
      $mustCertify = (bool) $node->get('field_requires_certification')->value;
    }
    else {
      $mustCertify = FALSE;
    }
    // A user can reserve this room if...
    // they are a manager OR...
    if ($userIsManager == TRUE) {
      return TRUE;
    }
    // the room is reservable and they are staff OR...
    if ($reservable && $userIsStaff) {
      return TRUE;
    }
    // ...the room is reservable, requires certification and they are certified OR...
    if ($reservable && $mustCertify && $userIsCertifiedForRoom) {
      return TRUE;
    }
    // ...the room is reservable and does not require certification.
    if ($reservable && !$mustCertify) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Mimics functionality from RoomReserveApp to check dates
   */
  public function checkDates($advanced_limit, $start_date, $end_date) {
    $current_user = \Drupal::currentUser();
    $roles = $current_user->getRoles();
    $userIsManager = $userIsStaff = FALSE;
    if (in_array('intercept_event_manager', $roles) || in_array('intercept_event_organizer', $roles) || in_array('intercept_system_admin', $roles) || in_array('intercept_room_manager', $roles)) {
      $userIsManager = TRUE;
    }
    if (in_array('intercept_staff', $roles)) {
      $userIsStaff = TRUE;
    }
    // Don't check dates if the user is staff.
    if ($userIsStaff == TRUE || $userIsManager) {
      return TRUE;
    }
    // Check the dates.
    $now = new DrupalDateTime();
    $diff = $now->diff($start_date)->days;
    if ($diff <= $advanced_limit) {
      return TRUE;
    } 

    return FALSE;
  }
}
