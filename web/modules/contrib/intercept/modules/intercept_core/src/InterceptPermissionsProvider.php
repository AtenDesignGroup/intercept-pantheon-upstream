<?php

namespace Drupal\intercept_core;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides permissions information for intercept_core.
 */
class InterceptPermissionsProvider implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The Intercept management plugin manager.
   *
   * @var \Drupal\intercept_core\ManagementManagerInterface
   */
  protected $managementManager;

  /**
   * Construct a new InterceptPermissionProvider instance.
   *
   * @param \Drupal\intercept_core\ManagementManagerInterface $management_manager
   *   The Intercept management plugin manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The string translation manager.
   */
  public function __construct(ManagementManagerInterface $management_manager, TranslationInterface $translation_manager) {
    $this->managementManager = $management_manager;
    $this->stringTranslation = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.intercept_management'),
      $container->get('string_translation')
    );
  }

  /**
   * Callback from intercept_core.permissions.yml.
   */
  public function managementPermissions() {
    $permissions = [];
    $permissions["access management"] = [
      'title' => $this->t("Access management"),
      'description' => $this->t('Allow access to the @link_name item in the @menu_name menu.', [
        '@link_name' => 'Manage',
        '@menu_name' => 'My Account',
      ]),
    ];
    $permissions["access all management pages"] = [
      'title' => $this->t("Access ALL management pages"),
      'restrict access' => TRUE,
    ];
    foreach ($this->managementManager->getPages() as $key => $page) {
      // For the default page we use the simple "access management" permission.
      if ($key == 'default') {
        continue;
      }
      $permissions["access management page {$page['key']}"] = [
        'title' => $this->t('Access management page @key', [
          '@key' => $page['key'],
        ]),
      ];
    }
    return $permissions;
  }

}
