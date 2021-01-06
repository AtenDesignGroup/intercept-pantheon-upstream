<?php

namespace Drupal\duration_field\Drush;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for the Duration Field module.
 */
class DurationFieldCommand extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a DurationFieldCommand object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    EntityFieldManagerInterface $entityFieldManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Prepares the Duration Field module for uninstall.
   *
   * Deletes all data and field instances.
   *
   * @command duration_field:prepare_uninstall
   * @aliases df-pu
   * @usage duration_field:prepare_uninstall
   *   Deletes all duration field data and duration field fields from the
   *   system.
   */
  public function prepareModuleUninstall() {
    if ($this->confirm('This will delete all duration field in the database, with no means to retrieve it. Do you wish to continue?')) {
      $this->output()->writeln("The following fields have been deleted:");
      $fields = duration_field_get_duration_fields();
      foreach ($fields as $field) {
        $this->output->writeln($field['entity_type'] . ':' . $field['bundle'] . ':' . $field['field']->getName());
        // Delete field.
        $config = FieldConfig::loadByName($field['entity_type'], $field['bundle'], $field['field']->getName());
        if ($config) {
          $config->delete();
        }
        // Delete field storage.
        $storage = FieldStorageConfig::loadByName($field['entity_type'], $field['field']->getName());
        if ($storage) {
          $storage->delete();
        }
      }
    }
    else {
      $this->output()->writeln("No loss of duration for you");
    }
  }

}
