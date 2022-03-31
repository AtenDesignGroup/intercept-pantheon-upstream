<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\intercept_core\Utility\Dates;
use Drupal\intercept_event\EventEvaluationManager;
use Drupal\intercept_event\SuggestedEventsProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EventsController.
 */
class EventsController extends ControllerBase {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Intercept event evaluation manager.
   *
   * @var \Drupal\intercept_event\EventEvaluationManager
   */
  protected $evaluationManager;

  /**
   * The Intercept suggested events provider.
   *
   * @var \Drupal\intercept_event\SuggestedEventsProvider
   */
  protected $suggestedEventsProvider;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The Intercept dates utility.
   *
   * @var \Drupal\intercept_core\Utility\Dates
   */
  protected $dateUtility;

  /**
   * Constructs an EventsController object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user interface.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\intercept_event\EventEvaluationManager $evaluation_manager
   *   The Intercept event evaluation manager.
   * @param \Drupal\intercept_event\SuggestedEventsProvider $suggested_events_provider
   *   The Intercept suggested events provider.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The rendering interface.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\intercept_core\Utility\Dates $date_utility
   *   The Intercept dates utility.
   */
  public function __construct(AccountProxyInterface $currentUser, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entityTypeManager, EventEvaluationManager $evaluation_manager, SuggestedEventsProvider $suggested_events_provider, RendererInterface $renderer, Connection $connection, Dates $date_utility) {
    $this->currentUser = $currentUser;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entityTypeManager;
    $this->evaluationManager = $evaluation_manager;
    $this->suggestedEventsProvider = $suggested_events_provider;
    $this->renderer = $renderer;
    $this->connection = $connection;
    $this->dateUtility = $date_utility;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('intercept_event.evaluation_manager'),
      $container->get('intercept_event.suggested_events_provider'),
      $container->get('renderer'),
      $container->get('database'),
      $container->get('intercept_core.utility.dates')
    );
  }

  /**
   * List.
   *
   * @return string
   *   Return a render array containing the events list block.
   */
  public function list() {
    $suggested_events = $this->suggestedEventsProvider->getSuggestedEventIds();

    $build = [
      '#theme' => 'intercept_event_list',
      '#attached' => [
        'drupalSettings' => [
          'intercept' => [
            'events' => [
              'recommended' => $suggested_events,
            ],
          ],
        ],
        'library' => [
          'intercept_event/eventList',
        ],
      ],
    ];
    $this->attachFieldSettings($build);
    return $build;
  }

  /**
   * Check bundle access and permissions.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Event node to check registration access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The AccessResult object.
   */
  public function registerAccess(NodeInterface $node) {
    $access_handler = $this->entityTypeManager()->getAccessControlHandler('event_registration');
    if (!$access_handler->createAccess('event_registration')) {
      return AccessResult::forbidden();
    }
    if (!$this->isEventBundle($node)) {
      return AccessResult::forbidden();
    }
    if ($node->hasField('field_must_register') && !$node->field_must_register->value) {
      return AccessResult::forbidden();
    }

    // See if the current user is registered for this event.
    $query = $this->entityTypeManager->getStorage('event_registration')->getQuery()
      ->condition('field_user', $this->currentUser->Id())
      ->condition('status', 'active')
      ->condition('field_event', $node->Id());
    $results = $query->execute();

    $accessResult = !empty($results) ? AccessResult::allowed() : AccessResult::forbidden();

    switch ($node->registration->status) {
      case 'expired':
      case 'closed':
        return $accessResult;

      case 'full':
      case 'open_pending':
        return AccessResult::forbidden();
    }
    $access = $this->moduleHandler()->invokeAll('event_registration_event_create_access', [$node]);
    if (!empty($access)) {
      $result = array_shift($access);
      if ($result->isForbidden()) {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::allowed();
  }

  /**
   * Check bundle access and permissions.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Event node to check Registrations tab access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The AccessResult object.
   */
  public function registrationsAccess(NodeInterface $node) {
    $has_permission = $this->currentUser()->hasPermission('access event registrations tab');
    return AccessResult::allowedIf($this->isEventBundle($node) && $has_permission);
  }

  /**
   * Check bundle access and permissions.
   *
   * * @param \Drupal\node\NodeInterface $node
   *   The Event node to check Attendance tab access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The AccessResult object.
   */
  public function attendanceAccess(NodeInterface $node) {
    $has_permission = $this->currentUser()->hasPermission('access event attendance tab');
    return AccessResult::allowedIf($this->isEventBundle($node) && $has_permission);
  }

  /**
   * Whether the node is an Event type.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node entity to check.
   *
   * @return bool
   *   Whether the node is an Event type.
   */
  private function isEventBundle(NodeInterface $node) {
    return $node->bundle() == 'event';
  }

  /**
   * Gets the list builder for a Node.
   *
   * @param string $entity_type_id
   *   The entity type ID for this view builder.
   * @param \Drupal\node\NodeInterface $node
   *   The Node entity.
   *
   * @return \Drupal\Core\Entity\EntityViewBuilderInterface
   *   A view builder instance.
   */
  protected function getListBuilder($entity_type_id, NodeInterface $node = NULL) {
    $list_builder = $this->entityTypeManager()->getListBuilder($entity_type_id);
    if ($node) {
      $list_builder->setEvent($node);
    }
    return $list_builder;
  }

  /**
   * Exposes certain field config to drupalSettings.
   */
  protected function attachFieldSettings(array &$build) {
    // Load field_event_designation options.
    $event_fields = $this->entityFieldManager->getFieldStorageDefinitions('node', 'event');
    if (array_key_exists('field_event_designation', $event_fields)) {
      $options = options_allowed_values($event_fields['field_event_designation']);
      $build['#attached']['drupalSettings']['intercept']['events']['field_event_designation']['options'] = $options;
    }
  }

  /**
   * Gets the node_event_registrations build array.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node entity.
   *
   * @return array
   *   The build render array.
   */
  public function registrations(NodeInterface $node) {
    $build = [
      '#theme' => 'node_event_registrations',
      '#content' => [],
    ];
    $content = &$build['#content'];
    if ($node->hasField('field_event_user_reg_max')) {
      $content['max_registrations_per_user'] = [
        '#type' => 'item',
        '#title' => 'Maximum registrations per user:',
        '#markup' => $node->field_event_user_reg_max->value ?: "No maximum",
      ];
    }
    $properties = $node->registration->getItemDefinition()->getSetting('properties');
    $field = $node->registration;
    foreach ($properties as $name => $property) {
      // This property doesn't need to be seen by staff
      // when viewing Registrations tab.
      if ($name == 'status_user' || $name == 'status') {
        continue;
      }
      $content['details'][$name] = [
        '#type' => 'item',
        '#title' => $property->getLabel(),
        '#markup' => $field->{$name},
      ];
    }
    $content['add'] = [
      '#title' => 'Add event registration',
      '#type' => 'link',
      '#url' => Url::fromRoute('entity.event_registration.event_form', [
        'node' => $node->id(),
        'destination' => Url::fromRoute('<current>')->toString(),
      ]),
      '#attributes' => [
        'class' => ['button button-action'],
      ],
    ];
    $content['export'] = [
      '#title' => 'Export Registrant Email Addresses',
      '#type' => 'link',
      '#url' => Url::fromRoute('view.intercept_event_registration.rest_export_1', ['nid' => $node->id()]),
      '#attributes' => [
        'class' => ['button button-action'],
      ],
      '#prefix' => '&nbsp;&nbsp;'
    ];
    $content['list'] = $this->getListBuilder('event_registration', $node)->render();
    return $build;
  }

  /**
   * Gets the event_attendance list build array.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node entity.
   *
   * @return array
   *   The build render array.
   */
  public function attendance(NodeInterface $node) {
    $build = [];
    $build['export'] = [
      '#title' => 'Export Attendee Email Addresses',
      '#type' => 'link',
      '#url' => Url::fromRoute('view.intercept_event_attendance.rest_export_1', ['nid' => $node->id()]),
      '#attributes' => [
        'class' => ['button button-action'],
      ],
    ];
    $build['list'] = $this->getListBuilder('event_attendance', $node)->render();
    return $build;
  }

  /**
   * Node analysis task callback.
   */
  public function analysis(NodeInterface $node) {
    $event_uuid = $node->uuid();
    $event_nid = $node->id();
    $view_builder = $this->entityTypeManager()->getViewBuilder('node');

    return [
      '#theme' => 'node_event_analysis',
      '#content' => [
        'header' => $view_builder->view($node, 'header'),
        'attendance' => [
          'title' => $this->t('Number of Attendees'),
          'form' => $this->entityFormBuilder()->getForm($node, 'attendance'),
        ],
        'staff_evaluation' => [
          'title' => $this->t('Evaluate Your Event'),
          'form' => $this->evaluationManager->getStaffForm($node),
        ],
        'attendance_list' => [
          '#markup' => '<div id="eventAttendanceListRoot" data-event-uuid="' . $event_uuid . '" data-event-nid="' . $event_nid . '"></div>',
          '#attached' => [
            'library' => ['intercept_event/eventAttendanceList'],
          ],
        ],
        'customer_evaluations' => [
          '#markup' => '<div class="js-event-evaluations--attendee" data-event-uuid="' . $event_uuid . '" data-event-nid="' . $event_nid . '"></div>',
          '#attached' => [
            'library' => ['intercept_event/eventCustomerEvaluations'],
          ],
        ],
      ],
    ];
  }

  /**
   * This is the My Events page. Shows a list of events for the logged in
   * customer account.
   *
   * @return array
   *   The build render array.
   */
  public function myEvents() {
    $query_params = \Drupal::request()->query->all();
    $build = [];
    $build['#attached']['library'][] = 'intercept_base/evaluation';
    $build['#title'] = $this->t('My Events');
    $content = &$build['content'];

    // This is a 4-part query to get events.
    // 1) Get non-canceled registrations for the current user.
    $nids = [];
    $events = [];
    $query_er = $this->connection->select('event_registration', 'er');
    $query_er->join('event_registration__field_event', 'erfe', 'er.id = erfe.entity_id');
    $query_er->join('event_registration__field_user', 'erfu', 'er.id = erfu.entity_id');
    $query_er->addField('erfe', 'field_event_target_id');
    $query_er->condition('status', 'canceled', '!=');
    $query_er->condition('erfu.field_user_target_id', $this->currentUser->id());
    $result = $query_er->execute()->fetchAll();
    if (count($result) > 0) {
      foreach ($result as $value) {
        $nids[] = $value->field_event_target_id;
      }
    }

    // 2) Get attendances/scans for the current user.
    if (@$query_params['field_date_time_value'] == 1) { // Past Events
      $query_ea = $this->connection->select('event_attendance', 'ea');
      $query_ea->join('event_attendance__field_event', 'eafe', 'ea.id = eafe.entity_id');
      $query_ea->join('event_attendance__field_user', 'eafu', 'ea.id = eafu.entity_id');
      $query_ea->addField('eafe', 'field_event_target_id');
      $query_ea->condition('status', 1);
      $query_ea->condition('eafu.field_user_target_id', $this->currentUser->id());
      $result = $query_ea->execute()->fetchAll();
      if (count($result) > 0) {
        foreach ($result as $value) {
          $nids[] = $value->field_event_target_id;
        }
      }
    }
    
    // 3) Get the saves/flags for the current user.
    $query_f = $this->connection->select('flagging', 'f');
    $query_f->addField('f', 'entity_id');
    $query_f->condition('f.flag_id', 'saved_event');
    $query_f->condition('f.uid', $this->currentUser->id());
    $result = $query_f->execute()->fetchAll();
    if (count($result) > 0) {
      foreach ($result as $value) {
        $nids[] = $value->entity_id;
      }
    }

    // 4) Display only the event nodes that have nids in that array.
    if (count($nids) > 0) {

      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'event')
        ->condition('status', 1)
        ->condition('field_event_designation', 'events')
        ->condition('nid', $nids, 'IN')
        ->pager(30);

      // Query differences in Past vs. Upcoming:
      date_default_timezone_set('UTC');
      if (@$query_params['field_date_time_value'] == 1) { // Past Events
        $query->condition('field_date_time.end_value', date(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, strtotime('now')), '<');
        $query->sort('field_date_time.end_value', 'DESC');

        // Don't show canceled past events.
        $orGroup = $query->orConditionGroup()
          ->condition('field_event_status', 'canceled', '!=')
          ->condition('field_event_status', NULL, '=');
        $query->condition($orGroup);
      }
      else { // Upcoming Events
        $query->condition('field_date_time.end_value', date(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, strtotime('now')), '>=');
        $query->sort('field_date_time.value', 'ASC');
      }
      $events = $query->execute();
    }
    if (count($events) > 0) { // See if we still have any after specifying upcoming/past.
      $content['prefix']['#markup'] = '<div class="view__content l--section"><div class="list__wrapper"><ol class="list--content">';

      $allowed_tags = [
        'div', 'article', 'h3', 'h4', 'i', 'b', 'em', 'strong', 'li', 'p', 'span',
        'a', 'img', 'svg', 'title',
        'g', 'circle', 'path'
      ];

      // From: https://www.drupal.org/forum/support/module-development-and-code-questions/2019-04-24/how-to-integrate-a-render-array-into
      foreach ($events as $nid) {
        $node = $this->entityTypeManager->getStorage('node')->load($nid);

        // Temporarily change these values just for the display of the correct times.
        $date_item = $node->get('field_date_time')->getValue();
        $start_date = $this->dateUtility->getDrupalDate($date_item[0]['value']);
        $end_date = $this->dateUtility->getDrupalDate($date_item[0]['end_value']);
        $start_date = $this->dateUtility->convertTimezone($start_date, 'default')->format('Y-m-d\TH:i:s');
        $end_date = $this->dateUtility->convertTimezone($end_date, 'default')->format('Y-m-d\TH:i:s');
        $node->set('field_date_time', [
          'value'=> $start_date,
          'end_value' => $end_date
        ]);

        $view = $this->entityTypeManager->getViewBuilder('node')->view($node, 'evaluation_attendee');
        $content['nodes'][$nid]['#markup'] = '<li class="list__item">';
        $content['nodes'][$nid]['#markup'] .= $this->renderer->render($view);
        $content['nodes'][$nid]['#markup'] .= '</li>';
        $content['nodes'][$nid]['#allowed_tags'] = $allowed_tags;
      }

      $content['suffix']['#markup'] = '</ol></div></div>';

      $build['pager'] = [
        '#type' => 'pager'
      ];
    }
    else {
      if (@$query_params['field_date_time_value'] == 1) { // Past Events
        $content['prefix']['#markup'] = '<div class="view__content l--section">No matching past events were found.</div>';
      }
      else {
        $content['prefix']['#markup'] = '<div class="view__content l--section">No matching upcoming events were found.</div>';
      }
    }

    return $build;
  }

}
