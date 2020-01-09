<?php

namespace Drupal\intercept_event\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\intercept_event\EventManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

use Drupal\intercept_event\SuggestedEventsQuery;

/**
 * Provides a 'UserSuggestedEvents' block.
 *
 * @Block(
 *  id = "user_suggested_events",
 *  admin_label = @Translation("User suggested events"),
 * )
 */
class UserSuggestedEvents extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\intercept_event\EventManagerInterface definition.
   *
   * @var \Drupal\intercept_event\EventManagerInterface
   */
  protected $interceptEventManager;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new UserSuggestedEvents object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Drupal\Core\Entity\EntityTypeManagerInterface definition.
   * @param \Drupal\intercept_event\EventManagerInterface $intercept_event_manager
   *   Drupal\intercept_event\EventManagerInterface definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Drupal\Core\Session\AccountProxyInterface definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EventManagerInterface $intercept_event_manager, AccountProxyInterface $current_user, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->interceptEventManager = $intercept_event_manager;
    $this->currentUser = $current_user;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('intercept_event.manager'),
      $container->get('current_user'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_mode' => 'listing',
      'results' => 3,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $view_modes = \Drupal::service('entity_display.repository')->getViewModeOptionsByBundle('node', 'event');
    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $view_modes,
      '#default_value' => $this->configuration['view_mode'],
      '#weight' => '10',
    ];

    $form['results'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Results'),
      '#default_value' => $this->configuration['results'],
      '#weight' => '15',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach (['view_mode', 'results'] as $field_name) {
      $this->configuration[$field_name] = $form_state->getValue($field_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $nids = [];
    $storage = $this->entityTypeManager->getStorage('node');

    // ATTENDED EVENTS
    // SELECT e2.field_event_target_id
    // FROM event_attendance AS e
    // INNER JOIN event_attendance__field_event e2 ON e2.entity_id = e.id
    // INNER JOIN node n ON n.nid = e2.field_event_target_id
    // INNER JOIN event_attendance__field_user e3 ON e3.entity_id = e.id
    // WHERE e3.field_user_target_id = $uid
    // AND e.created > -1 year.
    $query_attended = $this->connection->select('event_attendance', 'e');
    // Get the event node ids.
    $query_attended->addField('e2', 'field_event_target_id');
    $query_attended->addJoin('INNER', 'event_attendance__field_event', 'e2', 'e2.entity_id = e.id');
    $query_attended->addJoin('INNER', 'node', 'n', 'n.nid = e2.field_event_target_id');
    $query_attended->addJoin('INNER', 'event_attendance__field_user', 'e3', 'e3.entity_id = e.id');
    $query_attended->condition('e3.field_user_target_id', $this->currentUser->id());
    // Within last year.
    $query_attended->condition('e.created', strtotime('-1 year'), '>');
    $result_attended = $query_attended->execute()->fetchAll();
    if (count($result_attended) > 0) {
      foreach ($result_attended as $attended) {
        $nids[] = $attended->field_event_target_id;
      }
    }

    // REGISTERED EVENTS
    // Same as above, but add: status != canceled.
    $query_registration = $this->connection->select('event_registration', 'r');
    // Get the event node ids.
    $query_registration->addField('r2', 'field_event_target_id');
    $query_registration->addJoin('INNER', 'event_registration__field_event', 'r2', 'r2.entity_id = r.id');
    $query_registration->addJoin('INNER', 'node', 'n', 'n.nid = r2.field_event_target_id');
    $query_registration->addJoin('INNER', 'event_registration__field_user', 'r3', 'r3.entity_id = r.id');
    $query_registration->condition('r3.field_user_target_id', $this->currentUser->id());
    // Within last year.
    $query_registration->condition('r.created', strtotime('-1 year'), '>');
    $query_registration->condition('r.status', 'canceled', '!=');
    $result_registration = $query_registration->execute()->fetchAll();
    if (count($result_registration) > 0) {
      foreach ($result_registration as $registration) {
        $nids[] = $registration->field_event_target_id;
      }
    }

    // SAVED EVENTS
    // Get nodes flagged by current user.
    $query_saved = $this->connection->select('flagging', 'f');
    // Get the event node ids.
    $query_saved->addField('f', 'entity_id');
    $query_saved->condition('f.flag_id', 'saved_event');
    $query_saved->condition('f.uid', $this->currentUser->id());
    // Within last year.
    $query_saved->condition('f.created', strtotime('-1 year'), '>');
    $result_saved = $query_saved->execute()->fetchAll();
    if (count($result_saved) > 0) {
      foreach ($result_saved as $saved) {
        $nids[] = $saved->entity_id;
      }
    }

    // Figure out the event types and locations of the past $nids.
    $nodes_historical = $storage->loadMultiple($nids);
    $event_types_historical = $locations_historical = $audiences_historical = [];
    foreach ($nodes_historical as $node_historical) {
      $audience = $node_historical->get('field_audience_primary')->getString();
      if (!in_array($audience, $audiences_historical) && !empty($audience)) {
        $audiences_historical[] = $audience;
      }
      $location = $node_historical->get('field_location')->getString();
      if (!in_array($location, $locations_historical) && !empty($location)) {
        $locations_historical[] = $location;
      }
      $type = $node_historical->get('field_event_type_primary')->getString();
      if (!in_array($type, $event_types_historical) && !empty($type)) {
        $event_types_historical[] = $type;
      }
    }

    // RECOMMENDATIONS.
    $view = $this->entityTypeManager->getViewBuilder('node');
    $node = $this->entityTypeManager->getDefinition('node');
    $customer = $this->entityTypeManager->getStorage('profile')->loadByUser($this->currentUser, 'customer');
    $query = new SuggestedEventsQuery($node, 'AND', \Drupal::service('database'), ['Drupal\Core\Entity\Query\Sql']);

    $current_date = $this->currentDate()->setTimezone(new \DateTimeZone('UTC'));
    // 3 items by default, but get 20 to remove dupl. titles.
    // Sort based on date.
    $query
      ->condition('type', 'event', '=')
      ->condition('field_date_time', $current_date->format('c'), '>=')
      ->condition('status', 1, '=')
      ->condition('field_event_designation', 'events', '=')
      ->range(0, 20)
      ->sort('field_date_time', 'ASC');

    // Exclude attended, saved, and registered events.
    if (count($nids) > 0) {
      $query->condition('nid', $nids, 'NOT IN');
    }
    // Store what we've got so far in case we need to use our fallback query.
    $query_fallback = clone $query;

    // Preferred Audiences.
    if ($customer && (($audiences = $this->simplifyValues($customer->field_audiences->getValue())) || count($audiences_historical) > 0)) {
      if (count($audiences_historical) > 0 && is_array($audiences)) {
        $audiences = array_merge($audiences, $audiences_historical);
      }
      elseif (count($audiences_historical) > 0) {
        $audiences = $audiences_historical;
      }
      if (!empty($audiences)) {
        $query->condition('field_event_audience', array_unique($audiences), 'IN');
      }
    }

    // Preferred Locations.
    if ($customer && (($locations = $this->simplifyValues($customer->field_preferred_location->getValue())) || count($locations_historical) > 0)) {
      if (count($locations_historical) > 0 && is_array($locations)) {
        $locations = array_merge($locations, $locations_historical);
      }
      elseif (count($locations_historical) > 0) {
        $locations = $locations_historical;
      }
      $query->condition('field_location', array_unique($locations), 'IN');
    }

    // Preferred Event Types.
    if ($customer && (($event_types = $this->simplifyValues($customer->field_event_types->getValue())) || count($event_types_historical) > 0)) {
      if (count($event_types_historical) > 0 && is_array($event_types)) {
        $event_types = array_merge($event_types, $event_types_historical);
      }
      elseif (count($event_types_historical) > 0) {
        $event_types = $event_types_historical;
      }
      $query->condition('field_event_type', array_unique($event_types), 'IN');
    }
    // If the customer has no preferences of any kind, show featured events.
    if (empty($audiences) && empty($locations) && empty($event_types)) {
      $query->condition('field_featured', 1, '=');
    }

    $result = $query->execute();
    $nodes = $storage->loadMultiple($result);

    // Fallback - If we still have no events, try using all ORs in query.
    if (count($nodes) == 0) {
      if (!empty($audiences) || !empty($locations) || !empty($event_types)) {
        $preferences = [
          'field_event_audience' => $audiences,
          'field_location' => $locations,
          'field_event_type' => $event_types,
        ];
        // Create the orConditionGroup.
        $orGroup = $query_fallback->orConditionGroup();
        foreach ($preferences as $field_name => $preference) {
          if (!empty($preference)) {
            $orGroup->condition($field_name, array_unique($preference), 'IN');
          }
        }
        // Add the group to the query.
        $query_fallback->condition($orGroup);
        $result = $query_fallback->execute();
        $nodes = $storage->loadMultiple($result);
      }
    }

    // Ensure no two event titles are the same. If one is the same, remove it.
    $titles = [];
    foreach ($nodes as $key => $node) {
      // $this->configuration['results'] = 3 results by default.
      if (!in_array($node->get('title')->getString(), $titles) && count($titles) < $this->configuration['results']) {
        $titles[] = $node->get('title')->getString();
      }
      else {
        unset($nodes[$key]);
      }
    }

    if (!empty($nodes)) {
      $build['results'] = [
        '#theme' => 'events_recommended',
        '#content' => $view->viewMultiple($nodes, $this->configuration['view_mode']),
        '#cache' => [
          'tags' => $this->getUser()->getCacheTags(),
        ],
      ];
      $build['#cache']['tags'][] = 'flagging_list';
    }
    else {
      $build = [];
    }

    return $build;
  }

  /**
   * Returns the current user.
   *
   * @return \Drupal\user\UserInterface
   *   The User entity.
   */
  private function getUser() {
    return $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
  }

  /**
   * Convert from sub-arrays with target_id to simple arrays.
   */
  private function simplifyValues($values) {
    return array_map(function ($value) {
      return $value['target_id'];
    }, $values);
  }

  /**
   * Gets the current DrupalDateTime.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The current DrupalDateTime.
   */
  private function currentDate() {
    return new DrupalDateTime();
  }

}
