<?php

namespace Drupal\intercept_room_reservation\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
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

  protected $reservationManager;

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
    $step = $request->query->get('step');
    $build = [];

    // if ($this->reservationManager->userExceededReservationLimit($this->currentUser())) {
    //   $config = $this->config('intercept_room_reservation.settings');
    //   $limit_text = $config->get('reservation_limit_text');
    //   $text = !empty($limit_text['value']) ? $limit_text['value'] : '';
    //   $build['message'] = [
    //     '#type' => 'html_tag',
    //     '#tag' => 'div',
    //     '#attributes' => [
    //       'id' => 'reserveRoomRoot',
    //       // TODO: Move this into the theme layer with the react.js version of this page.
    //       'class' => ['l--offset'],
    //     ],
    //   ];

    //   $build['message']['text'] = [
    //     '#type' => 'processed_text',
    //     '#text' => $this->t($text, [
    //       '@account-link' => \Drupal\Core\Link::createFromRoute('your account', 'entity.user.room_reservations', [
    //         'user' => $this->currentUser()->id(),
    //       ])->toString(),
    //       '@max-room-reservations' => $config->get('reservation_limit'),
    //     ]),
    //     '#format' => !empty($limit_text['format']) ? $limit_text['format'] : 'basic_html',
    //   ];
    //   return $build;
    // }

    // @TODO: Move this to the reservation manager.
    $bypass_agreement = $this->currentUser()->hasPermission('bypass room reservation agreement');
    $store = $this->tempStoreFactory->get('reservation_agreement');
    if (!$store->get('room') && !$bypass_agreement) {
      return $this->agreement($request);
    }

    $build = [];
    $build['#attached']['library'][] = 'intercept_room_reservation/reserveRoom';
    $build['#markup'] = '';
    $build['intercept_room_reserve']['#markup'] = '<div id="reserveRoomRoot"></div>';

    return $build;
  }

  /**
   * Reservation agreement form page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   */
  public function agreement(Request $request) {
    $config = $this->config('intercept_room_reservation.settings');
    $agreement = $config->get('agreement_text');
    $build['agreement'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'reserveRoomRoot',
        // TODO: Move this into the theme layer with the react.js version of this page.
        'class' => ['l--offset'],
      ],
    ];

    $build['agreement']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => t('Reserve a Room'),
    ];

    $build['agreement']['text'] = [
      '#type' => 'processed_text',
      '#text' => $agreement['value'],
      '#format' => $agreement['format'],
    ];

    $build['agreement']['form'] = $this->formBuilder()->getForm(RoomReservationAgreementForm::class);

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
    $room_reservation = $this->entityManager()->getStorage('room_reservation')->loadRevision($room_reservation_revision);
    $view_builder = $this->entityManager()->getViewBuilder('room_reservation');

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
    $room_reservation = $this->entityManager()->getStorage('room_reservation')->loadRevision($room_reservation_revision);
    return $this->t('Revision of %title from %date', ['%title' => $room_reservation->label(), '%date' => format_date($room_reservation->getRevisionCreationTime())]);
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
    $room_reservation_storage = $this->entityManager()->getStorage('room_reservation');

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
          $link = $this->l($date, new Url('entity.room_reservation.revision', ['room_reservation' => $room_reservation->id(), 'room_reservation_revision' => $vid]));
        }
        else {
          $link = $room_reservation->link($date);
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
                'langcode' => $langcode
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
    $form_state = new \Drupal\Core\Form\FormState();
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
    $decode = \Drupal::service('serializer.encoder.jsonapi')->decode($request->getContent(), 'api_json');
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
