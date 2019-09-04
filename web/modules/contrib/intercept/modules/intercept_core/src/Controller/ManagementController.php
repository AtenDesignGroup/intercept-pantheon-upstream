<?php

namespace Drupal\intercept_core\Controller;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ManagementController.
 */
class ManagementController extends ManagementControllerBase {

  public function alter(array &$build, $page_name) {
    if ($page_name == 'default') {
      $build['sections']['main']['#actions']['configuration'] = [
        '#link' => $this->getManagementButton('System Configuration', 'system_configuration'),
        '#weight' => 12,
      ];
      $build['sections']['main']['#actions']['account'] = [
        '#link' => $this->getButton(
          'Edit My Account',
          'entity.user.edit_form',
          [
            'user' => \Drupal::currentUser()->id(),
          ]),
        '#weight' => 11,
      ];
    }
  }

  public function viewDefault(AccountInterface $user, Request $request) {
    return [
      'title' => $this->title('Welcome, @name', ['@name' => $user->getDisplayName()]),
      'links' => [],
    ];
  }

  /**
   * Subpage of viewSettings.
   */
  public function viewSettingsSite(AccountInterface $user, Request $request) {
    $build = [
      'title' => $this->title('Settings'),
    ];

    $build['form'] = $this->formBuilder()->getForm('Drupal\system\Form\SiteInformationForm');

    if ($this->moduleHandler()->moduleExists('r4032login')) {
      $build['form']['#validate'] = array_filter($build['form']['#validate'], function ($value) {
        return $value != 'r4032login_form_system_site_information_settings_validate';
      });
    }

    $this->hideElements($build['form'], ['site_information']);
    $build['form']['site_information']['site_slogan']['#access'] = FALSE;
    return $build;
  }

  /**
   * Subpage of viewSettings.
   */
  public function viewSettingsLogo(AccountInterface $user, Request $request) {
    $build = [
      'title' => $this->title('Logo Settings'),
    ];
    $default_theme = \Drupal::config('system.theme')->get('default');
    $build['form'] = \Drupal::service('form_builder')->getForm('Drupal\system\Form\ThemeSettingsForm', $default_theme);
    $this->hideElements($build['form'], ['logo']);
    return $build;
  }

  public function viewSettings(AccountInterface $user, Request $request) {
    $build = [
      'title' => $this->title('Site Settings'),
    ];

    $table = $this->table();
    $table->row($this->getButtonSubpage('logo', 'Logo'), $this->t('Change your site logo'));
    $table->row($this->getButtonSubpage('site', 'Site information'), $this->t('Change basic site information'));
    $build['links'] = $table->toArray();
    return $build;
  }

  public function viewSystemConfiguration(AccountInterface $user, Request $request) {
    $build = [
      'title' => $this->title('System configuration'),
      'sections' => [
        'main' => [
          '#actions' => [
            'settings' => [
              '#link' => $this->getManagementButton('Site Settings', 'settings'),
              '#weight' => 50,
            ]
          ]
        ]
      ],
    ];
    return $build;
  }

}
