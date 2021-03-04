<?php

namespace Drupal\intercept_room_reservation\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\OpenOffCanvasDialogCommand;
use Drupal\Core\Ajax\SetDialogTitleCommand;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\intercept_core\ReservationManagerInterface;
use Drupal\intercept_room_reservation\Entity\RoomReservation;
use Drupal\intercept_room_reservation\Entity\RoomReservationInterface;
use Drupal\intercept_room_reservation\Form\RoomReservationAvailabilityForm;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class RoomReservationController.
 *
 *  Returns responses for Room reservation routes.
 */
class RoomReservationController extends ControllerBase implements ContainerInjectionInterface {

  use AjaxHelperTrait;

  /**
   * The reservation manager.
   *
   * @var \Drupal\intercept_core\ReservationManagerInterface
   */
  protected $reservationManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The serializer which serializes the views result.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Create a new RoomReservationController.
   *
   * @param \Drupal\intercept_core\ReservationManagerInterface $reservation_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(ReservationManagerInterface $reservation_manager, AccountInterface $current_user, EntityFormBuilderInterface $entity_form_builder, DateFormatterInterface $date_formatter, EntityFieldManagerInterface $entity_field_manager, SerializerInterface $serializer, RendererInterface $renderer) {
    $this->reservationManager = $reservation_manager;
    $this->currentUser = $current_user;
    $this->entityFormBuilder = $entity_form_builder;
    $this->entityFieldManager = $entity_field_manager;
    $this->dateFormatter = $date_formatter;
    $this->serializer = $serializer;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('intercept_core.reservation.manager'),
      $container->get('current_user'),
      $container->get('entity.form_builder'),
      $container->get('date.formatter'),
      $container->get('entity_field.manager'),
      $container->get('serializer'),
      $container->get('renderer')
    );
  }

  /**
   * Reserve Room.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   *
   * @return array
   *   Return Room reservation page.
   */
  public function reserve(Request $request) {
    $room_reservation_settings = \Drupal::config('intercept_room_reservation.settings');
    // Add customer room reservation limit.
    $limit = $room_reservation_settings->get('reservation_limit', 0);
    // Add room reservation agreement text.
    $agreement_text = $room_reservation_settings->get('agreement_text', '');

    // Add customer advanced limit.
    $advanced_limit = $room_reservation_settings->get('advanced_reservation_limit', 0);

    // Add last reservation before closing value (number of minutes).
    $last_reservation_before_closing = $room_reservation_settings->get('last_reservation_before_closing', 15);

    // Add customer barred message.
    $reservation_barred_text = $room_reservation_settings->get('reservation_barred_text');

    // Add publicize field.
    $reservation_fields = $this->entityFieldManager->getFieldDefinitions('room_reservation', 'room_reservation');
    if (array_key_exists('field_publicize', $reservation_fields)) {
      $publicize_description = $reservation_fields['field_publicize']->getDescription();
    }

    // Add default location.
    $default_locations = [];
    $customer_barred = FALSE;
    if ($this->currentUser->isAuthenticated()) {
      $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser->id());
      $customer = $this->entityTypeManager()
        ->getStorage('profile')
        ->loadByProperties([
          'type' => 'customer',
          'uid' => $this->currentUser->id(),
        ]);
      if (!empty($customer)) {
        $customer = reset($customer);
        // See if the customer is allowed to make reservations or is barred.
        $customer_barred = $customer->get('field_room_reservation_barred')->getString();
        $customer_barred = $customer_barred === '1';
      }

      if ($reservations = $this->reservationManager->getReservationsByUser('room', $user)) {
        if (!empty($reservations)) {
          $last_reservation = reset($reservations);
          // First, look for the last room reservation made.
          if ($last_reservation = reset($reservations)) {
            $last_room = $last_reservation->field_room->entity;
            $last_location = $last_room->field_location->entity;

            if (!empty($last_location)) {
              $default_locations = [$last_location->uuid()];
            }
          }
        }
      }
      else {
        // If no reservation, get the preferred locations.
        if (!empty($customer)) {
          foreach ($customer->get('field_preferred_location')->referencedEntities() as $location) {
            if ($location->field_branch_location->value) {
              $default_locations[] = $location->uuid();
            }
          }
        }
      }
    }
    $build = [
      'intercept_room_reserve' => [
        '#markup' => '<div id="reserveRoomRoot"></div>',
      ],
      '#attached' => [
        'library' => [
          'intercept_room_reservation/reserveRoom',
        ],
        'drupalSettings' => [
          'intercept' => [
            'user' => [
              'barred' => $customer_barred,
            ],
            'room_reservations' => [
              'agreement_text' => $agreement_text['value'],
              'customer_advanced_limit' => $advanced_limit,
              'customer_advanced_text' => $advanced_limit > 0 ? $this->t('Reservations may be made up to @limit days in advance', ['@limit' => $advanced_limit]) : '',
              'customer_limit' => $limit,
              'default_locations' => $default_locations,
              'field_publicize' => [
                'description' => $publicize_description ?: '',
              ],
              'last_reservation_before_closing' => $last_reservation_before_closing,
              'reservation_barred_text' => $reservation_barred_text['value'],
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * Room Reservation Scheduler.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   *
   * @return array
   *   Return room reservation scheduler page.
   */
  public function scheduler(Request $request) {
    $build = [
      '#theme' => 'room_reservation_scheduler',
    ];

    $build['#attached']['library'][] = 'intercept_room_reservation/roomReservationScheduler';
    $build['#attached']['library'][] = 'intercept_room_reservation/roomReservationMediator';
    $build['#content'] = [
      'intercept_room_reservation_scheduler' => [
        '#markup' => '<div id="roomReservationSchedulerRoot"></div>',
      ],
    ];

    $statuses = [
      'requested',
      'approved',
      'selected',
      'event'
    ];

    $build['#content']['status_legend'] = [
      '#theme' => 'intercept_reservation_status_legend',
      '#statuses' => [],
    ];

    foreach ($statuses as $status) {
      $build['#content']['status_legend']['#statuses'][$status] = [
        '#theme' => 'intercept_reservation_status',
        '#status' => $status,
      ];
    }

    return $build;
  }

  /**
   * Custom room reservation add form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   */
  public function add(Request $request) {
    $config = $this->config('intercept_room_reservation.settings');
    $form_mode = $config->get('off_canvas_form_mode') ? $config->get('off_canvas_form_mode') : 'default';

    /** @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $room_reservation */
    $room_reservation = RoomReservation::create();
    $values = $request->query->get('values');

    // The form values can be preset in the request.
    if (!empty($values)) {
      // Populate the room value.
      if (!empty($values['room'])) {
        $room_reservation->field_room->target_id = $room_reservation['room'];
      }
      // @TODO: Populate the date values.
      // Currently the values get updated when the dialog loads
    }

    $form = \Drupal::service('entity.form_builder')->getForm($room_reservation, $form_mode);
    $build = [
      'form' => $form,
    ];

    $response = new AjaxResponse();
    $action = $request->get('action');

    switch ($action) {
      case 'replace':
        $response->addCommand(new SetDialogTitleCommand('#drupal-off-canvas', 'Edit Reservation'));
        $response->addCommand(new HtmlCommand('#drupal-off-canvas', $build));
        break;

      default:
        $response->addCommand(new OpenOffCanvasDialogCommand('Add Reservation', $build, $request->get('dialogOptions', [])));
        break;
    }

    return $response;
  }

  /**
   * Custom room reservation edit form.
   *
   * @param \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $room_reservation
   *   A Room reservation object.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   */
  public function edit(RoomReservationInterface $room_reservation, Request $request) {
    $config = $this->config('intercept_room_reservation.settings');
    $form_mode = $config->get('off_canvas_form_mode') ? $config->get('off_canvas_form_mode') : 'default';

    $form = \Drupal::service('entity.form_builder')->getForm($room_reservation, $form_mode);
    $build = [
      'form' => $form,
    ];

    $response = new AjaxResponse();
    $action = $request->get('action');

    switch ($action) {
      case 'replace':
        $response->addCommand(new SetDialogTitleCommand('#drupal-off-canvas', 'Edit Reservation'));
        $response->addCommand(new HtmlCommand('#drupal-off-canvas', $build));
        break;

      default:
        $response->addCommand(new OpenOffCanvasDialogCommand('Edit Reservation', $build, $request->get('dialogOptions')));
        break;
    }

    return $response;
  }

  /**
   * User account reservations.
   *
   * @param \Drupal\user\UserInterface $user
   *
   * @return array
   *   Page callback for user/{user}/reservations.
   */
  public function manage(UserInterface $user) {
    $build = [];
    $build['#attached']['library'][] = 'intercept_room_reservation/manageRoomReservations';
    $build['#markup'] = '';
    $build['intercept_room_reserve']['#markup'] = '<div id="roomReservationsRoot"></div>';

    $build['upcoming_room_reservations'] = [
      '#type' => 'view',
      '#name' => 'intercept_room_reservations',
      '#display_id' => 'upcoming',
    ];

    return $build;
  }

  /**
   * Displays a Room reservation revision.
   *
   * @param int $room_reservation_revision
   *   The Room reservation revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($room_reservation_revision) {
    $room_reservation = $this->entityTypeManager()->getStorage('room_reservation')->loadRevision($room_reservation_revision);
    return $this->entityTypeManager()->getViewBuilder('room_reservation')->view($room_reservation);
  }

  /**
   * Page title callback for a Room reservation revision.
   *
   * @param int $room_reservation_revision
   *   The Room reservation revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($room_reservation_revision) {
    $room_reservation = $this->entityTypeManager()->getStorage('room_reservation')->loadRevision($room_reservation_revision);
    return $this->t('Revision of %title from %date', ['%title' => $room_reservation->label(), '%date' => \Drupal::service('date.formatter')->format($room_reservation->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Room reservation .
   *
   * @param \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $room_reservation
   *   A Room reservation object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(RoomReservationInterface $room_reservation) {
    $account = $this->currentUser();
    $langcode = $room_reservation->language()->getId();
    $langname = $room_reservation->language()->getName();
    $languages = $room_reservation->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $room_reservation_storage = $this->entityTypeManager()->getStorage('room_reservation');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $room_reservation->label()]) : $this->t('Revisions for %title', ['%title' => $room_reservation->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all room reservation revisions") || $account->hasPermission('administer room reservation entities')));
    $delete_permission = (($account->hasPermission("delete all room reservation revisions") || $account->hasPermission('administer room reservation entities')));

    $rows = [];

    $vids = $room_reservation_storage->revisionIds($room_reservation);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\intercept_room_reservation\RoomReservationInterface $revision */
      $revision = $room_reservation_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $room_reservation->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.room_reservation.revision', ['room_reservation' => $room_reservation->id(), 'room_reservation_revision' => $vid]));
        }
        else {
          $link = $room_reservation->toLink($date)->toString();
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.room_reservation.translation_revert', [
                'room_reservation' => $room_reservation->id(),
                'room_reservation_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.room_reservation.revision_revert', ['room_reservation' => $room_reservation->id(), 'room_reservation_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.room_reservation.revision_delete', ['room_reservation' => $room_reservation->id(), 'room_reservation_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['room_reservation_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

  /**
   * API Callback to get a user's status.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   */
  public function userStatus(Request $request) {
    $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());
    $result = [
      'uuid' => $user->uuid(),
      'limit' => $this->config('intercept_room_reservation.settings')->get('reservation_limit', 1),
      'advanced_limit' => $this->config('intercept_room_reservation.settings')->get('advanced_reservation_limit', 0),
      'count' => $this->reservationManager->userReservationCount($this->currentUser()),
      'exceededLimit' => $this->reservationManager->userExceededReservationLimit($this->currentUser()),
    ];

    return JsonResponse::create($result, 200);
  }

  /**
   * Room node local task for reservations.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The room node.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   */
  public function reservations(NodeInterface $node, Request $request) {
    $reservations = $this->reservationManager->reservations('room', function ($query) use ($node) {
      $query->condition('field_room', $node->id(), '=');
      $query->sort('field_dates.value', 'ASC');
    });
    $list = $this->entityTypeManager()->getListBuilder('room_reservation');
    $reservation_ids = array_keys($reservations);
    $link = Link::createFromRoute('Create reservation', 'entity.room_reservation.add_form', [
      'room' => $node->id(),
    ], [
      'attributes' => ['class' => ['button button-action']],
    ]);
    $form_state = new FormState();
    $form_state->set('node', $node);
    $form = $this->formBuilder()->buildForm(RoomReservationAvailabilityForm::class, $form_state);
    return [
      'create' => $link->toRenderable(),
      'list' => $list
        ->setEntityIds($reservation_ids)
        ->setLimit(10)
        ->hideColumns(['room'])
        ->render(),
      'availability_form' => [
        '#title' => $this->t('Availability form'),
        '#type' => 'details',
        '#open' => !empty($form_state->getUserInput()),
        'form' => $form,
      ],
    ];
  }

  /**
   * Custom callback to check availability before reserving a room.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function reserveRoom(Request $request) {
    $decode = \Drupal::service('serializer')->encode($request->getContent(), 'json');
    if (!array_key_exists('data', $decode)) {
      return JsonResponse::create();
    }
    $dates = $decode['data']['attributes']['field_dates'];
    $room = $decode['data']['relationships']['field_room']['data']['id'];
    $manager = \Drupal::service('intercept_core.reservation.manager');
    $availability = $manager->availability([
      'rooms' => $manager->convertIds([$room]),
      'start' => $dates['value'],
      'end' => $dates['end_value'],
    ]);

    $availability[$room]['uuid'] = $room;

    if ($availability[$room]['has_reservation_conflict']) {
      return JsonResponse::create([
        'message' => $this->t('Has reservation conflict'),
        'conflicting_reservation' => $availability[$room],
        'error' => TRUE,
        'error_code' => 409,
      ], 409);
    }

    $resource_type = \Drupal::service('jsonapi.resource_type.repository')->get('room_reservation', 'room_reservation');

    // From Drupal\jsonapi\Routing::getRoutesForResourceType().
    // This is still not ideal and will be discussed in
    // https://www.drupal.org/project/intercept/issues/3002286.
    $request->attributes->set('serialization_class', JsonApiDocumentTopLevel::class);
    return \Drupal::service('jsonapi.request_handler')->handle($request, $resource_type);
  }

  /**
   * API Callback to check room availability.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function availability(Request $request) {
    // Accept query string params, and then also accept a post request.
    $params = $request->query->get('filter');

    if ($post = Json::decode($request->getContent())) {
      $params = empty($params) ? $post : array_merge($params, $post);
    }

    $result = [];
    if (!empty($params)) {
      $manager = \Drupal::service('intercept_core.reservation.manager');
      $rooms = !empty($params['rooms']) ? $manager->convertIds($params['rooms']) : [];
      $result = $manager->availability([
        'start' => $params['start'],
        'end' => $params['end'],
        'exclude' => isset($params['exclude']) ? $params['exclude'] : NULL,
        'exclude_uuid' => isset($params['exclude_uuid']) ? $params['exclude_uuid'] : NULL,
        'duration' => isset($params['duration']) ? $params['duration'] : NULL,
        'rooms' => $rooms,
        'debug' => !empty($params['debug']),
      ]);
    }

    return JsonResponse::create($result, 200);
  }

}
