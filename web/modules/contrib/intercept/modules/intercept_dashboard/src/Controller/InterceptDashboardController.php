<?php

namespace Drupal\intercept_dashboard\Controller;

use DateInterval;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Intercept Dashboard routes.
 */
class InterceptDashboardController extends ControllerBase {
  /**
   * EntityQuery service.
   *
   * @var QueryInterface $entityQuery
   */
  protected $entityQuery;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
  */
  protected $database;

  /**
   * The total number of events returned.
   *
   * @var int
  */
  protected $totalEvents;

  /**
   * FilterProvider service.
   *
   * @var \Drupal\intercept_dashboard\FilterProviderInterface $filterProvider
   */
  protected $filterProvider;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Http\RequestStack
   */
  protected $currentRequest;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->entityQuery = $container->get('entity.query.sql');
    $instance->database = $container->get('database');
    $instance->filterProvider = $container->get('intercept_dashboard.filterProvider');
    $instance->currentRequest = $container->get('request_stack')->getCurrentRequest();
    return $instance;
  }

  /**
   * Builds the Intercept Event Data Dashboard page.
   */
  public function dashboard() {
    /**
     * Constructs the Intercept Events data dashboard.
     */
    $build = [
      '#theme' => 'intercept_dashboard',
    ];

    /**
     * Filters
     */
    $build['#filters'] = $this->formBuilder()
      ->getForm('Drupal\intercept_dashboard\Form\DashboardEventFilters');
    $build['#filters_summary'] = $this->buildFilterSummary();

    /**
     * Data Summary
     */
    $build['#summary']['total_events'] = $this->buildTotalEvents($this->getTotalEvents());
    $build['#summary']['total_attendees'] = $this->buildTotalAttendees();
    $build['#summary']['total_checked_in'] = $this->buildTotalAttendeesCheckedIn();
    $build['#summary']['total_saves'] = $this->buildTotalSaves();
    // The following lines are not rendered but are helpful for debugging.
    // $build['#summary']['total_registrants'] = $this->buildTotalRegistrants();

    $totalCustomerEvaluations = $this->queryTotalCustomerEvaluations();
    $build['#summary']['percent_positive_customer_evaluations'] = $this->buildPercentPositiveCustomerEvaluations($totalCustomerEvaluations['percent_positive_customer_evaluations']);
    $build['#summary']['percent_negative_customer_evaluations'] = $this->buildPercentNegativeCustomerEvaluations($totalCustomerEvaluations['percent_negative_customer_evaluations']);
    $build['#summary']['total_customer_evaluations'] = $this->buildTotalCustomerEvaluations($totalCustomerEvaluations['total_customer_evaluations']);
    // The following lines are not rendered but are helpful for debugging.
    // $build['#summary']['positive_customer_evaluations'] = $this->buildTotalPositiveCustomerEvaluations($totalCustomerEvaluations['total_positive_customer_evaluations']);
    // $build['#summary']['negative_customer_evaluations'] = $this->buildTotalNegativeCustomerEvaluations($totalCustomerEvaluations['total_negative_customer_evaluations']);
    $build['#summary']['total_staff_evaluations'] = $this->buildTotalStaffEvaluations();

    /**
     * Data Table
     */
    $build['#event_table'] = $this->buildEventTable();

    $build['#charts'] = [];

    /**
     * Attendees by Event Type chart
     */
    $build['#charts']['by_primary_event_type'] = $this->buildBarChart(
      'attendeesByPrimaryEventType',
      $this->t('Attendance by Primary Event Type'),
      $this->getAttendeesByPrimaryEventTypeData(),
    );

    return $build;
  }

  /**
   * Constructs the base query to select events based on
   * the provided filter values.
   *
   * @return SelectInterface
   */
  public function getBaseQuery() {
    // Get filter and sort criteria from url query params.
    $params = $this->currentRequest->query->all();

    $query = $this->database->select('node_field_data', 'n');
    // $query->fields('n', ['nid']);
    $query->condition('n.type', 'event');
    $query->condition('n.status', 1);

    /**
     * @todo: Make sure the default value is shared with the form.
     */
    $startDate = new DrupalDateTime($params['start'] ?? date('Y-m-01'));
    $startDate->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $startFormatted = $startDate->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    /**
     * @todo: Make sure the default value is shared with the form.
     */
    $endDate = new DrupalDateTime($params['end'] ?? date('Y-m-d'));
    // Increase the date by 1 day to include the current day.
    $endDate->add(DateInterval::createFromDateString('1 day'));
    $endDate->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $endFormatted = $endDate->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    $query->join('node__field_date_time', 'date', 'n.nid = date.entity_id');
    $query->condition('date.field_date_time_value', [$startFormatted, $endFormatted], 'BETWEEN');

    // Add keyword condition.
    // @todo: Can this be integrated into search_api?
    if (isset($params['keyword']) && !empty($params['keyword'])) {
      $query->leftJoin('node__field_text_content', 'text_content', 'n.nid = text_content.entity_id');
      $query->leftJoin('node__field_text_intro', 'text_intro', 'n.nid = text_intro.entity_id');
      $query->leftJoin('node__field_text_teaser', 'text_teaser', 'n.nid = text_teaser.entity_id');
      $keywordCondition = $query->orConditionGroup()
        ->condition('n.title', '%' . $this->database->escapeLike($params['keyword']) . '%', 'LIKE')
        ->condition('text_content.field_text_content_value', '%' . $this->database->escapeLike($params['keyword']) . '%', 'LIKE')
        ->condition('text_intro.field_text_intro_value', '%' . $this->database->escapeLike($params['keyword']) . '%', 'LIKE')
        ->condition('text_teaser.field_text_teaser_value', '%' . $this->database->escapeLike($params['keyword']) . '%', 'LIKE');
      $query->condition($keywordCondition);
    }

    // Add created_by condition.
    if (isset($params['created_by']) && !empty($params['created_by'])) {
      $query->condition('n.uid', array_values($params['created_by'][0]), 'IN');
    }

    // Add primary audience condition.
    if (isset($params['audience']) && !empty($params['audience'])) {
      $query->join('node__field_audience_primary', 'audience', 'n.nid = audience.entity_id AND audience.field_audience_primary_target_id IN (:audience[])', [':audience[]' => $params['audience']]);
    }

    // Add primary event type condition.
    if (isset($params['type']) && !empty($params['type'])) {
      $query->join('node__field_event_type_primary', 'event_type', 'n.nid = event_type.entity_id AND event_type.field_event_type_primary_target_id IN (:type[])', [':type[]' => $params['type']]);
    }

    // Add tags condition.
    if (isset($params['tags']) && !empty($params['tags'])) {
      $query->join('node__field_event_tags', 'event_tags', 'n.nid = event_tags.entity_id AND event_tags.field_event_tags_target_id IN (:tags[])', [':tags[]' => $params['tags']]);
    }

    // Add location condition.
    if (isset($params['location']) && !empty($params['location'])) {
      $query->join('node__field_location', 'location', 'n.nid = location.entity_id AND location.field_location_target_id IN (:location[])', [':location[]' => $params['location']]);
    }

    // Add event series condition.
    if (isset($params['event_series']) && !empty($params['event_series'])) {
      $query->join('node__field_event_series', 'event_series', 'n.nid = event_series.entity_id AND event_series.field_event_series_target_id IN (:event_series[])', [':event_series[]' => $params['event_series']]);
    }

    // Add external presenter condition.
    if (isset($params['external_presenter']) && !empty($params['external_presenter'])) {
      $query->leftJoin('node__field_presenter', 'presenter', 'n.nid = presenter.entity_id');
      if ($params['external_presenter'] === 'yes') {
        $query->isNotNull('presenter.field_presenter_value');
      } else {
        $query->isNull('presenter.field_presenter_value');
      }
    }

    return $query;
  }

  /**
   * Builds the filter summary render array that shows the current filters set.
   *
   * @return array Render array
   */
  public function buildFilterSummary() {
    $params = $this->currentRequest->query->all();
    $filters = [];

    if (isset($params['keyword'])) {
      $filters[] = $this->buildCurrentFilter(
        'keyword',
        $this->t('Keywords')
      );
    }

    if (isset($params['type'])) {
      $filters[] = $this->buildCurrentFilter(
        'type',
        $this->t('Event Type'),
        $this->filterProvider->getRelatedTermOptions('event_type')
      );
    }

    if (isset($params['audience'])) {
      $filters[] = $this->buildCurrentFilter(
        'audience',
        $this->t('Audience'),
        $this->filterProvider->getRelatedTermOptions('audience')
      );
    }

    if (isset($params['location'])) {
      $filters[] = $this->buildCurrentFilter(
        'location',
        $this->t('Location'),
        $this->filterProvider->getRelatedContentOptions('location')
      );
    }

    if (isset($params['event_series'])) {
      $filters[] = $this->buildCurrentFilter(
        'event_series',
        $this->t('Series'),
        $this->filterProvider->getRelatedContentOptions('event_series')
      );
    }

    if (isset($params['tags'])) {
      $filters[] = $this->buildCurrentFilter(
        'tags',
        $this->t('Tags'),
        $this->filterProvider->getRelatedTermOptions('tag')
      );
    }

    if (isset($params['created_by'])) {
      $filters[] = $this->buildCurrentFilter(
        'created_by',
        $this->t('Created by'),
        $this->filterProvider->getRelatedUserOptions(array_values($params['created_by'][0]))
      );
    }

    if (isset($params['external_presenter'])) {
      $filters[] = $this->buildCurrentFilter(
        'external_presenter',
        $this->t('External Presenter'),
        [
          'yes' => $this->t('Yes'),
          'no' => $this->t('No'),
        ],
      );
    }

    $build = [
      'summary' => [
        '#markup' => $this->t('Showing data for @total events.', [
          '@total' => number_format($this->getTotalEvents()),
        ])
      ],
    ];

    if (!empty($filters)) {
      $build['current_filters'] = [
        '#theme' => 'intercept_current_filters',
        '#filters' => $filters,
      ];
    }

    return $build;
  }

  /**
   * Get a partial render array for a specific filter value.
   *
   * @param string $param
   *   The related Url query parameter.
   * @param string $label
   *   Human-readable filter set label.
   * @param string[] $options
   *   (optional) Id Label pairs. Used to determine the human-readable label for the filter.
   * @return void
   */
  public function buildCurrentFilter($param, $label, $options = NULL) {
    $params = $this->currentRequest->query->all();

    $filter = [
      'label' => $label,
      'values' => [],
    ];

    if (is_array($params[$param])) {
      foreach ($params[$param] as $value) {
        $value = is_array($value) ? reset($value) : $value;
        $filter['values'][] = [
          'remove_url' => $this->filterProvider->getRemoveUrl($param, $value),
          'text' => isset($options) ? $options[$value] : $value,
        ];
      }
    } else {
      $value = $params[$param];
      $filter['values'][] = [
        'remove_url' => $this->filterProvider->getRemoveUrl($param),
        'text' => isset($options) ? $options[$value] : $value,
      ];
    }
    return $filter;
  }

  /**
   * Builds the percent of total Customer Evaluations that are negative render array.
   *
   * @return array Render array
   */
  public function buildPercentNegativeCustomerEvaluations($value) {

    $build = [
      '#theme' => 'intercept_dashboard_metric',
      '#label' => $this->t('Negative reviews of all events'),
      '#value' => number_format($value ?? 0) . '%',
      '#cache' => [
        'context' => 'url',
      ]
    ];
    return $build;
  }

  /**
   * Builds the percent of total Customer Evaluations that are positive render array.
   *
   * @return array Render array
   */
  public function buildPercentPositiveCustomerEvaluations($value) {

    $build = [
      '#theme' => 'intercept_dashboard_metric',
      '#label' => $this->t('Positive reviews of all events'),
      '#value' => number_format($value ?? 0) . '%',
      '#cache' => [
        'context' => 'url',
      ]
    ];
    return $build;
  }

  /**
   * Builds the Total Customer Evaluations render array.
   *
   * @return array Render array
   */
  public function buildTotalCustomerEvaluations($value) {

    $build = [
      '#theme' => 'intercept_dashboard_metric',
      '#label' => $this->t('Events with Customer Feedback'),
      '#value' => number_format($value ?? 0),
      '#cache' => [
        'context' => 'url',
      ]
    ];
    // @todo: Provide a view of all feedback.
    // if ($value > 0) {
    //   $build['#link'] = Link::createFromRoute($this->t('View All', []), '<current>', [], [
    //     'attributes' => [
    //       'title' => $this->t('View all staff comments'),
    //       'rel' => 'nofollow'
    //     ],
    //   ]);
    // }
    return $build;
  }

  /**
   * Builds the total Negative Customer Evaluations render array.
   *
   * @return array Render array
   */
  public function buildTotalNegativeCustomerEvaluations($value) {

    $build = [
      '#theme' => 'intercept_dashboard_metric',
      '#label' => $this->t('Negative reviews of all events'),
      '#value' => number_format($value ?? 0),
      '#cache' => [
        'context' => 'url',
      ]
    ];
    return $build;
  }

  /**
   * Builds the total positive Customer Evaluations render array.
   *
   * @return array Render array
   */
  public function buildTotalPositiveCustomerEvaluations($value) {

    $build = [
      '#theme' => 'intercept_dashboard_metric',
      '#label' => $this->t('Positive reviews of all events'),
      '#value' => number_format($value ?? 0),
      '#cache' => [
        'context' => 'url',
      ]
    ];
    return $build;
  }

  /**
   * Builds the total attendees for all events.
   *
   * @return array Render array of the total attendees metric.
   */
  public function buildTotalAttendees() {
    $eventQuery = $this->getBaseQuery();
    $eventQuery->join($this->getAttendeesTotalSubQuery(), 'attendees', 'n.nid = attendees.nid');
    $eventQuery->addExpression('SUM(attendees)', 'total_attendees');

    $count = $eventQuery
      ->execute()
      ->fetchAssoc()['total_attendees'];

    $build = [
      '#theme' => 'intercept_dashboard_metric',
      '#label' => $this->t('Attendees'),
      '#value' => number_format($count ?? 0),
      '#cache' => [
        'context' => 'url',
      ]
    ];
    return $build;
  }

  /**
   * Render total staff evaluations.
   *
   * @return array Render array.
   */
  public function buildTotalStaffEvaluations() {
    $eventQuery = $this->getBaseQuery();
    $eventQuery->join($this->getStaffEvaluationsSubQuery(), 'staff_evaluations', 'n.nid = staff_evaluations.nid');
    $eventQuery->addExpression('SUM(staff_evaluations)', 'total_staff_evaluations');

    $count = $eventQuery
      ->execute()
      ->fetchAssoc()['total_staff_evaluations'];

    $build = [
      '#theme' => 'intercept_dashboard_metric',
      '#label' => $this->t('Events with Staff Feedback'),
      '#value' => number_format($count ?? 0),
      '#cache' => [
        'context' => 'url',
      ]
    ];

    if ($count > 0) {
      // @todo: Provide a view of all feedback.
      // $build['#link'] = Link::createFromRoute($this->t('View All', []), '<current>', [], [
      //   'attributes' => [
      //     'title' => $this->t('View all staff comments'),
      //     'rel' => 'nofollow'
      //   ],
      // ]);
    }
    return $build;
  }

  /**
   * Queries the Total Events and formats it in a render array.
   *
   * @return array Render array
   */
  public function getTotalEvents() {
    if (!isset($this->totalEvents)) {
      $query = $this->getBaseQuery();
      $this->totalEvents = $query
        ->countQuery()
        ->execute()
        ->fetchField();
    }

    return $this->totalEvents;
  }

  /**
   * Queries the Total Events and formats it in a render array.
   *
   * @return array Render array
   */
  public function buildTotalEvents($value) {
    $build = [
      '#theme' => 'intercept_dashboard_metric',
      '#label' => $this->t('Total Events'),
      '#value' => number_format($value ?? 0),
      '#cache' => [
        'context' => 'url',
      ]
    ];
    return $build;
  }

  /**
   * Queries the Total Attendees Checked-in.
   *
   * @return array Render array
   */
  public function buildTotalAttendeesCheckedIn() {
    // Join the attendee counts to the eventQuery.
    $eventQuery = $this->getBaseQuery();
    $eventQuery->join($this->getCheckedInSubQuery(), 'checked_in', 'n.nid = checked_in.nid');
    $eventQuery->addExpression('SUM(checked_in)', 'total_checked_in');

    $count = $eventQuery
      ->execute()
      ->fetchAssoc()['total_checked_in'];

    $build = [
      '#theme' => 'intercept_dashboard_metric',
      '#label' => $this->t('Attendees checked in'),
      '#value' => number_format($count ?? 0),
      '#cache' => [
        'context' => 'url',
      ]
    ];

    return $build;
  }

  /**
   * Queries the total Registrations count and returns a render array of the data.
   *
   * @return array Render array
   */
  public function buildTotalRegistrants() {
    // Join the attendee counts to the eventQuery.
    $eventQuery = $this->getBaseQuery();
    $eventQuery->join($this->getRegistrantsSubQuery(), 'registrants', 'n.nid = registrants.nid');
    $eventQuery->addExpression('SUM(registrants)', 'total_registrants');

    $count = $eventQuery
      ->execute()
      ->fetchAssoc()['total_registrants'];

    $build = [
      '#theme' => 'intercept_dashboard_metric',
      '#label' => $this->t('Registrants'),
      '#value' => number_format($count ?? 0),
      '#cache' => [
        'context' => 'url',
      ]
    ];

    return $build;
  }

  /**
   * Builds the total saves for all events.
   *
   * @return array Render array.
   */
  public function buildTotalSaves() {
    $eventQuery = $this->getBaseQuery();
    $eventQuery->join($this->getSavedEventsSubQuery(), 'saves', 'n.nid = saves.nid');
    $eventQuery->addExpression('SUM(saves)', 'total_saves');

    $count = $eventQuery
      ->execute()
      ->fetchAssoc()['total_saves'];

    $build = [
      '#theme' => 'intercept_dashboard_metric',
      '#label' => $this->t('Event Saves'),
      '#value' => number_format($count ?? 0),
      '#cache' => [
        'context' => 'url',
      ]
    ];
    return $build;
  }


  /**
   * Generates a sortable paginated table view of filtered events
   *
   * @return array Render array.
   */
  public function buildEventTable() {

    $header = [
      // Keep for debugging purposes.
      // [
      //   'data' => $this->t('NID'),
      //   'field' => 'nid',
      //   'initial_click_sort' => 'asc',
      // ],
      [
        'data' => $this->t('Event'),
        'field' => 'title',
        'initial_click_sort' => 'asc',
      ],
      [
        'data' => $this->t('Date'),
        'field' => 'field_date_time_value',
        'sort' => 'desc',
      ],
      [
        'data' => $this->t('Attendees'),
        'field' => 'attendees',
        'initial_click_sort' => 'desc',
      ],
      [
        'data' => $this->t('Checked In'),
        'field' => 'checked_in',
        'initial_click_sort' => 'desc',
      ],
      [
        'data' => $this->t('Registrants'),
        'field' => 'registrants',
        'initial_click_sort' => 'desc',
      ],
      [
        'data' => $this->t('Saved Event'),
        'field' => 'saves',
        'initial_click_sort' => 'desc',
      ],
      [
        'data' => $this->t('Customer Rating'),
        'field' => 'percent_positive_customer_evaluations',
        'initial_click_sort' => 'desc',
      ],
      [
        'data' => $this->t('Customer Feedback'),
        'field' => 'customer_evaluations',
        'initial_click_sort' => 'desc',
      ],
      [
        'data' => $this->t('Staff Comments'),
        'field' => 'staff_evaluations',
        'initial_click_sort' => 'desc',
      ],
    ];

    $eventQuery = $this->getBaseQuery();
    $eventQuery->fields('n', [
      'nid',
      'title'
    ]);
    $eventQuery->fields('date', [
      'field_date_time_value'
    ]);

    // Attendees
    $eventQuery->leftJoin($this->getAttendeesTotalSubQuery(), 'attendees', 'n.nid = attendees.nid');
    $eventQuery->fields('attendees', [
      'attendees'
    ]);

    // Checked-in
    $eventQuery->leftJoin($this->getCheckedInSubQuery(), 'checked_in', 'n.nid = checked_in.nid');
    $eventQuery->fields('checked_in', [
      'checked_in'
    ]);

    // Saves
    $eventQuery->leftJoin($this->getSavedEventsSubQuery(), 'saves', 'n.nid = saves.nid');
    $eventQuery->fields('saves', [
      'saves'
    ]);

    // Registrants
    $eventQuery->leftJoin($this->getRegistrantsSubQuery(), 'registrants', 'n.nid = registrants.nid');
    $eventQuery->fields('registrants', [
      'registrants'
    ]);

    // Customer Evaluations
    $eventQuery->leftJoin($this->getCustomerEvaluationsSubQuery(), 'customer_evaluations', 'n.nid = customer_evaluations.nid');
    $eventQuery->fields('customer_evaluations', [
      'percent_positive_customer_evaluations',
      'percent_negative_customer_evaluations',
      'customer_evaluations',
    ]);

    // Staff Evaluations
    $eventQuery->leftJoin($this->getStaffEvaluationsSubQuery(), 'staff_evaluations', 'n.nid = staff_evaluations.nid');
    $eventQuery->fields('staff_evaluations', [
      'staff_evaluations',
    ]);

    /** @var TableSortExtender $table_sort */
    $table_sort = $eventQuery->extend('Drupal\Core\Database\Query\TableSortExtender');
    $table_sort->orderByHeader($header);

    /** @var PagerSelectExtender $pager */
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $pager->limit(20);

    $result = $pager->execute();

    // Populate the rows.
    $rows = [];
    foreach($result as $row) {
      $date = new DrupalDateTime($row->field_date_time_value);
      $rows[] = [
        'data' => [
          // Keep for debugging purposes.
          // 'nid' => $row->nid,
          'title' => new FormattableMarkup('
              <a href="@url">@title</a>',
              [
                '@title' => $row->title,
                '@url' => Url::fromRoute('entity.node.canonical', ['node' => $row->nid])->toString()
              ]
          ),
          'date' => $date->format('n/j/y'),
          'attendees' => number_format($row->attendees ?? 0),
          'checked_in' => number_format($row->checked_in ?? 0),
          'registrants' => number_format($row->registrants ?? 0),
          'saves' => number_format($row->saves ?? 0),
          'customer_rating' => $row->customer_evaluations ?
            new FormattableMarkup('<div class="feeback__wrapper">
                <span class="feedback feedback--positive"><span class="feedback__icon"></span> <span class="visually-hidden">Positive</span>@positive<span class="feedback__suffix">%</span></span>
                <span class="feedback feedback--negative"><span class="feedback__icon"></span> <span class="visually-hidden">Negative</span> @negative<span class="feedback__suffix">%</span></span>
            </div>', [
              '@positive' => number_format($row->percent_positive_customer_evaluations, 0),
              '@negative' => number_format($row->percent_negative_customer_evaluations, 0),
            ]) :
            new FormattableMarkup('<span>—</span>', []),
          'customer_evaluations' => $row->customer_evaluations ?
            new FormattableMarkup('
              <a href="@url">View <span class="visually-hidden">Customer Evaluations</span></a>',
              [
                '@url' => Url::fromRoute('entity.node.analysis', ['node' => $row->nid])->toString()
              ]
              ) :
            '—',
          'staff_evaluations' => $row->staff_evaluations ?
            new FormattableMarkup('
              <a href="@url">View <span class="visually-hidden">Staff Comments</span></a>',
              [
                '@url' => Url::fromRoute('entity.node.analysis', ['node' => $row->nid])->toString()
              ]
              ) :
            '—',
        ]
      ];
    }

    // Generate the table.
    $build['event_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'class' => [
          'intercept-dashboard-table__table'
        ]
      ]
    );

    // Finally add the pager.
    $build['pager'] = array(
      '#type' => 'pager',
      '#quantity' => 5,
      '#attributes' => [
        'class' => [
          'intercept-dashboard-pager'
        ]
      ]
    );

    return $build;
  }

  /**
   * Constructs a bar chart render array.
   *
   * @param string $id
   *   The chart identifier.
   * @param string $label
   *   The chart label
   * @param array $data
   *   An associative array of chart data.
   * @param array $data['header']
   *   An array of table header columns.
   * @param array $data['rows']
   *   An array of table rows.
   * @return array
   *   A render array.
   */
  public function buildBarChart($id, $label, array $data) {
    $build = [];
    $html_id = Html::getUniqueId($id);
    $html_label_id = $html_id . '--label';
    $build['id'] = $html_id;
    $build['label_id'] = $html_label_id;
    $build['label'] = [
      '#markup' => $label,
    ];
    $build['chart'] = [
      '#type' => 'chart',
      '#chart_type' => 'column',
      'series' => [
        '#type' => 'chart_data',
        '#title' => t('Attendees'),
        '#data' => array_map(function ($row) {
          return $row['data']['attendees'] ?? 0;
        }, $data['rows']),
        '#color' => '#007E9E',
      ],
      'xaxis' => [
        '#type' => 'chart_xaxis',
        '#labels' => array_map(function ($row) {
          return $row['data']['name'];
        }, $data['rows']),
      ],
      '#raw_options' => [
        'options' => [
          'indexAxis' => 'y',
          'maintainAspectRatio' => FALSE,
          'barThickness' => 22,
          'plugins' => [
            'legend' => [
              'display' => FALSE,
            ],
          ],
          'scales' => [
            'x' => [
              'grid' => [
                'borderColor' => '#000000',
                'color' => '#000000',
                'borderDash' => [],
                'tickBorderDash' => [],
                'tickColor' => '#000000',
                'tickLength' => 18,
              ],
              'ticks' => [
                'font' => [
                  'size' => 14,
                ]
              ]
            ],
            'y' => [
              'grid' => [
                'drawOnChartArea' => FALSE,
                'offset' => FALSE,
                'drawTicks' => TRUE,
                'tickBorderDash' => [1],
                'tickLength' => 32,
                'tickColor' => '#4C4D4F',
              ],
              'ticks' => [
                'color' => '#4C4D4F',
                'font' => [
                  'weight' => 'normal',
                  'size' => 16,
                ]
              ],
            ],
          ],
        ],
      ],
      '#attached' => [
        'library' => [
          'intercept_dashboard/intercept_dashboard_chart',
        ],
      ],
    ];
    $build['height'] = (count($data['rows']) + 1) * 40;

    // Generate the table.
    $build['table'] = array(
      '#theme' => 'table',
      '#header' => $data['header'],
      '#rows' => $data['rows'],
      '#attributes' => [
        'class' => [
          'intercept-dashboard-chart__table'
        ],
        'aria-labelledby' => $html_label_id,
      ]
    );

    return $build;
  }

  public function getAttendeesByPrimaryEventTypeData() {
    $header = [
      [
        'data' => $this->t('Primary Event Type'),
      ],
      [
        'data' => $this->t('Attendees'),
      ],
    ];

    $query = $this->database->select('taxonomy_term_field_data', 't');
    $query->condition('t.vid', 'event_type');
    $query->fields('t', [
      'tid',
      'name'
    ]);

    $eventQuery = $this->getBaseQuery();

    // Attendees
    $attendees_table = $eventQuery->join($this->getAttendeesTotalSubQuery(), 'attendees', 'n.nid = attendees.nid');
    $eventQuery->fields($attendees_table, [
      'attendees'
    ]);
    $eventQuery->addExpression('SUM(attendees)', 'total_attendees');

    // Term data
    $relationship_table = $eventQuery->join('node__field_event_type_primary', 'event_types', 'n.nid = event_types.entity_id');
    $eventQuery->addField($relationship_table, 'field_event_type_primary_target_id', 'tid');
    $event_type_table = $eventQuery->join($query, 'term', 'term.tid = ' . $relationship_table . '.field_event_type_primary_target_id');
    $eventQuery->groupBy('tid');

    $eventQuery->fields($event_type_table, [
      'tid',
      'name',
    ]);

    /** @var TableSortExtender $table_sort */
    $table_sort = $eventQuery->extend('Drupal\Core\Database\Query\TableSortExtender');
    $table_sort->orderBy('total_attendees', 'DESC');

    $result = $eventQuery->execute();

    // Populate the rows.
    $rows = [];
    foreach($result as $row) {
      $rows[] = [
        'data' => [
          // Keep for debugging purposes.
          // 'tid' => $row->tid,
          'name' => $row->name,
          'attendees' => $row->total_attendees ?? 0,
        ]
      ];
    }

    return [
      'header' => $header,
      'rows' => $rows,
    ];
  }

  /**
   * Construct a query to count users checked-in to events.
   * This is a sum of all field_attendees_count values on event_attendance entities
   * that reference the given events.
   *
   * @return SelectInterface
   */
  public function getCheckedInSubQuery() {
    $data = [
      'entity_table' => 'event_attendance',
      'join_table'   => 'event_attendance__field_event',
      'field_table'  => 'event_attendance__field_attendees',
      'field_column' => 'field_attendees_count',
    ];

    // Construct the CheckedIn subQuery.
    $query = $this->database->select($data['entity_table'], 'related_entity_table');
    $query->join($data['join_table'], 'related_join_table', 'related_entity_table.id = related_join_table.entity_id AND related_join_table.deleted = :deleted', [':deleted' => 0]);
    $query->join($data['field_table'], 'related_field_table', 'related_entity_table.id = related_field_table.entity_id');
    $query->addField('related_join_table', 'field_event_target_id', 'nid');
    $query->addExpression('SUM(field_attendees_count)', 'checked_in');
    $query->groupBy('nid');

    return $query;
  }

  /**
   * Construct a query of saved events counts.
   *
   * @return SelectInterface
   */
  public function getSavedEventsSubQuery() {
    // Construct the Customer Evaluation subQuery.
    $query = $this->database->select('flag_counts', 'saves');
    $query->condition('flag_id', 'saved_event');
    $query->addField('saves', 'entity_id', 'nid');
    $query->addField('saves', 'count', 'saves');

    return $query;
  }

  /**
   * Construct a query of attendees for an events.
   *
   * @return SelectInterface
   */
  public function getAttendeesTotalSubQuery() {
    // Construct the Customer Evaluation subQuery.
    $query = $this->database->select('node__field_attendees', 'attendees');
    $query->addExpression('SUM(field_attendees_count)', 'attendees');
    $query->addField('attendees', 'entity_id', 'nid');
    $query->groupBy('nid');

    return $query;
  }

  /**
   * Construct a query to count customer evaluations for an events.
   *
   * @return SelectInterface
   */
  public function getCustomerEvaluationsSubQuery() {
    // Construct the Customer Evaluation subQuery.
    $query = $this->database->select('votingapi_vote', 'v');
    $query->condition('type', 'evaluation');
    $query->addField('v', 'entity_id', 'nid');
    $query->addExpression('COUNT(value)', 'customer_evaluations');
    $query->addExpression('SUM(value=1)', 'positive_customer_evaluations');
    $query->addExpression('SUM(value=0)', 'negative_customer_evaluations');
    $query->addExpression('SUM(value=1) / COUNT(value) * 100', 'percent_positive_customer_evaluations');
    $query->addExpression('SUM(value=0) / COUNT(value) * 100', 'percent_negative_customer_evaluations');
    $query->groupBy('nid');

    return $query;
  }

  /**
   * Construct a query to count staff evaluations for an events.
   *
   * @return SelectInterface
   */
  public function getStaffEvaluationsSubQuery() {
    // Construct the Customer Evaluation subQuery.
    $query = $this->database->select('votingapi_vote', 'v');
    $query->condition('type', 'evaluation_staff');
    $query->isNotNull('feedback__value');
    $query->addField('v', 'entity_id', 'nid');
    $query->addExpression('COUNT(feedback__value)', 'staff_evaluations');
    $query->groupBy('nid');

    return $query;
  }

  /**
   * Construct a query to count event registrations.
   * This is a sum of all field_registrants_count values on event_registration entities
   * that reference the given events.
   *
   * @return SelectInterface
   */
  public function getRegistrantsSubQuery() {
    $data = [
      'entity_table' => 'event_registration',
      'join_table'   => 'event_registration__field_event',
      'field_table'  => 'event_registration__field_registrants',
      'field_column' => 'field_registrants_count',
    ];
    $count_field = 'event_count_value';

    // Construct the CheckedIn subQuery.
    $query = $this->database->select($data['entity_table'], 'related_entity_table');
    $query->join($data['join_table'], 'related_join_table', 'related_entity_table.id = related_join_table.entity_id AND related_join_table.deleted = :deleted', [':deleted' => 0]);
    $query->join($data['field_table'], 'related_field_table', 'related_entity_table.id = related_field_table.entity_id');
    $query->addField('related_join_table', 'field_event_target_id', 'nid');
    $query->addExpression('SUM(field_registrants_count)', 'registrants');
    $query->groupBy('nid');

    return $query;
  }


  /**
   * Executes a queries the Total Customer Evaluations
   *
   * @return array[
   *   'total_customer_evaluations' => string
   *   'total_positive_customer_evaluations' => string
   *   'total_negative_customer_evaluations' => string
   *   'percent_positive_customer_evaluations' => string
   *   'percent_negative_customer_evaluations' => string
   * ].
   */
  public function queryTotalCustomerEvaluations() {
    // Join the attendee counts to the eventQuery.
    $query = $this->getBaseQuery();
    $query->join($this->getCustomerEvaluationsSubQuery(), 'customer_evaluations', 'n.nid = customer_evaluations.nid');
    $query->addExpression('SUM(customer_evaluations)', 'total_customer_evaluations');
    $query->addExpression('SUM(positive_customer_evaluations)', 'total_positive_customer_evaluations');
    $query->addExpression('SUM(negative_customer_evaluations)', 'total_negative_customer_evaluations');
    $query->addExpression('SUM(positive_customer_evaluations) / SUM(customer_evaluations) * 100', 'percent_positive_customer_evaluations');
    $query->addExpression('SUM(negative_customer_evaluations) / SUM(customer_evaluations) * 100', 'percent_negative_customer_evaluations');

    $results = $query
      ->execute()
      ->fetchAssoc();

    return $results;
  }

}
