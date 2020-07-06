<?php

namespace Drupal\intercept_event\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\intercept_event\SuggestedEventsProviderInterface;

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The Intercept suggested events provider.
   *
   * @var \Drupal\intercept_event\SuggestedEventsProviderInterface
   */
  protected $suggestedEventsProvider;

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
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\intercept_event\SuggestedEventsProviderInterface $suggested_events_provider
   *   The Intercept suggested events provider.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, SuggestedEventsProviderInterface $suggested_events_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->suggestedEventsProvider = $suggested_events_provider;
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
      $container->get('current_user'),
      $container->get('intercept_event.suggested_events_provider')
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

    if ($events = $this->suggestedEventsProvider->getSuggestedEvents()) {
      // Ensure no two event titles are the same. If one is the same, remove it.
      $titles = [];
      foreach ($events as $key => $node) {
        // $this->configuration['results'] = 3 results by default.
        /** @var \Drupal\node\NodeInterface $node */
        if (!in_array($node->get('title')->getString(), $titles) && count($titles) < $this->configuration['results']) {
          $titles[] = $node->get('title')->getString();
        }
        else {
          unset($events[$key]);
        }
      }
      uasort($events, 'static::sort');
      $viewBuilder = $this->entityTypeManager->getViewBuilder('node');
      $build['results'] = [
        '#theme' => 'events_recommended',
        '#content' => $viewBuilder->viewMultiple($events, $this->configuration['view_mode']),
        '#cache' => [
          'tags' => $this->getUser()->getCacheTags(),
        ],
      ];
      $build['#cache']['tags'][] = 'flagging_list';
    }

    return $build;
  }

  /**
   * Sorts events by date in ascending order.
   *
   * @param \Drupal\Core\Entity\EntityInterface $a
   *   First event for comparison.
   * @param \Drupal\Core\Entity\EntityInterface $b
   *   Second event for comparison.
   *
   * @return int
   *   If the first event is less than, equal to, or greater than the second.
   */
  public static function sort(EntityInterface $a, EntityInterface $b) {
    /** @var \Drupal\node\NodeInterface $a */
    /** @var \Drupal\node\NodeInterface $b */
    if (!$a->hasField('field_date_time') || !$b->hasField('field_date_time')) {
      return 0;
    }
    // First order by group, so that all items in the CSS_AGGREGATE_DEFAULT
    // group appear before items in the CSS_AGGREGATE_THEME group. Modules may
    // create additional groups by defining their own constants.
    if ($a->get('field_date_time')->value < $b->get('field_date_time')->value) {
      return -1;
    }
    elseif ($a->get('field_date_time')->value > $b->get('field_date_time')->value) {
      return 1;
    }
    else {
      return 0;
    }
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

}
