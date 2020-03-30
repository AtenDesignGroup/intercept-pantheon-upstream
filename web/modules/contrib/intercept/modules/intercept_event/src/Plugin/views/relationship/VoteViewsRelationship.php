<?php

namespace Drupal\intercept_event\Plugin\views\relationship;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\user\RoleInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a views relationship to select vote content by a vote.
 *
 * @ViewsRelationship("vote_relationship")
 */
class VoteViewsRelationship extends RelationshipPluginBase {

  /**
   * The Page Cache Kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The Vote type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $voteTypeStorage;

  /**
   * Constructs a VoteViewsRelationship object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $page_cache_kill_switch
   *   The kill switch.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $vote_type_storage
   *   The Vote type storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, KillSwitch $page_cache_kill_switch, AccountProxyInterface $current_user, ConfigEntityStorageInterface $vote_type_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pageCacheKillSwitch = $page_cache_kill_switch;
    $this->currentUser = $current_user;
    $this->definition = $plugin_definition + $configuration;
    $this->voteTypeStorage = $vote_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('page_cache_kill_switch'),
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('vote_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['vote'] = ['default' => NULL];
    $options['required'] = ['default' => TRUE];
    $options['user_scope'] = ['default' => 'current'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $entity_type = $this->definition['referenced_entity_type'];
    $form['admin_label']['admin_label']['#description'] = $this->t('The name of the selected flag makes a good label.');

    $form['vote'] = [
      '#type' => 'radios',
      '#title' => $this->t('Vote'),
      '#default_value' => $this->options['vote'],
      '#required' => TRUE,
    ];

    $vote_storage = \Drupal::service('entity_type.manager')->getStorage('vote_type');

    foreach ($vote_storage->loadMultiple() as $id => $vote_type) {
      $form['vote']['#options'][$id] = $vote_type->label();
    }

    $form['user_scope'] = [
      '#type' => 'radios',
      '#title' => $this->t('By'),
      '#options' => ['current' => $this->t('Current user'), 'any' => $this->t('Any user')],
      '#default_value' => $this->options['user_scope'],
    ];

    $form['required']['#title'] = $this->t('Include only voted content');
    $form['required']['#description'] = $this->t('If checked, only content that
      has this vote will be included. Leave unchecked to include all content;
 or, in combination with the <em>Flagged</em> filter, <a href="@unflagged-url">
to limit the results to specifically unflagged content</a>.', ['@unflagged-url' => 'http://drupal.org/node/299335']);

    if (!$form['vote']['#options']) {
      $form = [
        'error' => [
          '#markup' => '<p class="error form-item">' . $this->t('No %type flags exist. You must first <a href="@create-url">create a %type flag</a> before being able to use this relationship type.', ['%type' => $entity_type, '@create-url' => Url::fromRoute('entity.flag.collection')->toString()]) . '</p>',
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!($vote = $this->getVote())) {
      return;
    }

    $this->definition['extra'][] = [
      'field' => 'type',
      'value' => $vote->id(),
      'numeric' => FALSE,
    ];

    if ($this->options['user_scope'] == 'current') {
      $this->definition['extra'][] = [
        'field' => 'user_id',
        'value' => '***CURRENT_USER***',
        'numeric' => TRUE,
      ];
      $flag_roles = user_roles(FALSE, "flag " . $vote->id());
      if (isset($flag_roles[RoleInterface::ANONYMOUS_ID]) && $this->currentUser->isAnonymous()) {
        // Disable page caching for anonymous users.
        $this->pageCacheKillSwitch->trigger();

        // Add condition to the join on the PHP session id for anonymous users.
        $this->definition['extra'][] = [
          'field' => 'session_id',
          'value' => '***FLAG_CURRENT_USER_SID***',
        ];
      }
    }

    parent::query();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    // Relationships need to depend on the flag that creates the relationship.
    $dependencies['config'][] = $this->getVote()->getConfigDependencyName();
    return $dependencies;
  }

  /**
   * Get the flag of the relationship.
   *
   * @return \Drupal\votingapi\VoteInterface|null
   *   The vote being selected by in the view.
   */
  public function getVote() {
    $vote = $this->voteTypeStorage->load($this->options['vote']);
    return $vote;
  }

}
