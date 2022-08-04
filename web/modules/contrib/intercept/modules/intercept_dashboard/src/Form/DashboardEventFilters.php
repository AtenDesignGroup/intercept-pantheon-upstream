<?php

namespace Drupal\intercept_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Intercept Dashboard event filter form.
 */
class DashboardEventFilters extends FormBase {

  /**
   * EntityQuery service.
   *
   * @var QueryInterface $entityQuery
   */
  protected $entityQuery;

  /**
   * FilterProvider service.
   *
   * @var \Drupal\intercept_dashboard\FilterProviderInterface $filterProvider
   */
  protected $filterProvider;

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
    $params =  \Drupal::request()->query->all();

    $form['date'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['filters__inputs-inner']
      ]
    ];

    $form['date']['start'] = [
      '#type' => 'date',
      '#title' => $this
        ->t('Start Date'),
      // First day of the current month
      '#default_value' => $params['start'] ?? date('Y-m-01'),
      '#required' => TRUE
    ];

    $form['date']['end'] = [
      '#type' => 'date',
      '#title' => $this
        ->t('End Date'),
      // Today
      '#default_value' => $params['end'] ?? date('Y-m-d'),
      '#required' => TRUE
    ];

    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['filters__inputs-inner']
      ]
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

    // Created by filter
    $form['filters']['created_by'] = [
      '#title' => $this->t('Created by'),
      '#type' => 'entity_autocomplete',
      '#tags' => TRUE,
      '#target_type' => 'user',
      '#default_value' => !empty($params['created_by'])
        ? User::loadMultiple(array_values($params['created_by'][0]))
        : ''
    ];

    $form['filters']['external_presenter'] = [
      '#type' => 'select',
      '#title' => $this->t('External Presenter'),
      '#default_value' => $params['external_presenter'] ?? NULL,
      '#empty_option' => $this->t('- Any -'),
      '#options' => [
        'yes' => 'Yes',
        'no' => 'No'
      ],
    ];

    /**
     * Form Actions
     */
    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['filters__actions']
      ]
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['actions']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Clear'),
      '#submit' => ['::resetForm'],
    );

    return $form;
  }

  /**
   * Gets the Audience filter form element.
   *
   * @param string $name
   *  The name of the form element.
   * @param array $params
   *  The url parameters used to populate the default_value
   *
   * @return array Render Array
   */
  public function getAudienceFilter($name, $params) {
    $options = $this->filterProvider->getRelatedTermOptions('audience');

    return [
      '#type' => 'select',
      '#title' => $this->t('Audience'),
      '#default_value' => $params[$name] ?? [],
      '#options' => $options,
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];
  }

  /**
   * Gets the Event Type filter form element.
   *
   * @param string $name
   *  The name of the form element.
   * @param array $params
   *  The url parameters used to populate the default_value
   *
   * @return array Render Array
   */
  public function getEventTypeFilter($name, $params) {
    $options = $this->filterProvider->getRelatedTermOptions('event_type');

    return [
      '#type' => 'select',
      '#title' => $this->t('Event Type'),
      '#default_value' => $params[$name] ?? [],
      '#options' => $options,
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];
  }

  /**
   * Gets the Tags filter form element.
   *
   * @param string $name
   *  The name of the form element.
   * @param array $params
   *  The url parameters used to populate the default_value
   *
   * @return array Render Array
   */
  public function getTagFilter($name, $params) {
    $options = [];

    /** @var TermStorageInterface $termStorage */
    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $terms = $termStorage->loadTree('tag');

    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    return [
      '#type' => 'select',
      '#title' => $this->t('Tag'),
      '#default_value' => $params[$name] ?? [],
      '#options' => $options,
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];
  }

  /**
   * Gets the Location filter form element.
   *
   * @param string $name
   *  The name of the form element.
   * @param array $params
   *  The url parameters used to populate the default_value
   *
   * @return array Render Array
   */
  public function getLocationFilter($name, $params) {
    $options = $this->filterProvider->getRelatedContentOptions('location');

    return [
      '#type' => 'select',
      '#title' => $this->t('Location'),
      '#default_value' => $params[$name] ?? [],
      '#options' => $options,
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];
  }

  /**
   * Gets the Event Series filter form element.
   *
   * @param string $name
   *  The name of the form element.
   * @param array $params
   *  The url parameters used to populate the default_value
   *
   * @return array Render Array
   */
  public function getEventSeriesFilter($name, $params) {
    $options = $this->filterProvider->getRelatedContentOptions('event_series');

    return [
      '#type' => 'select',
      '#title' => $this->t('Event Series'),
      '#default_value' => $params[$name] ?? [],
      '#options' => $options,
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Redirect the form to the url with query parameters.

    // Clear out empty values to keep the url clean
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
   * Resets the form and url to default values
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    // Redirect the form to the base form route to reset to default values.
    $path = Url::fromRoute('intercept_dashboard.event_data_dashboard');
    $form_state->setRedirectUrl($path);
  }
}
