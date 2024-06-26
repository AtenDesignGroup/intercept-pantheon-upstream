<?php

declare(strict_types=1);

namespace Drupal\view_unpublished;

use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use function is_array;

/**
 * A helper class used by un/-install/update hooks.
 *
 * @todo Remove this in 2.0.x.
 *
 * @see view_unpublished_install
 * @see view_unpublished_uninstall
 */
final class ViewUnpublishedInstallHelper {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Config\CachedStorage definition.
   *
   * @var \Drupal\Core\Config\CachedStorage
   */
  protected $configStorage;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ViewUnpublishedInstallHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\CachedStorage $config_storage
   *   The config storage service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, CachedStorage $config_storage) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->configStorage = $config_storage;
  }

  /**
   * Helper that flags node_access to be rebuilt if unpublished nodes exist.
   */
  public function flagRebuild(): void {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $count_unpublished = $query
      ->condition('status', FALSE)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    if ($count_unpublished > 0) {
      node_access_needs_rebuild(TRUE);
    }
  }

  /**
   * Remove the errant view_unpublished dependency from Views.
   */
  public function removeDependency(): void {

    $view_names = $this->configStorage->listAll('views.view');
    foreach ($view_names as $name) {
      $dependencies = $this->configFactory->get($name)->get('dependencies.module');
      if (is_array($dependencies) && $dependencies !== [] && array_key_exists('view_unpublished', array_flip($dependencies))) {
        $dependencies = array_diff($dependencies, ['view_unpublished']);
        $this->configFactory
          ->getEditable($name)
          ->set('dependencies.module', $dependencies)
          ->save(TRUE);
      }
    }
  }

}
