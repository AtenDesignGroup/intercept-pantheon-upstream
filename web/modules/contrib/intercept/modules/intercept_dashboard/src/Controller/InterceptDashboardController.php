<?php

namespace Drupal\intercept_dashboard\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Intercept Dashboard routes.
 */
class InterceptDashboardController extends ControllerBase {
  /**
   * EntityQuery service.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
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
   * @var \Drupal\intercept_dashboard\FilterProviderInterface
   */
  protected $filterProvider;

  /**
   * Entity type manager service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $currentRequest;

  /**
   * The CSV encoder.
   *
   * @var \Drupal\csv_serialization\Encoder\CsvEncoder
   */
  protected $encoder;

  /**
   * The Intercept dates utility.
   *
   * @var \Drupal\intercept_core\Utility\Dates
   */
  protected $dateUtility;

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
    $instance->encoder = new CsvEncoder();
    $instance->dateUtility = $container->get('intercept_core.utility.dates');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function dateUtility() {
    return $this->dateUtility;
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
    // $build['#summary']['negative_customer_evaluations'] = $this->buildTotalNegativeCustomerEvaluations($totalCustomerEvaluations['total_negative_customer_evaluations']);.
    $build['#summary']['total_staff_evaluations'] = $this->buildTotalStaffEvaluations();

    /**
     * Data Table
     */
    $build['#event_table'] = $this->buildEventTable();

    $build['#charts'] = [];

    /**
     * Attendees by Primary Audience chart
     */
    $build['#charts']['by_primary_audience'] = $this->buildBarChart(
      'attendeesByPrimaryAudience',
      $this->t('Attendance by Primary Audience'),
      $this->getAttendeesByPrimaryAudienceData(),
    );

    /**
     * Attendees by Event Type chart
     */
    $build['#charts']['by_primary_event_type'] = $this->buildBarChart(
      'attendeesByPrimaryEventType',
      $this->t('Attendance by Primary Event Type'),
      $this->getAttendeesByPrimaryEventTypeData(),
      '#51832F'
    );

    /**
     * Attendees by Time
     */
    $date = new \DateTime('Saturday');
    $day = '';
    for ($days = 7; $days--;) {
      // $day == Sunday, Monday, etc.
      $day = $date->modify('+1 days')->format('l');

      $build['#charts']['by_time_' . strtolower($day)] = $this->buildLineChart(
        'attendeesByTime' . $day,
        $this->t('Attendance by Time'),
        $this->getAttendeesByTime(),
        $day
      );
    }

    return $build;
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
        ]),
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
   *
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
    }
    else {
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
      ],
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
      ],
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
      ],
    ];
    // Build the "View All" link to view all customer feedback.
    $eventQuery = $this->filterProvider->getBaseQuery();
    $eventQuery->addJoin('right', $this->getCustomerEvaluationsSubQuery(), 'customer_evaluations', 'n.nid = customer_evaluations.entity_id');
    $eventQuery->fields('customer_evaluations', ['entity_id']);
    $all_customer_evaluations = $eventQuery->execute()->fetchAll();
    $nids = [];
    foreach ($all_customer_evaluations as $evaluation) {
      $nids[] = $evaluation->entity_id;
    }
    if (count($nids) > 0) {
      $build['#link'] = new FormattableMarkup('
        <a href="@url" class="use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:1000,&quot;height&quot;:600}">View All</a>',
        [
          '@url' => Url::fromRoute('intercept_event.customer_evaluations')->setOption('query', ['nids' => implode(',', $nids)])->toString(),
        ]
      );
    }
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
      ],
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
      ],
    ];
    return $build;
  }

  /**
   * Builds the total attendees for all events.
   *
   * @return array Render array of the total attendees metric.
   */
  public function buildTotalAttendees() {
    $eventQuery = $this->filterProvider->getBaseQuery();
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
      ],
    ];
    return $build;
  }

  /**
   * Render total staff evaluations.
   *
   * @return array Render array.
   */
  public function buildTotalStaffEvaluations() {
    $eventQuery = $this->filterProvider->getBaseQuery();
    $eventQuery->join($this->getStaffEvaluationsSubQuery(), 'staff_evaluations', 'n.nid = staff_evaluations.nid');
    $eventQuery->addExpression('SUM(staff_evaluations)', 'total_staff_evaluations');
    $count = $eventQuery->execute()->fetchAssoc()['total_staff_evaluations'];

    $build = [
      '#theme' => 'intercept_dashboard_metric',
      '#label' => $this->t('Events with Staff Feedback'),
      '#value' => number_format($count ?? 0),
      '#cache' => [
        'context' => 'url',
      ],
    ];

    // Build the "View All" link to view all staff feedback.
    $eventQuery = $this->filterProvider->getBaseQuery();
    $eventQuery->addJoin('right', $this->getStaffEvaluationsSubQuery(), 'staff_evaluations', 'n.nid = staff_evaluations.nid'); // rightJoin is removed in D9.
    $eventQuery->fields('staff_evaluations', ['nid']);
    $all_staff_evaluations = $eventQuery->execute()->fetchAll();
    $nids = [];
    foreach ($all_staff_evaluations as $evaluation) {
      $nids[] = $evaluation->nid;
    }
    if (count($nids) > 0) {
      $build['#link'] = new FormattableMarkup('
        <a href="@url" class="use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:1000,&quot;height&quot;:600}">View All</a>',
        [
          '@url' => Url::fromRoute('intercept_event.staff_evaluations')->setOption('query', ['nids' => implode(',', $nids)])->toString(),
        ]
      );
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
      $query = $this->filterProvider->getBaseQuery();
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
      ],
    ];
    return $build;
  }

  /**
   * Queries the Total Attendees Checked-in.
   *
   * @return array Render array
   */
  public function buildTotalAttendeesCheckedIn() {
    $eventQuery = $this->filterProvider->getBaseQuery();
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
      ],
    ];

    return $build;
  }

  /**
   * Queries the total Registrations count and returns a render array of the data.
   *
   * @return array Render array
   */
  public function buildTotalRegistrants() {
    $eventQuery = $this->filterProvider->getBaseQuery();
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
      ],
    ];

    return $build;
  }

  /**
   * Builds the total saves for all events.
   *
   * @return array Render array.
   */
  public function buildTotalSaves() {
    $eventQuery = $this->filterProvider->getBaseQuery();
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
      ],
    ];
    return $build;
  }

  /**
   * Generates a sortable paginated table view of filtered events.
   *
   * @param int|false $limit
   *   An integer specifying the number of elements per page. If passed a false
   *   value, the pager is disabled.
   *
   * @return array Render array.
   */
  public function buildEventTable($limit = 20) {

    $header = [
      // Keep for debugging purposes.
      // [
      //   'data' => $this->t('NID'),
      //   'field' => 'nid',
      //   'initial_click_sort' => 'asc',
      // ],.
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
        'data' => $this->t('Net Promoter Score'),
        'field' => 'net_promoter_score',
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

    $eventQuery = $this->filterProvider->getBaseQuery();
    $eventQuery->fields('n', [
      'nid',
      'title',
    ]);
    $eventQuery->fields('date', [
      'field_date_time_value'
    ]);

    // Attendees.
    $eventQuery->leftJoin($this->getAttendeesTotalSubQuery(), 'attendees', 'n.nid = attendees.nid');
    $eventQuery->fields('attendees', [
      'attendees',
    ]);

    // Checked-in.
    $eventQuery->leftJoin($this->getCheckedInSubQuery(), 'checked_in', 'n.nid = checked_in.nid');
    $eventQuery->fields('checked_in', [
      'checked_in',
    ]);

    // Saves.
    $eventQuery->leftJoin($this->getSavedEventsSubQuery(), 'saves', 'n.nid = saves.nid');
    $eventQuery->fields('saves', [
      'saves',
    ]);

    // Net Promoter Score
    $eventQuery->leftJoin($this->getNetPromoterScoreSubQuery(), 'net_promoter_score', 'n.nid = net_promoter_score.nid');
    $eventQuery->fields('net_promoter_score', [
      'net_promoter_score',
    ]);

    // Registrants.
    $eventQuery->leftJoin($this->getRegistrantsSubQuery(), 'registrants', 'n.nid = registrants.nid');
    $eventQuery->fields('registrants', [
      'registrants',
    ]);

    // Customer Evaluations.
    $eventQuery->leftJoin($this->getCustomerEvaluationsSubQuery(), 'customer_evaluations', 'n.nid = customer_evaluations.entity_id');
    $eventQuery->fields('customer_evaluations', [
      'percent_positive_customer_evaluations',
      'percent_negative_customer_evaluations',
      'customer_evaluations',
    ]);

    // Staff Evaluations.
    $eventQuery->leftJoin($this->getStaffEvaluationsSubQuery(), 'staff_evaluations', 'n.nid = staff_evaluations.nid');
    $eventQuery->fields('staff_evaluations', [
      'staff_evaluations',
    ]);

    /** @var \Drupal\Core\Database\Query\TableSortExtender $table_sort */
    $table_sort = $eventQuery->extend('Drupal\Core\Database\Query\TableSortExtender');
    $table_sort->orderByHeader($header);

    /** @var \Drupal\Core\Database\Query\PagerSelectExtender $pager */
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $pager->limit($limit);

    $result = $pager->execute();

    // Populate the rows.
    $rows = [];
    foreach ($result as $row) {
      $date = new DrupalDateTime($row->field_date_time_value);
      $rows[] = [
        'data' => [
          // Keep for debugging purposes.
          // 'nid' => $row->nid,.
          'title' => new FormattableMarkup('
              <a href="@url">@title</a>',
              [
                '@title' => $row->title,
                '@url' => Url::fromRoute('entity.node.canonical', ['node' => $row->nid])->toString(),
              ]
          ),
          'date' => $date->format('n/j/y'),
          'attendees' => number_format($row->attendees ?? 0),
          'checked_in' => number_format($row->checked_in ?? 0),
          'registrants' => number_format($row->registrants ?? 0),
          'saves' => number_format($row->saves ?? 0),
          'customer_rating' => $row->customer_evaluations ?
          new FormattableMarkup('<div class="feedback__wrapper">
                <span class="feedback feedback--positive"><span class="feedback__icon"></span> <span class="visually-hidden">Positive</span>@positive<span class="feedback__suffix">%</span></span>
                <span class="feedback feedback--negative"><span class="feedback__icon"></span> <span class="visually-hidden">Negative</span> @negative<span class="feedback__suffix">%</span></span>
            </div>', [
              '@positive' => number_format($row->percent_positive_customer_evaluations, 0),
              '@negative' => number_format($row->percent_negative_customer_evaluations, 0),
            ]) :
          new FormattableMarkup('<span>—</span>', []),
          'net_promoter_score' => number_format($row->net_promoter_score ?? 0),
          'customer_evaluations' => $row->customer_evaluations ?
          new FormattableMarkup('
              <a href="@url" class="use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:1000}">View <span class="visually-hidden">Customer Feedback</span></a>',
              [
                '@url' => Url::fromRoute('entity.node.customer_evaluations', ['node' => $row->nid])->toString(),
              ]
          ) :
          '—',
          'staff_evaluations' => $row->staff_evaluations ?
          new FormattableMarkup('
              <a href="@url" class="use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:1000}">View <span class="visually-hidden">Staff Comments</span></a>',
              [
                '@url' => Url::fromRoute('entity.node.staff_evaluations', ['node' => $row->nid])->toString(),
              ]
          ) :
          '—',
        ],
      ];
    }

    // Generate the table.
    $build['event_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'class' => [
          'intercept-dashboard-table__table',
        ],
      ],
    ];

    // Add the pager.
    $build['pager'] = [
      '#type' => 'pager',
      '#quantity' => 5,
      '#attributes' => [
        'class' => [
          'intercept-dashboard-pager',
        ],
      ],
    ];

    // Add the link to download the CSV.
    $request = \Drupal::request();
    $link_renderable = Link::createFromRoute('Download CSV', 'intercept_dashboard.event_data_dashboard.export', ['_format' => 'csv'] + $request->query->all())->toRenderable();
    $link_renderable['#attributes'] = ['class' => ['button', 'intercept-dashboard-chart__toggle']];
    $build['csv_link'] = \Drupal::service('renderer')->renderPlain($link_renderable);

    return $build;
  }

  /**
   * Constructs a bar chart render array.
   *
   * @param string $id
   *   The chart identifier.
   * @param string $label
   *   The chart label.
   * @param array $data
   *   An associative array of chart data.
   * @param array $data['header']
   *   An array of table header columns.
   * @param array $data['rows']
   *   An array of table rows.
   *
   * @return array
   *   A render array.
   */
  public function buildBarChart($id, $label, array $data, $color = '#007E9E') {
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
        '#color' => $color,
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
                ],
              ],
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
                ],
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
    $build['table'] = [
      '#theme' => 'table',
      '#header' => $data['header'],
      '#rows' => $data['rows'],
      '#attributes' => [
        'class' => [
          'intercept-dashboard-chart__table',
        ],
        'aria-labelledby' => $html_label_id,
      ],
    ];

    return $build;
  }

  /**
   *
   */
  public function getAttendeesByPrimaryAudienceData() {
    $header = [
      [
        'data' => $this->t('Primary Audience'),
      ],
      [
        'data' => $this->t('Attendees'),
      ],
    ];

    $query = $this->database->select('taxonomy_term_field_data', 't');
    $query->condition('t.vid', 'audience');
    $query->fields('t', [
      'tid',
      'name',
    ]);

    $eventQuery = $this->filterProvider->getBaseQuery();

    // Attendees.
    $attendees_table = $eventQuery->join($this->getAttendeesTotalSubQuery(), 'attendees', 'n.nid = attendees.nid');
    $eventQuery->fields($attendees_table, [
      'attendees',
    ]);
    $eventQuery->addExpression('SUM(attendees)', 'total_attendees');

    // Term data.
    $relationship_table = $eventQuery->join('node__field_audience_primary', 'audiences', 'n.nid = audiences.entity_id');
    $eventQuery->addField($relationship_table, 'field_audience_primary_target_id', 'tid');
    $audience_table = $eventQuery->join($query, 'term', 'term.tid = ' . $relationship_table . '.field_audience_primary_target_id');
    $eventQuery->groupBy('tid');

    $eventQuery->fields($audience_table, [
      'tid',
      'name',
    ]);

    /** @var \Drupal\Core\Database\Query\TableSortExtender $table_sort */
    $table_sort = $eventQuery->extend('Drupal\Core\Database\Query\TableSortExtender');
    $table_sort->orderBy('total_attendees', 'DESC');

    $result = $eventQuery->execute();

    // Populate the rows.
    $rows = [];
    foreach ($result as $row) {
      $rows[] = [
        'data' => [
          // Keep for debugging purposes.
          // 'tid' => $row->tid,.
          'name' => $row->name,
          'attendees' => $row->total_attendees ?? 0,
        ],
      ];
    }

    return [
      'header' => $header,
      'rows' => $rows,
    ];
  }

  /**
   *
   */
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
      'name',
    ]);

    $eventQuery = $this->filterProvider->getBaseQuery();

    // Attendees.
    $attendees_table = $eventQuery->join($this->getAttendeesTotalSubQuery(), 'attendees', 'n.nid = attendees.nid');
    $eventQuery->fields($attendees_table, [
      'attendees',
    ]);
    $eventQuery->addExpression('SUM(attendees)', 'total_attendees');

    // Term data.
    $relationship_table = $eventQuery->join('node__field_event_type_primary', 'event_types', 'n.nid = event_types.entity_id');
    $eventQuery->addField($relationship_table, 'field_event_type_primary_target_id', 'tid');
    $event_type_table = $eventQuery->join($query, 'term', 'term.tid = ' . $relationship_table . '.field_event_type_primary_target_id');
    $eventQuery->groupBy('tid');

    $eventQuery->fields($event_type_table, [
      'tid',
      'name',
    ]);

    /** @var \Drupal\Core\Database\Query\TableSortExtender $table_sort */
    $table_sort = $eventQuery->extend('Drupal\Core\Database\Query\TableSortExtender');
    $table_sort->orderBy('total_attendees', 'DESC');

    $result = $eventQuery->execute();

    // Populate the rows.
    $rows = [];
    foreach ($result as $row) {
      $rows[] = [
        'data' => [
          // Keep for debugging purposes.
          // 'tid' => $row->tid,.
          'name' => $row->name,
          'attendees' => $row->total_attendees ?? 0,
        ],
      ];
    }

    return [
      'header' => $header,
      'rows' => $rows,
    ];
  }

  /**
   * Constructs a bar chart render array.
   *
   * @param string $id
   *   The chart identifier.
   * @param string $label
   *   The chart label.
   * @param array $data
   *   An associative array of chart data.
   * @param array $data['header']
   *   An array of table header columns.
   * @param array $data['rows']
   *   An array of table rows.
   * @param string $day
   *   The day of the week for this chart.
   *
   * @return array
   *   A render array.
   */
  public function buildLineChart($id, $label, array $data, $day) {
    $build = [];
    $html_id = Html::getUniqueId($id);
    $html_label_id = $html_id . '--label';
    $build['id'] = $html_id;
    $build['label_id'] = $html_label_id;
    if ($day == 'Sunday') {
      $build['label'] = [
        '#markup' => $label,
      ];
    }
    // Get the first 3 characters of the day to match up with data rows.
    $day_short = substr($day, 0, 3);

    $build['chart'] = [
      '#type' => 'chart',
      '#chart_type' => 'line',
      'series' => [
        '#type' => 'chart_data',
        '#title' => $day,
        '#data' => [
    // 9th hour of the day
          $data['rows'][$day_short . '9'] ?? 0,
    // 10th hour of the day
          $data['rows'][$day_short . '10'] ?? 0,
          $data['rows'][$day_short . '11'] ?? 0,
          $data['rows'][$day_short . '12'] ?? 0,
          $data['rows'][$day_short . '13'] ?? 0,
          $data['rows'][$day_short . '14'] ?? 0,
          $data['rows'][$day_short . '15'] ?? 0,
          $data['rows'][$day_short . '16'] ?? 0,
          $data['rows'][$day_short . '17'] ?? 0,
          $data['rows'][$day_short . '18'] ?? 0,
          $data['rows'][$day_short . '19'] ?? 0,
          $data['rows'][$day_short . '20'] ?? 0,
        ],
        '#color' => '#007E9E',
      ],
      'xaxis' => [
        '#type' => 'chart_xaxis',
        '#labels' => [
          '9:00',
          '10:00',
          '11:00',
          '12:00',
          '1:00',
          '2:00',
          '3:00',
          '4:00',
          '5:00',
          '6:00',
          '7:00',
          '8:00',
        ],
        // '#title' => $this->t('This is the x axis title.'),
      ],
      'yaxis' => [
        '#type' => 'chart_yaxis',
        // '#title' => $this->t('This is the y axis title.'),
      ],
      '#raw_options' => [
      // See https://www.chartjs.org/docs/latest/charts/line.html
        'options' => [
          'indexAxis' => 'x',
          'maintainAspectRatio' => FALSE,
          // 'showLine' => FALSE,
          // 'layout' => [
          //   'padding' => [
          //     'bottom' => 5
          //   ]
          // ],
          'plugins' => [
            'legend' => [
              'display' => TRUE,
              'position' => 'left',
              'labels' => [
                'boxWidth' => 0,
                'boxHeight' => 0,
              ],
              'font' => [
                'weight' => 'normal',
                'size' => 16,
              ],
            ],
          ],
          'scales' => [
            'x' => [
              'grid' => [
                'display' => FALSE,
              ],
              'ticks' => [
                'font' => [
                  'size' => 14,
                ],
              ],
            ],
            'y' => [
              'grid' => [
                'display' => FALSE,
              ],
              'ticks' => [
                'color' => '#4C4D4F',
                'font' => [
                  'weight' => 'normal',
                  'size' => 16,
                ],
              ],
              'min' => 0,
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

    return $build;
  }

  /**
   *
   */
  public function getAttendeesByTime() {
    $header = [
      [
        'data' => $this->t('Time'),
      ],
      [
        'data' => $this->t('Attendees'),
      ],
    ];

    $eventQuery = $this->filterProvider->getBaseQuery();
    $eventQuery->fields('n', [
      'nid',
      'title',
    ]);
    $eventQuery->fields('date', [
      'field_date_time_value',
      'field_date_time_end_value',
    ]);

    // Attendees.
    $eventQuery->join('node__field_attendees', 'attendees', 'n.nid = attendees.entity_id');
    $eventQuery->addExpression('SUM(field_attendees_count)', 'attendees_count');
    $eventQuery->groupBy('attendees.entity_id');

    // Execute the query.
    $result = $eventQuery->execute();

    // Populate the rows.
    $rows = [];
    $grouping_totals = [];
    foreach ($result as $row) {
      $start_date = $this->dateUtility->getDrupalDate($row->field_date_time_value);
      $end_date = $this->dateUtility->getDrupalDate($row->field_date_time_end_value);
      $start_date = $this->dateUtility->convertTimezone($start_date, 'default')->format('Y-m-d\TH:i:s');
      $end_date = $this->dateUtility->convertTimezone($end_date, 'default')->format('Y-m-d\TH:i:s');
      $start_date_ts = strtotime($start_date);
      $end_date_ts = strtotime($end_date);
      $dayofweek = date('D', $start_date_ts);
      $start_hour = date('G', $start_date_ts);
      $end_hour = date('G', $end_date_ts);
      $duration_seconds = $end_date_ts - $start_date_ts;
      $duration_hours = $duration_seconds / 60 / 60;
      $grouping = $dayofweek . $start_hour;
      $attendees_count = (int) $row->attendees_count;
      $rows[] = [
        'data' => [
          'nid' => $row->nid,
          'title' => $row->title,
          'start_start' => $start_date,
          'end_date' => $end_date,
          'attendees' => $attendees_count,
          'dayofweek' => $dayofweek,
          'start_hour' => $start_hour,
          'end_hour' => $end_hour,
          'duration_seconds' => $duration_seconds,
          'duration' => $duration_hours,
          'grouping' => $grouping,
        ],
      ];

      // Let's get a sum of attendees for the events broken up by hour of the day
      // and by day of the week.
      // During for loop classify each start date as Monday Tuesday etc.
      // Total those together.
      if (isset($grouping_totals[$grouping])) {
        $grouping_totals[$grouping] = $grouping_totals[$grouping] + $attendees_count;
      }
      else {
        $grouping_totals[$grouping] = $attendees_count;
      }

      // Get the "previous whole number".
      // This is equal to the number of additional hourly groupings.
      // Is it a whole number?
      if (floor($duration_hours) == $duration_hours) {
        // Subtract 1.
        $previous_whole_number = $duration_hours - 1;
      }
      // It is a fraction.
      else {
        // Round down.
        $previous_whole_number = floor($duration_hours);
      }

      // Handle events that last for longer than 1 hour.
      // @todo Handle multi-day events.
      if ($previous_whole_number >= 1 && $previous_whole_number <= 24) {
        for ($n = 1; $n <= $previous_whole_number; $n++) {
          if (isset($grouping_totals[$dayofweek . ($start_hour + $n)])) {
            $grouping_totals[$dayofweek . ($start_hour + $n)] = $grouping_totals[$dayofweek . ($start_hour + $n)] + $attendees_count;
          }
          else {
            $grouping_totals[$dayofweek . ($start_hour + $n)] = $attendees_count;
          }
        }
      }

    }
    ksort($grouping_totals);

    return [
      'header' => $header,
      'rows' => $grouping_totals,
    ];
  }

  /**
   * Construct a query to count users checked-in to events.
   * This is a sum of all field_attendees_count values on event_attendance entities
   * that reference the given events.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
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
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  public function getSavedEventsSubQuery() {
    $query = $this->database->select('flag_counts', 'saves');
    $query->condition('flag_id', 'saved_event');
    $query->addField('saves', 'entity_id', 'nid');
    $query->addField('saves', 'count', 'saves');

    return $query;
  }

  /**
   * Construct a query to get an average net promoter score.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  public function getNetPromoterScoreSubQuery() {
    $data = [
      'entity_table' => 'webform_submission_data',
      'join_table'   => 'webform_submission',
    ];

    $query = $this->database->select($data['entity_table'], 'related_entity_table');
    $query->condition('name', 'how_likely_are_you_to_recommend_this_event_to_a_friend');
    $query->join($data['join_table'], 'related_join_table', 'related_entity_table.sid = related_join_table.sid AND related_join_table.webform_id = :wid', [':wid' => 'intercept_event_feedback']);
    $query->addField('related_join_table', 'entity_id', 'nid');
    $query->addExpression('AVG(value)', 'net_promoter_score');
    $query->groupBy('nid');

    return $query;
  }

  /**
   * Construct a query of attendees for an event.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  public function getAttendeesTotalSubQuery() {
    $query = $this->database->select('node__field_attendees', 'attendees');
    $query->addExpression('SUM(field_attendees_count)', 'attendees');
    $query->addField('attendees', 'entity_id', 'nid');
    $query->groupBy('nid');

    return $query;
  }

  /**
   * Construct a query to count customer evaluations for events.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  public function getCustomerEvaluationsSubQuery() {
    // Construct the Customer Evaluation subQuery.
    $query = $this->database->select('webform_submission', 'ws');
    $query->addField('ws', 'entity_id');
    $query->innerJoin('webform_submission_data', 'wsd', 'ws.sid = wsd.sid');
    $query->condition('ws.webform_id', 'intercept_event_feedback');
    $query->condition('name', 'how_did_the_event_go');
    $query->addExpression('COUNT(value)', 'customer_evaluations');
    $query->addExpression('SUM(value = \'Like\')', 'positive_customer_evaluations');
    $query->addExpression('SUM(value = \'Dislike\')', 'negative_customer_evaluations');
    $query->addExpression('SUM(value = \'Like\') / COUNT(value) * 100', 'percent_positive_customer_evaluations');
    $query->addExpression('SUM(value = \'Dislike\') / COUNT(value) * 100', 'percent_negative_customer_evaluations');
    $query->groupBy('entity_id');

    return $query;
  }

  /**
   * Construct a query to count staff evaluations for an events.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  public function getStaffEvaluationsSubQuery() {
    // @todo: Use Webform.
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
   * @return \Drupal\Core\Database\Query\SelectInterface
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
   * Executes a queries the Total Customer Evaluations.
   *
   * @return array[
   *   'total_customer_evaluations' => string
   *   'total_positive_customer_evaluations' => string
   *   'total_negative_customer_evaluations' => string
   *   'percent_positive_customer_evaluations' => string
   *   'percent_negative_customer_evaluations' => string
   *   ].
   */
  public function queryTotalCustomerEvaluations() {
    $query = $this->filterProvider->getBaseQuery();
    $query->join($this->getCustomerEvaluationsSubQuery(), 'customer_evaluations', 'n.nid = customer_evaluations.entity_id');
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

  /**
   * Sends CSV data being displayed at the current path to the browser to download.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Response in CSV format.
   */
  public function buildCsv() {
    // We can't show all rows by default so we must pass a number.
    $limit = 10000;
    $table = $this->buildEventTable($limit);
    $headers = $table['event_table']['#header'];
    $rows = $table['event_table']['#rows'];
    // Get a simple string for each header item.
    $headers_simplified = [];
    foreach ($headers as $header) {
      $headers_simplified[] = $header['data']->__tostring();
    }
    // Change the table rows into CSV format.
    $csv_data = [];
    foreach ($rows as $key => $row) {
      $customer_rating = $row['data']['customer_rating']->__tostring();
      if (!empty($customer_rating)) {
        $customer_rating = str_replace('—', '', strip_tags($customer_rating));
        $customer_rating = str_replace('Positive', '', $customer_rating);
        if (is_numeric($customer_rating)) {
          // Change to a value usable in CSV as percentage.
          $customer_rating = $customer_rating / 100;
        }
      }
      $csv_data[$key] = [
        $headers_simplified[0] => $row['data']['title']->__tostring(),
        $headers_simplified[1] => $row['data']['date'],
        $headers_simplified[2] => $row['data']['attendees'],
        $headers_simplified[3] => $row['data']['checked_in'],
        $headers_simplified[4] => $row['data']['registrants'],
        $headers_simplified[5] => $row['data']['saves'],
        $headers_simplified[6] => $customer_rating,
        $headers_simplified[7] => $row['data']['net_promoter_score'],
        // $headers_simplified[8] => str_replace('—', '', $row['data']['customer_evaluations']),
        // $headers_simplified[9] => str_replace('—', '', $row['data']['staff_evaluations']),
      ];
    }
    $result = $this->encoder->encode($csv_data, 'csv');
    return new Response($result);
  }

}
