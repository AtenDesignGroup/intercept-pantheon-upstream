<?php

namespace Drupal\intercept_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Intercept Dashboard event filter form.
 */
class DashboardEventFilters extends FormBase {

  /**
   * EntityQuery service.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;

  /**
   * FilterProvider service.
   *
   * @var \Drupal\intercept_dashboard\FilterProviderInterface
   */
  protected $filterProvider;

  /**
   * Empty options array.
   * Used to provide a default empty option for select elements.
   *
   * @var array
   */
  protected $emptyOptions = [
    '- no options -' => [],
  ];

  /**
   * Normalizes the options array.
   */
  protected function normalizeOptions($possibleOptions, $availableOptions, $selectedOptions = []) {
    // Merge possible options with currently selected options.
    // This is to ensure that the form element is populated with
    // the currently selected options and they are enabled.
    $entities = array_merge($availableOptions, $selectedOptions ?? []);

    // Get id => label pairs for the currently enabled options.
    foreach ($entities as $id) {
      if (isset($possibleOptions[$id])) {
        $enabledOptions[$id] = $possibleOptions[$id];
      }
    }

    if (!empty($enabledOptions)) {
      // Alphabetize the options.
      asort($enabledOptions);
    } else {
      // If no options are enabled, use the empty options array.
      $enabledOptions = $this->emptyOptions;
    }

    return $enabledOptions;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->entityQuery = $container->get('entity.query.sql');
    $instance->filterProvider = $container->get('intercept_dashboard.filterProvider');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'intercept_dashboard_dashboard_event_filters';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $params = \Drupal::request()->query->all();

    $form['date'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['filters__inputs-inner'],
      ],
    ];

    $form['date']['start'] = [
      '#type' => 'date',
      '#title' => $this
        ->t('Start Date'),
      // First day of the current month.
      '#default_value' => $params['start'] ?? date('Y-m-01'),
      '#required' => TRUE,
    ];

