<?php

namespace Drupal\intercept_dashboard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Filter Provider service class.
 */
class FilterProvider implements FilterProviderInterface {

  use DependencySerializationTrait;

  use StringTranslationTrait;

  /**
   * Current request object.
   *
   * @var \Drupal\Core\Http\RequestStack
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
   * Constructs a new EventManager object.
   */
  public function __construct(RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->entityTypeManager = $entity_type_manager;
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
    $params = $this->currentRequest->query->all();
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
