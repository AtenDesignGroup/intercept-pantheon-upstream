<?php

namespace Drupal\intercept_core\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\intercept_core\ManagementManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InterceptMenuLinks extends DeriverBase implements ContainerDeriverInterface {

  protected $managementManager;

  use StringTranslationTrait;

  public function __construct(ManagementManagerInterface $management_manager) {
    $this->managementManager = $management_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.intercept_management')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->managementManager->getPages() as $id => $page) {
      $page = (object) $page;
      // Skip if no menu link is specified.
      if (isset($page->menu_link) && !$page->menu_link) {
        continue;
      }

      $this->derivatives[$id] = [
        'title' => $page->title,
        'weight' => isset($page->menu_weight) ? $page->menu_weight : 0,
        'route_name' => "{$id}.redirect",
        'menu_name' => 'intercept-manage',
      ];
    }

    return $this->derivatives;
  }

}
