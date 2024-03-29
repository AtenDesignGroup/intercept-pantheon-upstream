<?php

namespace Drupal\intercept_dashboard;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Filter Provider service class.
 */
class FilterProvider implements FilterProviderInterface {

  use DependencySerializationTrait;

  use StringTranslationTrait;

  /**
   * Current request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $currentRequest;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Cached options.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Cached query params.
   *
   * @var array
   */
  protected $params;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritDoc}
   */
  protected function shouldExcludeFilter($filter, $filtersToExclude = []) {
    $params = $this->params;
    return in_array($filter, $filtersToExclude) || !isset($params[$filter]) || empty($params[$filter]);
  }

  /**
   * Constructs a new EventManager object.
   */
  public function __construct(RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->params = $this->currentRequest->query->all();
  }

  /**
   * {@inheritDoc}
   */
  public function getBaseQuery($filtersToExclude = []) {
    // Your getBaseQuery function implementation here.
    // Get filter and sort criteria from url query params.
    $params = $this->params;

    $query = $this->database->select('node_field_data', 'n');
    $query->condition('n.type', 'event');
    $query->condition('n.status', 1);

    /**
     * @todo Make sure the default value is shared with the form.
     */
    $startDate = new DrupalDateTime($params['start'] ?? date('Y-m-01'));
    $startDate->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $startFormatted = $startDate->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    /**
     * @todo Make sure the default value is shared with the form.
     */
    $endDate = new DrupalDateTime($params['end'] ?? date('Y-m-d'));
    // Increase the date by 1 day to include the current day.
    $endDate->add(\DateInterval::createFromDateString('1 day'));
    $endDate->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $endFormatted = $endDate->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    $query->join('node__field_date_time', 'date', 'n.nid = date.entity_id');
    $query->condition('date.field_date_time_value', [$startFormatted, $endFormatted], 'BETWEEN');

    // Add keyword condition.
    // @todo Can this be integrated into search_api?
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

    // Add external presenter condition.
    if (isset($params['external_presenter']) && !empty($params['external_presenter'])) {
      $query->leftJoin('node__field_presenter', 'presenter', 'n.nid = presenter.entity_id');
      if ($params['external_presenter'] === 'yes') {
        $query->isNotNull('presenter.field_presenter_value');
      }
      else {
        $query->isNull('presenter.field_presenter_value');
      }
    }

    // Add created_by condition.
    if (isset($params['created_by']) && !empty($params['created_by'])) {
      $query->condition('n.uid', array_values($params['created_by'][0]), 'IN');
    }

    // Add primary audience condition.
    if (!$this->shouldExcludeFilter('audience', $filtersToExclude)) {
      $query->join('node__field_audience_primary', 'audience', 'n.nid = audience.entity_id AND audience.field_audience_primary_target_id IN (:audience[])', [':audience[]' => $params['audience']]);
    }

    // Add primary event type condition.
    if (!$this->shouldExcludeFilter('type', $filtersToExclude)) {
      $query->join('node__field_event_type_primary', 'event_type', 'n.nid = event_type.entity_id AND event_type.field_event_type_primary_target_id IN (:type[])', [':type[]' => $params['type']]);
    }

    // Add tags condition.
    if (!$this->shouldExcludeFilter('tags', $filtersToExclude)) {
      $query->join('node__field_event_tags', 'event_tags', 'n.nid = event_tags.entity_id AND event_tags.field_event_tags_target_id IN (:tags[])', [':tags[]' => $params['tags']]);
    }

    // Add location condition.
    if (!$this->shouldExcludeFilter('location', $filtersToExclude)) {
      $query->join('node__field_location', 'location', 'n.nid = location.entity_id AND location.field_location_target_id IN (:location[])', [':location[]' => $params['location']]);
    }

    // Add event series condition.
    if (isset($params['event_series']) && !empty($params['event_series'])) {
      $query->join('node__field_event_series', 'event_series', 'n.nid = event_series.entity_id AND event_series.field_event_series_target_id IN (:event_series[])', [':event_series[]' => $params['event_series']]);
    }

    return $query;
  }

  /**
   * {@inheritDoc}
   */
  public function getRelatedTermOptions(string $vocabulary) {
    if (!isset($this->options[$vocabulary])) {
      $options = [];

      /** @var TermStorageInterface $termStorage */
      $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
      $terms = $termStorage->loadTree($vocabulary);

      foreach ($terms as $term) {
        $options[$term->tid] = $term->name;
      }

      $this->options[$vocabulary] = $options;
    }

    return $this->options[$vocabulary];
  }

  /**
   * {@inheritDoc}
   */
  public function getRelatedContentOptions(string $bundle) {
    if (!isset($this->options[$bundle])) {
      $options = [];

      $nids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->condition('type', $bundle)
        ->sort('title', 'asc')
        ->execute();

      $nodes = Node::loadMultiple($nids);

      foreach ($nodes as $node) {
        $options[$node->id()] = $node->getTitle();
      }

      $this->options[$bundle] = $options;
    }

    return $this->options[$bundle];

  }

  /**
   * {@inheritDoc}
   */
  public function getRelatedUserOptions(array $ids) {
    if (!isset($this->options['users'])) {
      $options = [];
      $options = [];

      $users = User::loadMultiple($ids);

      /** @var \Drupal\user\Entity\UserInterface $user */
      foreach ($users as $user) {
        $options[$user->id()] = $user->label();
      }

      $this->options['users'] = $options;
    }

    return $this->options['users'];
  }

  /**
   * {@inheritDoc}
   */
  public function getRemoveUrl(string $param, ?string $value = NULL) {
    $params = $this->params;
    $options = [
      'query' => $params,
    ];
    // We don't know how many results this will provide so we revert back to the first page of results.
    unset($options['query']['page']);

    if (empty($value) || !isset($params[$param][$value])) {
      $value = $params[$param];
      unset($options['query'][$param]);
    }
    else {
      unset($options['query'][$param][$value]);
    }

    return Url::fromRoute(
      '<current>',
      [],
      $options,
    );
  }

}
