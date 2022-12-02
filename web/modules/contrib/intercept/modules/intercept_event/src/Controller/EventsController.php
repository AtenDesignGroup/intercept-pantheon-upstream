<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\intercept_core\Utility\Dates;
use Drupal\intercept_event\EventEvaluationManager;
use Drupal\intercept_event\SuggestedEventsProvider;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Dompdf\Dompdf;

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
   * Gets the plugin ID string.
   *
   * @var string
   */
  protected $pluginId;

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
    $config_factory = \Drupal::service('config.factory');
    $settings = $config_factory->get('intercept_ils.settings');
    $intercept_ils_plugin = $settings->get('intercept_ils_plugin', '');
    if ($intercept_ils_plugin) {
      $ils_manager = \Drupal::service('plugin.manager.intercept_ils');
      $ils_plugin = $ils_manager->createInstance($intercept_ils_plugin);
      $this->client = $ils_plugin->getClient();
      $this->pluginId = $intercept_ils_plugin;
    }
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
    $toggle_filter = \Drupal::config('intercept_event.list')->get('toggle_filter');

    $build = [
      '#theme' => 'intercept_event_list',
      '#attached' => [
        'drupalSettings' => [
          'intercept' => [
            'events' => [
              'recommended' => $suggested_events,
              'toggle_filter' => $toggle_filter,
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
      '#prefix' => '&nbsp;&nbsp;',
    ];
    $content['attendance_sheet'] = [
      '#title' => 'Print Sign-In Sheet',
      '#type' => 'link',
      '#url' => Url::fromRoute('entity.node.attendance_sheet', [
        'node' => $node->id(),
      ]),
      '#attributes' => [
        'class' => ['button button-action'],
      ],
      '#prefix' => '&nbsp;&nbsp;',
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
   * Node staff evaluations task callback.
   */
  public function staffEvaluations(NodeInterface $node) {
    return $this->staffEvaluationBuildTable($node);
  }

  /**
   * All staff evaluations task callback. Builds multiple tables in a modal.
   */
  public function staffEvaluationsAll() {
    // Get specific node ids.
    $query_params = \Drupal::request()->query->all();
    $nids = explode(',', $query_params['nids']);
    if (empty($nids)) {
      return;
    }
    $nodes = Node::loadMultiple($nids);
    $tables = [];

    foreach($nodes as $node) {
      $tables[] = $this->staffEvaluationBuildTable($node);
    }
    return $tables;
  }

  /**
   * Builds a table (row and header) for a given node object.
   */
  private function staffEvaluationBuildTable(NodeInterface $node) {

    // Build rows first. We need to get a count to include in the header markup.
    $rows = [];

    $query = $this->connection->select('votingapi_vote', 'v');
    $query->condition('type', 'evaluation_staff');
    $query->isNotNull('feedback__value');
    $query->fields('v', [
      'feedback__value',
      'user_id'
    ]);
    $query->condition('entity_id', $node->id());
    $result = $query->execute()->fetchAll();
    if (count($result) > 0) {
      foreach ($result as $value) {
        $username = '';
        $user = User::load($value->user_id);
        if ($user) {
          $username = $user->name->value;
        }
        $rows[] = [
          'data' => [
            [
              'data' => new FormattableMarkup('@feedback<div class="right-align"><i>Feedback left by: @username</i></div>', [
                '@feedback' => $value->feedback__value,
                '@username' => $username
              ]),
              'colspan' => 2
            ]
          ]
        ];
      }
    }

    // Now we'll build the header since we have a count.
    $dateTime = new DrupalDateTime($node->get('field_date_time')->value, 'UTC');
    $event_date = date('n/j/y', $dateTime->getTimestamp());
    $count = count($rows);
    $header = [
      ['data' => $node->getTitle()],
      ['data' => new FormattableMarkup('@event_date&nbsp;&nbsp;&nbsp;•&nbsp;&nbsp;&nbsp;@count @comments', [
        '@event_date' => $event_date, 
        '@count' => $count,
        '@comments' => $count = 1 ? 'comment' : 'comments'
        ])]
    ];

    $build = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'class' => [
          'intercept-event-staff-evaluations-table__table',
        ],
      ]
    ];

    return $build;
  }

  /**
   * Node customer evaluations task callback.
   */
  public function customerEvaluations(NodeInterface $node) {
    return $this->customerEvaluationBuildTable($node);
  }

  /**
   * All customer evaluations task callback. Builds multiple tables in a modal.
   */
  public function customerEvaluationsAll() {
    // Get specific node ids.
    $query_params = \Drupal::request()->query->all();
    $nids = explode(',', $query_params['nids']);
    if (empty($nids)) {
      return;
    }
    $nodes = Node::loadMultiple($nids);
    $tables = [];

    foreach($nodes as $node) {
      $tables[] = $this->customerEvaluationBuildTable($node);
    }
    return $tables;
  }

  /**
   * Builds a table (row and header) for a given node object.
   */
  private function customerEvaluationBuildTable(NodeInterface $node) {

    // Build rows first. We need to get a count to include in the header markup.
    $rows = [];

    // Positive/negative taxonomy terms
    // Get the primary event type for this event (term).
    // should be Lifeskills (194)
    $event_type_primary = $node->get('field_event_type_primary')->target_id;
    // Get the positive/negative terms that are associated that term.
    $term = Term::load($event_type_primary);

    // Get the term names and ids for display.
    $positives = $negatives = [];
    $positive_terms = $term->get('field_evaluation_criteria_pos')->referencedEntities();
    foreach($positive_terms as $positive) {
      $positives[] = [
        'id' => $positive->id(),
        'name' => $positive->getName(),
        'count' => 0
      ];
    }
    $negative_terms = $term->get('field_evaluation_criteria_neg')->referencedEntities();
    foreach($negative_terms as $negative) {
      $negatives[] = [
        'id' => $negative->id(),
        'name' => $negative->getName(),
        'count' => 0
      ];
    }

    // Sums
    $percentage_positive = 0;
    $percentage_negative = 0;
    $query = $this->connection->select('votingapi_vote', 'v');
    $query->condition('type', 'evaluation');
    $query->condition('entity_id', $node->id());
    $query->addExpression('SUM(value=1) / COUNT(value) * 100', 'percent_positive_customer_evaluations');
    $query->addExpression('SUM(value=0) / COUNT(value) * 100', 'percent_negative_customer_evaluations');
    $result = $query->execute()->fetchAll();
    if (count($result) > 0) {
      foreach ($result as $value) {
        $percentage_positive = $value->percent_positive_customer_evaluations;$percentage_negative = $value->percent_negative_customer_evaluations;
      }
    }
    $rows[] = [
      [
        'data' => new FormattableMarkup('<div   class="feedback__wrapper">
          <span class="feedback feedback--positive"><span class="feedback__icon"></span> <b>@positive% Positive</b></span>
          </div>', [
            '@positive' => number_format($percentage_positive, 0),
          ]
        ),
        'colspan' => 2
      ],
      [
        'data' => new FormattableMarkup('<div   class="feedback__wrapper">
          <span class="feedback feedback--negative"><span class="feedback__icon"></span> <b>@negative% Negative</b></span>
          </div>', [
            '@negative' => number_format  ($percentage_negative, 0),
          ]
        ),
        'colspan' => 2
      ]
    ];

    // Individual evaluations
    $query = $this->connection->select('votingapi_vote', 'v');
    $query->condition('type', 'evaluation');
    $query->fields('v', [
      'vote_criteria',
      'value'
    ]);
    $query->condition('entity_id', $node->id());
    $result = $query->execute()->fetchAll();
    if (count($result) > 0) {
      foreach ($result as $value) {
        $vote_criteria = unserialize($value->vote_criteria);
        foreach ($vote_criteria['taxonomy_term'] as $taxonomy_term) {
          // Loop through the responses and each time a term pops up add to the total for that term.
          foreach($positives as $key => $positive) {
            if ($taxonomy_term == $positive['id']) {
              $positives[$key]['count']++;
            }
          }
          foreach($negatives as $key => $negative) {
            if ($taxonomy_term == $negative['id']) {
              $negatives[$key]['count']++;
            }
          }
        }
      }
    }

    // Print each one with a total.
    foreach($positives as $index => $positive) {
      $rows[] = [
        ['data' => $positive['name']],
        ['data' => new FormattableMarkup('<b>@id</b>', [
          '@id' => $positive['count']
        ])],
        ['data' => $negatives[$index]['name']],
        ['data' => new FormattableMarkup('<b>@id</b>', [
          '@id' => $negatives[$index]['count']
        ])],
      ];
    }

    // Now we'll build the header since we have a count.
    $dateTime = new DrupalDateTime($node->get('field_date_time')->value, 'UTC');
    $event_date = date('n/j/y', $dateTime->getTimestamp());
    $header = [
      ['data' => $node->getTitle(), 'colspan' => 3],
      ['data' => new FormattableMarkup('@event_date', [
        '@event_date' => $event_date
        ])]
    ];

    $build = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'class' => [
          'intercept-event-customer-evaluations-table__table',
        ],
      ]
    ];

    return $build;
  }

  /**
   * This is the Saved Events page. Shows a list of events for the logged in
   * customer account.
   *
   * @return array
   *   The build render array.
   */
  public function myEvents() {
    $query_params = \Drupal::request()->query->all();
    $build = [];
    $build['#attached']['library'][] = 'intercept_base/evaluation';
    $build['#title'] = $this->t('Saved Events');
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
    // Past Events
    if (@$query_params['field_date_time_value'] == 1) {
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
      // Past Events.
      if (@$query_params['field_date_time_value'] == 1) {
        $query->condition('field_date_time.end_value', date(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, strtotime('now')), '<');
        $query->sort('field_date_time.end_value', 'DESC');

        // Don't show canceled past events.
        $orGroup = $query->orConditionGroup()
          ->condition('field_event_status', 'canceled', '!=')
          ->condition('field_event_status', NULL, '=');
        $query->condition($orGroup);
      }
      // Upcoming Events.
      else {
        $query->condition('field_date_time.end_value', date(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, strtotime('now')), '>=');
        $query->sort('field_date_time.value', 'ASC');
      }
      $events = $query->execute();
    }
    // See if we still have any after specifying upcoming/past.
    if (count($events) > 0) {
      $content['prefix']['#markup'] = '<div class="view__content l--section"><div class="list__wrapper"><ol class="list--content">';

      $allowed_tags = [
        'div', 'article', 'h3', 'h4', 'i', 'b', 'em', 'strong', 'li', 'p', 'span',
        'a', 'img', 'svg', 'title',
        'g', 'circle', 'path',
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
          'value' => $start_date,
          'end_value' => $end_date,
        ]);

        $view = $this->entityTypeManager->getViewBuilder('node')->view($node, 'evaluation_attendee');
        $content['nodes'][$nid]['#markup'] = '<li class="list__item">';
        $content['nodes'][$nid]['#markup'] .= $this->renderer->render($view);
        $content['nodes'][$nid]['#markup'] .= '</li>';
        $content['nodes'][$nid]['#allowed_tags'] = $allowed_tags;
      }

      $content['suffix']['#markup'] = '</ol></div></div>';

      $build['pager'] = [
        '#type' => 'pager',
      ];
    }
    else {
      // Past Events.
      if (@$query_params['field_date_time_value'] == 1) {
        $content['prefix']['#markup'] = '<div class="view__content l--section">No matching past events were found.</div>';
      }
      else {
        $content['prefix']['#markup'] = '<div class="view__content l--section">No matching upcoming events were found.</div>';
      }
    }

    return $build;
  }

  /**
   * This is the main function for obtaining the attendance sheet.
   *
   * @param $node
   *   An integer that is the event ID. This should be passed into the function
   *   from the API call.
   */
  public function getEventAttendanceSheet($node) {

    $event = Node::load($node);
    $title = $event->getTitle();
    // Convert from stdObject to array.
    $registrations = \Drupal::service('intercept_event.manager')->getEventRegistrations($event);
    $arryResults = [];
    // Count multiple registrants from a single registration (children, spouses, etc.)
    $total_registrations = \Drupal::service('intercept_event.manager')->getEventActiveRegistrants($event);
    if ($total_registrations > count($registrations)) {
      foreach ($registrations as $index => $registration) {
        $arryResults[$index] = $registration;
        // Put it into the array multiple times if there are multiple people
        // registered so that it appears multiple times on the signup sheet.
        $num_registrants = $registration->get('field_registrants')->getTotal();
        if ($num_registrants > 1) {
          for ($n = 2; $n <= $num_registrants; $n++) {
            // Duplicate the entry to show multiple registered people.
            // Build a hopefully unique array index #.
            $id = $n * $index * 100;
            $arryResults[$id] = $registration;
          }
        }
      }
    }
    else {
      $arryResults = $registrations;
    }

    // Build an array of names to correspond with the array of event nodes.
    $names = [];
    foreach ($arryResults as $id => $registration) {
      $uid = $this->simplifyValues($registration->get('field_user')->getValue());
      if ($uid) {
        $authdata = $this->getAuthdata($uid);
        if (!empty($authdata)) {
          $names[$id]['name_first'] = $authdata->NameFirst;
          $names[$id]['name_last'] = $authdata->NameLast;
          $names[$id]['barcode'] = $authdata->Barcode;
        }
        if (empty($names[$id]['name_last']) && empty($names[$id]['name_first'])) {
          $account = User::load($uid);
          $names[$id]['name_last'] = $account->getUsername();
        }
        $names[$id]['name_full'] = $names[$id]['name_last'] . ', ' . $names[$id]['name_first'];
      }
    }

    // Let's sort the array by Last Name, First Name to make it easier to find
    // names when the list is printed out and we're tracking attendance from
    // the sign-in sheet.
    uasort($names, function ($a, $b) {
      return strcasecmp($a['name_full'], $b['name_full']);
    });
    // Use that sorting outcome to sort $arryResults which contains the entities.
    $arryResultsSorted = [];
    foreach ($names as $id => $name) {
      $arryResultsSorted[$id] = $arryResults[$id];
    }

    // Get list of locations.
    $locations = [];
    $locations[] = Node::load($this->simplifyValues($event->get('field_location')->getValue()));

    $strCSS = $this->getPdfStyle();
    $strHTML = "<html>\n\t<head>\n\t\t<title>$title</title>\n\t\t$strCSS";
    $strHTML .= "\n\t</head>\n\t<body>";

    // Fix timezone to default of site.
    $date_item = $event->get('field_date_time')->getValue();
    $start_date = $this->dateUtility->getDrupalDate($date_item[0]['value']);
    $end_date = $this->dateUtility->getDrupalDate($date_item[0]['end_value']);
    $strEventDateFull = $this->dateUtility->convertTimezone($start_date, 'default')->format('F j, Y');
    $strEventTimeRange = $this->dateUtility->convertTimezone($start_date, 'default')->format('g:i A') . " to " . $this->dateUtility->convertTimezone($end_date, 'default')->format('g:i A');

    // Generate A Signup page for each Location.
    foreach ($locations as $intLocationID => $location) {
      $strPageHTML = "";
      $intLocationID = $location->Id();
      $strEventLocation = $location->getTitle();

      /*
      Calculate the number of attendees.
      The number of attendees per page depends on the number registrations
      we have for an event.

      This is because the line height for each registrant is smaller than
      the empty space for those who did not register.

      We are looking to have approximately 18 registrants per page. This
      number takes into account the header for each page as well as
      registrants.

      If there are 10 or fewer registrants for a particular location, the
      script will fill the remaining lines on the page with manual
      registrations.

      If there are more than 10, but fewer than 18 registrants, a second
      page with just manual registrations will be create for the location.

      This same idea applies to the last page of the registrants for a
      particular location.
       */

      $intItemsPerPage = 18;
      (int) $intNumPages = ceil($total_registrations / $intItemsPerPage);
      // Round up to the next whole number since we can't have half pages.
      $intExpectedItemCount = $intItemsPerPage * $intNumPages;

      // Calculate difference between the remaining items and the expected items.
      $intDifference = $intExpectedItemCount - $total_registrations;

      // If the difference is zero, or greater than 10, add a page
      // for blank lines.
      if ($intDifference == 0 || ($intItemsPerPage - $intDifference) > 12) {
        $intNumPages++;
      }

      $intTempOffset = 0;
      $intPageID = 1;
      $intCurrPage = 0;
      while ($intCurrPage < $intNumPages) {
        $intTempOffset = $intItemsPerPage * $intCurrPage;
        $arryPageData = array_slice($arryResultsSorted, $intTempOffset, $intItemsPerPage, TRUE);

        $strPageType = "registrations";
        if (empty($arryPageData)) {
          $strPageType = "blanklines";
        }
        $intPageItemCount = count($arryPageData);

        $strPageOfPages = "(" . $intPageID . " of " . $intNumPages . ")";
        $strPageHTML = '
        <table class="table">
          <thead>
            <tr class="header-tr">
              <td colspan="2" class="sign-in">' . $strPageOfPages . ' Sign-in sheet for:</td>
              <td colspan="1" class="event-date-time">' . $strEventDateFull . ' - ' . $strEventTimeRange . '</td>
            </tr>
            <tr class="header-tr">
              <td colspan="2" class="event-title">' . $title . '</td>
              <td colspan="1" class="event-location">' . $strEventLocation . '</td>
            </tr>
          </thead>
        </table>';

        $strPageHTML .= '
        <table class="table">
          <tbody>
            <tr class="header">
              <td class="header-last-name">Last Name</td>
              <td class="header-first-name">First Name</td>
              <td class="header-sign-in">Sign In</td>
            </tr>';

        if ($strPageType == "registrations") {
          foreach ($arryPageData as $id => $arryRegistrationInfo) {

            $strPageHTML .= '
            <tr>
              <td class="container">' . $names[$id]['name_last'] . '</td>
              <td class="container">' . $names[$id]['name_first'] . '</td>
              <td class="border-bottom-1-black" class="container">&nbsp;</td>
            </tr>';
          }

          // If we have more 10 or fewer attendees on this page, fill the
          // Remaining lines with manual sign-in lines.
          if ($intPageItemCount < $intItemsPerPage) {
            $intDifference = $intItemsPerPage - $intPageItemCount;
            $intI = 0;
            // The blank lines are larger than the normal lines, so we
            // need to have one fewer blank line to fit on the page.
            while ($intI < $intDifference) {
              $strPageHTML .=
              '<tr class="sign-in-blank">
                <td><div class="border-bottom-1-black">&nbsp;</div></td>
                <td><div class="border-bottom-1-black">&nbsp;</div></td>
                <td id="sign-in-line"><div class="border-bottom-1-black">&nbsp;</div></td>
              </tr>';
              $intI++;
            }
          }
        }
        elseif ($strPageType == "blanklines") {
          $intI = 0;
          // Need to adjust for line height differences.
          while ($intI <= $intItemsPerPage - 1) {
            $strPageHTML .= '
            <tr class="sign-in-blank">
              <td><div class="border-bottom-1-black">&nbsp;</div></td>
              <td><div class="border-bottom-1-black">&nbsp;</div></td>
              <td id="sign-in-line"><div class="border-bottom-1-black">&nbsp;</div></td>
            </tr>
            ';

            $intI++;
          }
        }
        $strPageHTML .= '
          </tbody>
        </table>';
        // $strPageHTML .= '<div class="page-break"></div>';
        $strHTML .= $strPageHTML;

        $intCurrPage++;
        $intPageID++;
      }
    }

    $strHTML .= "\n\t</body>\n</html>";

    // DEBUGGING for strHTML
    // ini_set('xdebug.var_display_max_data', -1);
    // print '<pre>';
    // var_dump($strHTML);
    // print '</pre>';
    // exit;
    // End debugging.
    $dompdf = new Dompdf();
    $options = $dompdf->getOptions();
    $options->setIsHtml5ParserEnabled(TRUE);
    $dompdf->setOptions($options);
    $dompdf->setPaper('letter', 'landscape');
    $dompdf->load_html($strHTML);
    $dompdf->render();

    // Set PDF File name.
    $arryReplaceFile = [" ", "/", "?", "!"];
    $arryReplaceFileWith = ["-", "", "", ""];
    $strFileEventTitle = strtolower(str_ireplace($arryReplaceFile, $arryReplaceFileWith, $title));
    $strFileName = $event->Id() . "--" . $strFileEventTitle . "--attendance.pdf";

    // Stream/Download PDF File.
    $dompdf->stream($strFileName);

    return new JsonResponse("", 200);
  }

  /**
   * This defines various CSS classes within the PDF.
   *
   * This is here to make it easier to separate out the CSS styles instead
   * of using them inline.
   */
  protected function getPdfStyle() {
    $strStyle = <<< EOF
    <style>
    \t\t\tbody { font-family: DejaVu Serif; font-size: 12px; }
    \t\t\ttr { font-family: DejaVu Serif; font-size: 12px; height: 4em !important; }

    \t\t\t.border-bottom-1-black { border-bottom: 1px solid #000; }

    \t\t\t.clear { clear: both; }
    \t\t\t.container { height: 2rem; vertical-align: bottom; padding-bottom: 9.5px; padding-bottom:0px; }

    \t\t\t.event-date-time { text-align: right; }
    \t\t\t.event-title { font-weight: 600; font-size: 1.5em; }
    \t\t\t.event-location { text-align: right; }

    \t\t\t.header { text-align: left; font-size: 1.2em; font-weight: 600; }
    \t\t\t.header-last-name { width: 20%; }
    \t\t\t.header-first-name { width: 20%; }
    \t\t\t.header-organization { width: 30%; }
    \t\t\t.header-sign-in { width: 30%;}
    \t\t\t.header-tr { font-weight: 350; }

    \t\t\t.page-break { page-break-after: always; }

    \t\t\t.sign-in { font-weight: 600; }
    \t\t\t.sign-in-blank { font-size: 1.6em;}
    \t\t\t.sign-in-blank > td { padding-left: 0; padding-right: 15px; }
    \t\t\ttd#sign-in-line { padding-right: 0px !important; margin-right:0px; }

    \t\t\t.table { margin-left: 0px; margin-right: 0px; width: 100%; }

    \t\t</style>
    EOF;

    return $strStyle;
  }

  /**
   * Convert from sub-arrays with target_id to simple arrays.
   */
  private function simplifyValues($values) {
    $result = array_column($values, 'target_id');
    return $result[0];
  }

  /**
   * Get authdata for user in the row in order to display customer info.
   */
  protected function getAuthdata($uid) {
    if ($this->pluginId) {
      $authmap = \Drupal::service('externalauth.authmap');
      $authdata = $authmap->getAuthdata($uid, $this->pluginId);
      $authdata_data = unserialize($authdata['data']);

      return $authdata_data;
    }
    return NULL;
  }

  /**
   * Shows staff a list of disclaimers that can appear for customers.
   * This is linked from the description on that field on the event node form.
   */
  public function disclaimers() {

    $build = [];
    $content = &$build['content'];
    $content['title'] = [
      '#markup' => '<h1 class="title">Event Disclaimers</h1>',
    ];
    $content['overview'] = [
      '#markup' => '<p>This page shows a listing of all available disclaimers that staff can add to events for customers to see.</p>',
    ];

    // Get taxonomy term list for custom display.
    /** @var TermStorageInterface $termStorage */
    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $terms = $termStorage->loadTree('disclaimer');

    foreach ($terms as $term) {
      $content['divider_' . $term->tid] = [
        '#markup' => '<hr>',
      ];
      $content['term_name_' . $term->tid] = [
        '#markup' => '<p><strong>' . $term->name . '</strong></p>',
      ];
      $content['term_description_' . $term->tid] = [
        '#markup' => $term->description__value,
      ];
    }
    return $build;
  }

}
