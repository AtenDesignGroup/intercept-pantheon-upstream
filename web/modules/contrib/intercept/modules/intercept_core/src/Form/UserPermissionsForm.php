<?php

namespace Drupal\intercept_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\user\Form\UserPermissionsForm as CoreUserPermissionsForm;

/**
 * Builds a permissions form for Intercept-specific roles.
 */
class UserPermissionsForm extends CoreUserPermissionsForm {

  /**
   * Get Intercept-specific roles.
   *
   * @return array
   *   An array of Intercept-specific user roles.
   */
  public static function roles() {
    return [
      'intercept_registered_customer',
      'intercept_kiosk',
      'intercept_staff',
      'intercept_room_reservation_approver',
      'intercept_event_organizer',
      'intercept_event_manager',
      'intercept_system_admin',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getRoles() {
    return $this->roleStorage->loadMultiple(self::roles());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $permissions = $this->permissionHandler->getPermissions();
    $intercept_permissions = [];
    $intercept_providers = [];
    foreach ($permissions as $name => $info) {
      if (strpos($info['provider'], 'intercept') !== FALSE) {
        $intercept_providers[$info['provider']] = $info['provider'];
        $intercept_permissions[$name] = $info;
      }
    }
    $build = parent::buildForm($form, $form_state);
    foreach (Element::getVisibleChildren($build['permissions'], TRUE) as $name) {
      // Leave the header rows.
      if (!empty($intercept_providers[$name])) {
        continue;
      }
      if (empty($intercept_permissions[$name])) {
        unset($build['permissions'][$name]);
      }
    }
    return $build;
  }

}
