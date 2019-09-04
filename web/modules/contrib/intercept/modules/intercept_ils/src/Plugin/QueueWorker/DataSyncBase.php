<?php

namespace Drupal\npq\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
* Provides base functionality for the syncing ILS data.
*/
abstract class DataSyncBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DataSyncBase constructor.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
  * {@inheritdoc}
  */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
  * {@inheritdoc}
  */
  public function processItem($data) {
    /** @var NodeInterface $node */
    $node = $this->nodeStorage->load($data->nid);
    if (!$node->isPublished() && $node instanceof NodeInterface) {
      return $this->publishNode($node);
    }
  }
}