    $form['date']['end'] = [
      '#type' => 'date',
      '#title' => $this
        ->t('End Date'),
      // Today.
      '#default_value' => $params['end'] ?? date('Y-m-d'),
      '#required' => TRUE,
    ];

    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['filters__inputs-inner'],
      ],
    ];

    $form['filters']['keyword'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('Keyword'),
      '#default_value' => $params['keyword'] ?? NULL,
    ];

    // Primary Event Type filter.
    $form['filters']['type'] = $this->getEventTypeFilter('type', $params);

    // Audience filter.
    $form['filters']['audience'] = $this->getAudienceFilter('audience', $params);

    // Location filter.
    $form['filters']['location'] = $this->getLocationFilter('location', $params);

    // Event Series filter.
    $form['filters']['event_series'] = $this->getEventSeriesFilter('event_series', $params);

    // Tags filter.
    $form['filters']['tags'] = $this->getTagFilter('tags', $params);

    // Created by filter.
    $form['filters']['created_by'] = [
      '#title' => $this->t('Created by'),
      '#type' => 'entity_autocomplete',
      '#tags' => TRUE,
      '#target_type' => 'user',
      '#default_value' => !empty($params['created_by'])
        ? User::loadMultiple(array_values($params['created_by'][0]))
        : '',
    ];

    $form['filters']['external_presenter'] = [
      '#type' => 'select',
      '#title' => $this->t('External Presenter'),
      '#default_value' => $params['external_presenter'] ?? NULL,
      '#empty_option' => $this->t('- Any -'),
      '#options' => [
        'yes' => 'Yes',
        'no' => 'No',
      ],
    ];

    /**
     * Form Actions
     */
    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['filters__actions'],
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => t('Clear'),
      '#submit' => ['::resetForm'],
    ];

    return $form;
  }

  /**
   * Gets the Audience filter form element.
   *
   * @param string $name
   *   The name of the form element.
   * @param array $params
   *   The url parameters used to populate the default_value.
   *
   * @return array Render Array
   */
  public function getAudienceFilter($name, $params) {
    $options = $this->filterProvider->getRelatedTermOptions('audience');
    $enabledOptions = [];

    // Query for available options based the events result set.
    $query = $this->filterProvider->getBaseQuery(['audience']);
    $query->leftJoin('node__field_audience_primary', 'audience', 'n.nid = audience.entity_id');
    $query->fields('audience', ['field_audience_primary_target_id']);
    $query->distinct();
    $result = $query->execute();
    $availableOptions = $result->fetchCol();

    $enabledOptions = $this->normalizeOptions($options, $availableOptions, $params[$name] ?? []);

    return [
      '#type' => 'select',
      '#title' => $this->t('Audience'),
      '#default_value' => $params[$name] ?? [],
      '#options' => $enabledOptions,
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];
  }

  /**
   * Gets the Event Type filter form element.
   *
   * @param string $name
   *   The name of the form element.
   * @param array $params
   *   The url parameters used to populate the default_value.
   *
   * @return array Render Array
   */
  public function getEventTypeFilter($name, $params) {
    // Get all possible options.
    $options = $this->filterProvider->getRelatedTermOptions('event_type');
    $enabledOptions = [];

    // Query for available options based the events result set.
    $query = $this->filterProvider->getBaseQuery(['type']);
    $query->leftJoin('node__field_event_type_primary', 'event_type', 'n.nid = event_type.entity_id');
    $query->fields('event_type', ['field_event_type_primary_target_id']);
    $query->distinct();
    $result = $query->execute();
    $availableOptions = $result->fetchCol();

    $enabledOptions = $this->normalizeOptions($options, $availableOptions, $params[$name] ?? []);

    return [
      '#type' => 'select',
      '#title' => $this->t('Event Type'),
      '#default_value' => $params[$name] ?? [],
      '#options' => $enabledOptions,
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];
  }

  /**
   * Gets the Tags filter form element.
   *
   * @param string $name
   *   The name of the form element.
   * @param array $params
   *   The url parameters used to populate the default_value.
   *
   * @return array Render Array
   */
  public function getTagFilter($name, $params) {
    // Get all possible options.
    $options = $this->filterProvider->getRelatedTermOptions('tag');
    $enabledOptions = [];

    // Query for available options based the events result set.
    $query = $this->filterProvider->getBaseQuery(['tags']);
    $query->leftJoin('node__field_event_tags', 'event_tags', 'n.nid = event_tags.entity_id');
    $query->isNotNull('event_tags.entity_id');
    $query->fields('event_tags', ['field_event_tags_target_id']);
    $query->distinct();
    $result = $query->execute();
    $availableOptions = $result->fetchCol();

    $enabledOptions = $this->normalizeOptions($options, $availableOptions, $params[$name] ?? []);

    return [
      '#type' => 'select',
      '#title' => $this->t('Tag'),
      '#default_value' => $params[$name] ?? [],
      '#options' => $enabledOptions,
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];
  }

  /**
   * Gets the Location filter form element.
   *
   * @param string $name
   *   The name of the form element.
   * @param array $params
   *   The url parameters used to populate the default_value.
   *
   * @return array Render Array
   */
  public function getLocationFilter($name, $params) {
    // Get all possible options.
    $options = $this->filterProvider->getRelatedContentOptions('location');
    $enabledOptions = [];

    // Query for available options based the events result set.
    $query = $this->filterProvider->getBaseQuery(['location']);
    $query->leftJoin('node__field_location', 'location', 'n.nid = location.entity_id');
    $query->fields('location', ['field_location_target_id']);
    $query->distinct();
    $result = $query->execute();
    $availableOptions = $result->fetchCol();

    $enabledOptions = $this->normalizeOptions($options, $availableOptions, $params[$name] ?? []);

    return [
      '#type' => 'select',
      '#title' => $this->t('Location'),
      '#default_value' => $params[$name] ?? [],
      '#options' => $enabledOptions,
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];
  }

  /**
   * Gets the Event Series filter form element.
   *
   * @param string $name
   *   The name of the form element.
   * @param array $params
   *   The url parameters used to populate the default_value.
   *
   * @return array Render Array
   */
  public function getEventSeriesFilter($name, $params) {
    // Get all possible options.
    $options = $this->filterProvider->getRelatedContentOptions('event_series');
    $enabledOptions = [];

    // Query for available options based the events result set.
    $query = $this->filterProvider->getBaseQuery(['event_series']);
    $query->leftJoin('node__field_event_series', 'event_series', 'n.nid = event_series.entity_id');
    $query->fields('event_series', ['field_event_series_target_id']);
    $query->distinct();
    $result = $query->execute();
    $availableOptions = $result->fetchCol();

    $enabledOptions = $this->normalizeOptions($options, $availableOptions, $params[$name] ?? []);

    return [
      '#type' => 'select',
      '#title' => $this->t('Event Series'),
      '#default_value' => $params[$name] ?? [],
      '#options' => $enabledOptions,
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Redirect the form to the url with query parameters.
    // Clear out empty values to keep the url clean.
    $params = array_filter(
      $form_state->cleanValues()->getValues(),
      function ($param) {
        return !empty($param);
      }
    );

    $path = Url::fromRoute('intercept_dashboard.event_data_dashboard', [], [
      'query' => $params,
    ]);
    $form_state->setRedirectUrl($path);
  }

  /**
   * Resets the form and url to default values.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    // Redirect the form to the base form route to reset to default values.
    $path = Url::fromRoute('intercept_dashboard.event_data_dashboard');
    $form_state->setRedirectUrl($path);
  }

}
