<?php

namespace Drupal\intercept_event\Plugin\Block;

use Drupal\Core\Block\BlockBase;
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
   * Constructs a new UserSuggestedEvents object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EventManagerInterface $intercept_event_manager, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->interceptEventManager = $intercept_event_manager;
    $this->currentUser = $current_user;
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
      $container->get('current_user')
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
    $view = $this->entityTypeManager->getViewBuilder('node');
    $storage = $this->entityTypeManager->getStorage('node');
    $node = $this->entityTypeManager->getDefinition('node');
    $customer = $this->entityTypeManager->getStorage('profile')->loadByUser($this->currentUser, 'customer');
    $query = new SuggestedEventsQuery($node, 'AND', \Drupal::service('database'), ['Drupal\Core\Entity\Query\Sql']);

    $current_date = $this->currentDate()->setTimezone(new \DateTimeZone('UTC'));
    $query
      ->condition('type', 'event', '=')
      ->condition('field_date_time', $current_date->format('c'), '>=')
      ->condition('status', 1, '=')
      ->range(0, $this->configuration['results']);

    if ($customer && $audiences = $this->simplifyValues($customer->field_audiences->getValue())) {
      $query->sortExpression('field_event_audience', $audiences);
    }
    if ($customer && $locations = $this->simplifyValues($customer->field_preferred_location->getValue())) {
      $query->sortExpression('field_location', $locations);
    }
    if ($customer && $event_types = $this->simplifyValues($customer->field_event_types->getValue())) {
      $query->sortExpression('field_event_type', $event_types);
    }
    // Add the default of featured events to sort to the top.
    $query->sortExpression('field_featured', [1]);

    $result = $query->execute();
    $nodes = $storage->loadMultiple($result);
    $build['results'] = [
      '#theme' => 'events_recommended',
      '#content' => $view->viewMultiple($nodes, $this->configuration['view_mode']),
      '#cache' => [
        'tags' => $this->getUser()->getCacheTags(),
      ],
    ];
    $build['#cache']['tags'][] = 'flagging_list';

    return $build;
  }

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

  private function currentDate() {
    return new \Drupal\Core\Datetime\DrupalDateTime();
  }
}
