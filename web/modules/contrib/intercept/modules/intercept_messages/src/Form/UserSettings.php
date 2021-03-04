<?php

namespace Drupal\intercept_messages\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides alter functions for the user settings form.
 */
class UserSettings implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Constructs a UserSettings object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, UserDataInterface $user_data) {
    $this->moduleHandler = $module_handler;
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('user.data')
    );
  }

  /**
   * Performs the needed alterations to the settings form.
   */
  public function alterSettingsForm(array &$form, FormStateInterface $form_state) {
    $user = $form_state->getFormObject()->getEntity();
    if ($user->isAnonymous() || !$user->id()) {
      return;
    }
    $form['notifications'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notification settings'),
      '#weight' => 5,
    ];
    if ($this->moduleHandler->moduleExists('intercept_event')) {
      $email_event_enabled = $this->userData->get('intercept_messages', $user->id(), 'email_event');
      $form['notifications']['email_event'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable event notifications by email'),
        '#default_value' => isset($email_event_enabled) ? $email_event_enabled : TRUE,
        '#description' => $this->t("If disabled, we'll still contact you in some cases when necessary, but we'll try to keep it to a minimum."),
      ];
    }
    $form['actions']['submit']['#submit'][] = [$this, 'submitSettingsForm'];
  }

  /**
   * Submit callback for settings form.
   */
  public function submitSettingsForm(array &$form, FormStateInterface $form_state) {
    $user = $form_state->getFormObject()->getEntity();
    if ($this->moduleHandler->moduleExists('intercept_event')) {
      $this->userData->set('intercept_messages', $user->id(), 'email_event', $form_state->getValue('email_event'));
    }
  }

}
