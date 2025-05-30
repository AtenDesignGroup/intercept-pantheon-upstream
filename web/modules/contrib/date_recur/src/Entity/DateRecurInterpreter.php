<?php

declare(strict_types=1);

namespace Drupal\date_recur\Entity;

use Drupal\Component\Plugin\LazyPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\date_recur\Plugin\DateRecurInterpreterPluginCollection;
use Drupal\date_recur\Plugin\DateRecurInterpreterPluginInterface;

/**
 * Defines an instance of Recurring Date interpreter.
 *
 * @ConfigEntityType(
 *   id = "date_recur_interpreter",
 *   label = @Translation("Recurring date interpreter"),
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\date_recur\Entity\Handlers\DateRecurOccurrenceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\date_recur\Form\DateRecurInterpreterAddForm",
 *       "delete" = "Drupal\date_recur\Form\DateRecurInterpreterDeleteForm",
 *       "edit" = "Drupal\date_recur\Form\DateRecurInterpreterEditForm",
 *     },
 *   },
 *   admin_permission = "date_recur manage interpreters",
 *   config_prefix = "interpreter",
 *   links = {
 *     "canonical" = "/admin/config/regional/recurring-date-interpreters/manage/{date_recur_interpreter}",
 *     "add-form" = "/admin/config/regional/recurring-date-interpreters/add",
 *     "edit-form" = "/admin/config/regional/recurring-date-interpreters/manage/{date_recur_interpreter}",
 *     "delete-form" = "/admin/config/regional/recurring-date-interpreters/manage/{date_recur_interpreter}/delete",
 *     "collection" = "/admin/config/regional/recurring-date-interpreters",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "plugin",
 *     "settings",
 *   },
 * )
 */
class DateRecurInterpreter extends ConfigEntityBase implements DateRecurInterpreterInterface {

  /**
   * The machine name.
   *
   * @var string|null
   */
  protected ?string $id = NULL;

  /**
   * The custom label.
   *
   * @var string|null
   */
  protected ?string $label = NULL;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected string $plugin;

  /**
   * Plugin settings.
   *
   * @var array
   */
  protected array $settings = [];

  /**
   * The plugin collection.
   *
   * @var \Drupal\date_recur\Plugin\DateRecurInterpreterPluginCollection|null
   */
  protected ?DateRecurInterpreterPluginCollection $pluginCollection;

  /**
   * Get the plugin collection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The plugin collection.
   */
  protected function getPluginCollection(): LazyPluginCollection {
    if (!isset($this->pluginCollection)) {
      $this->pluginCollection = new DateRecurInterpreterPluginCollection(
        \Drupal::service('plugin.manager.date_recur_interpreter'),
        $this->plugin,
        $this->settings,
        $this->id,
      );
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections(): array {
    return ['settings' => $this->getPluginCollection()];
  }

  public function getPlugin(): DateRecurInterpreterPluginInterface {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin_id): void {
    $this->plugin = $plugin_id;
    $this->getPluginCollection()->addInstanceId($plugin_id);
  }

}
