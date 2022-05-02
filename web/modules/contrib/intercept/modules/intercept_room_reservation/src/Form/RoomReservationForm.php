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
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, EntityDisplayRepositoryInterface $entity_display_repository, Dates $date_utility, AccountProxy $current_user, RoomReservationCertificationChecker $certification_checker, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, ParseAutocompleteInput $autocompleteParser) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->entityDisplayRepository = $entity_display_repository;
    $this->dateUtility = $date_utility;
    $this->currentUser = $current_user;
    $this->certificationChecker = $certification_checker;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->autocompleteParser = $autocompleteParser;
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
      $container->get('intercept_room_reservation.autocomplete_parser')
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
    if ($entity->isNew()) {
      $entity->set('field_user', \Drupal::currentUser()->id());
    }

    // If intercept_certification is installed, see if the current user is
    // certified to reserve this room.
    $moduleHandler = \Drupal::moduleHandler();
    if ($moduleHandler->moduleExists('intercept_certification')) {
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
          }
      }

      // On ajax responses, $room is not available - but we have already
      // determined that the user is certified so we don't need to check again.
      $userIsCertified = (!empty($room)) ? $this->certificationChecker->userIsCertified($entity->field_user->target_id, $room) : TRUE;

      if (!$userIsCertified) {
        $form['user_not_certified'] = [
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
    }

    $form = parent::buildForm($form, $form_state);
    $form['#attached'] = [
      'library' => [
        'intercept_room_reservation/room-reservations',
        // @todo Determine if we're missing necessary functionality without the
        // following library:
        // 'intercept_core/delay_keyup',
      ],
    ];

    $form['field_room']['widget'][0]['target_id']['#ajax'] = [
      'callback' => '::availabilityCallback',
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
      'callback' => '::availabilityCallback',
      'disable-refocus' => TRUE,
      'event' => 'change delayed_keyup',
      'wrapper' => 'edit-field-dates-0-message',
      'progress' => [
        'type' => 'throbber',
        'message' => $this->t('Verifying reservation dates...'),
      ],
    ];
    $form['field_dates']['widget'][0]['end_value']['#ajax'] = [
      'callback' => '::availabilityCallback',
      'disable-refocus' => TRUE,
      'event' => 'change delayed_keyup',
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
    }
    else {
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
   * Gets the base reservation parameters.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   An array of room, and date values.
   */
  protected function getReservationParams(FormStateInterface $form_state) {
    $reservation_params = [];

    $field_room = $form_state->getValue('field_room');
    if (isset($field_room[0]['target_id'])) {
      $reservation_params['room'] = $field_room[0]['target_id'];
    }

    $field_dates = $form_state->getUserInput()['field_dates'];
    if (($start_date = $field_dates[0]['value']['date']) && ($start_time = $field_dates[0]['value']['time'])) {
      $reservation_params['start'] = $this->dateUtility->convertDate($start_date . 'T' . $start_time);
    }

    if (($end_date = $field_dates[0]['end_value']['date']) && ($end_time = $field_dates[0]['end_value']['time'])) {
      $reservation_params['end'] = $this->dateUtility->convertDate($end_date . 'T' . $end_time);
    }

    return $reservation_params;
  }

  /**
   * Checks to see if the given resource is available at the current time.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   A validation message render array.
   */
  public function checkAvailability(array &$form, FormStateInterface $form_state) {
    $reservation_params = $this->getReservationParams($form_state);

    if (!$reservation_params['room']) {
      return [];
    }

    $messages = [];

    if (isset($reservation_params['start']) && isset($reservation_params['end'])) {
      $reservation = $form_state->getFormObject()->getEntity();
      $reservation_params['rooms'] = [$reservation_params['room']];
      $reservation_params['start'] = $reservation_params['start']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
      $reservation_params['end'] = $reservation_params['end']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

      if ($reservation->id()) {
        $reservation_params['exclude'] = [$reservation->id()];
      }
      $reservation_manager = \Drupal::service('intercept_core.reservation.manager');

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

      if ($availability = $reservation_manager->availability($reservation_params)) {
        foreach ($availability as $room_availability) {
          foreach ($conflictTypes as $conflictType => $message) {
            $messages[$conflictType] = ($room_availability[$conflictType])
              ? $message : '';
          }
        }
      }
    }

    return $messages;
  }

  /**
   *
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

    $markup = [
      '#type' => 'html_tag',
      '#attributes' => [
        'id' => 'edit-field-dates-0-message',
      ],
      '#tag' => 'div',
      'child' => [
        '#type' => 'intercept_field_error_message',
        '#message' => $messages[0],
      ],
    ];
    if (count($messages) > 1) {
      $markup = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $messages,
        '#attributes' => [
          'id' => 'edit-field-dates-0-message',
        ],
        '#wrapper_attributes' => ['class' => 'container'],
      ];
    }
    return $markup;
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

    $validationMessages = $this->checkAvailability($form, $form_state);
    if (!empty($validationMessages)) {
      $markup = NULL;
      $messages = [];
      foreach (array_filter($validationMessages) as $key => $validationMessage) {
        if (!empty($validationMessage)) {
          $messages[] = $validationMessage;
        }
      }
      $markup = $this->wrapAvailabilityValidationError($messages);
    }
    $command = new ReplaceCommand('[id^="edit-field-dates-0-message"]', $markup);
    $response->addCommand($command);

    // Trigger the intercept:updateRoomReservation event.
    $reservation_params = $this->getReservationParams($form_state);
    if (isset($reservation_params['start']) && isset($reservation_params['end'])) {
      $reservation = $form_state->getFormObject()->getEntity();
      $reservation_params['id'] = $reservation->uuid();
      $reservation_params['start'] = $reservation_params['start']->format(\DateTime::RFC3339);
      $reservation_params['end'] = $reservation_params['end']->format(\DateTime::RFC3339);

      if (!empty($reservation->field_room) && $room = $form_state->getValue('field_room')[0]) {
        $reservation->set('field_room', $room['target_id']);
        $reservation_params['room'] = $reservation->field_room->entity->uuid();
      }
      $command = new InvokeCommand('html', 'trigger', [
        'intercept:updateRoomReservation', $reservation_params,
      ]);
      $response->addCommand($command);
    }

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

    $validationMessages = $this->checkAvailability($form, $form_state);
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
}
