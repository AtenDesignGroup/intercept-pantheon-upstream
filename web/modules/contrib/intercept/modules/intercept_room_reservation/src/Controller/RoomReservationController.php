<?php

namespace Drupal\intercept_room_reservation\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\intercept_core\ReservationManagerInterface;
use Drupal\intercept_room_reservation\Entity\RoomReservationInterface;
use Drupal\intercept_room_reservation\Form\RoomReservationAgreementForm;
use Drupal\intercept_room_reservation\Form\RoomReservationAvailabilityForm;
use Drupal\jsonapi\Resource\JsonApiDocumentTopLevel;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RoomReservationController.
 *
 *  Returns responses for Room reservation routes.
 */
class RoomReservationController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The reservation manager.
   *
   * @var \Drupal\intercept_core\ReservationManagerInterface
   */
  protected $reservationManager;

  /**
   * The private temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Create a new RoomReservationController.
   */
  public function __construct(ReservationManagerInterface $reservation_manager, PrivateTempStoreFactory $temp_store_factory) {
    $this->reservationManager = $reservation_manager;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('intercept_core.reservation.manager'),
      $container->get('user.private_tempstore')
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
    $build = [];
    $build['#attached']['library'][] = 'intercept_room_reservation/reserveRoom';
    $build['#markup'] = '';
    $build['intercept_room_reserve']['#markup'] = '<div id="reserveRoomRoot"></div>';

    return $build;
  }

  /**
   * User account reservations.
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
   * Displays a Room reservation  revision.
   *
   * @param int $room_reservation_revision
   *   The Room reservation  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($room_reservation_revision) {
    $room_reservation = $this->entityTypeManager()->getStorage('room_reservation')->loadRevision($room_reservation_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('room_reservation');

    return $view_builder->view($room_reservation);
  }

  /**
   * Page title callback for a Room reservation  revision.
   *
   * @param int $room_reservation_revision
   *   The Room reservation  revision ID.
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
   *   A Room reservation  object.
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
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
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
   * Custom callback to check availabiity before reserving a room.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
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
   */
  public function availability(Request $request) {
    // Accept query sring params, and then also accept a post request.
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
        'duration' => $params['duration'],
        'rooms' => $rooms,
        'debug' => !empty($params['debug']),
      ]);
    }

    return JsonResponse::create($result, 200);
  }

}
