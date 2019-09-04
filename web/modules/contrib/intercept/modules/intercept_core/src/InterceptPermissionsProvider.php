<?php

namespace Drupal\intercept_core;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InterceptPermissionsProvider implements ContainerInjectionInterface {

  /**
   * @var ManagementManagerInterface
   */
  protected $managementManager;

  /**
   * Construct a new InterceptPermissionProvider instance.
   */
  public function __construct(ManagementManagerInterface $management_manager) {
    $this->managementManager = $management_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.intercept_management') 
    );
  }

  /**
   * Callback from intercept_core.permissions.yml.
   */
  public function managementPermissions() {
    $permissions = [];
    $permissions["access management"] = [
      'title' => t("Access management"),
      'description' => t('Allow access to the @link_name item in the @menu_name menu.', [
        '@link_name' => 'Manage',
        '@menu_name' => 'My Account',
      ]),
    ];
    $permissions["access all management pages"] = [
      'title' => t("Access ALL management pages"),
      'restrict access' => TRUE,
    ];
    foreach ($this->managementManager->getPages() as $key => $page) {
      // For the default page we use the simple "access management" permission.
      if ($key == 'default') {
        continue;
      }
      $permissions["access management page {$page['key']}"] = [
        'title' => t('Access management page @key', [
          '@key' => $page['key'],
        ]),
      ];
    }
    return $permissions;
  }
}
