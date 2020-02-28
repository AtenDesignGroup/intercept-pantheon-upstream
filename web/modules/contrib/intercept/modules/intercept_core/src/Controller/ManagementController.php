<?php

namespace Drupal\intercept_core\Controller;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller for management routes.
 */
class ManagementController extends ManagementControllerBase {

  /**
   * {@inheritdoc}
   */
  public function alter(array &$build, $page_name) {
    if ($page_name == 'default') {
      $build['sections']['main']['#actions']['configuration'] = [
        '#link' => $this->getManagementButton('System Configuration', 'system_configuration'),
        '#weight' => 12,
      ];
    }
  }

  /**
   * Subpage of viewSettings.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return array
   *   The build render array.
   */
  public function viewDefault(AccountInterface $user, Request $request) {
    $title = $this->t('Welcome, @name', ['@name' => $user->getDisplayName()]);
    return [
      'title' => $this->title($title),
      'links' => [],
    ];
  }

  /**
   * Subpage of viewSettings.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return array
   *   The build render array.
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
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return array
   *   The build render array.
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

  /**
   * Subpage of viewSettings.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return array
   *   The build render array.
   */
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

  /**
   * Subpage of viewSettings.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return array
   *   The build render array.
   */
  public function viewSystemConfiguration(AccountInterface $user, Request $request) {
    $build = [
      'title' => $this->title('System Configuration'),
      'sections' => [
        'main' => [
          '#actions' => [
            'settings' => [
              '#link' => $this->getManagementButton('Site Settings', 'settings'),
              '#weight' => 50,
            ],
          ],
        ],
      ],
    ];
    return $build;
  }

}
