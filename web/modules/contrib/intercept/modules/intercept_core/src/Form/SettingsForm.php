<?php

namespace Drupal\intercept_core\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Intercept core settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['intercept_core.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'intercept_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('intercept_core.settings');

    $form['enable_dashboard_redirect'] = [
      '#title' => $this->t('Enable dashboard redirect'),
      '#description' => $this->t('By enabling this redirect, in most cases customers will be redirected to their Intercept Account Summary page at login.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('enable_dashboard_redirect'),
    ];

    $form['dashboard_redirect_whitelist'] = [
      '#title' => $this->t('Dashboard redirect whitelist'),
      '#description' => $this->t('By adding a path to this whitelist, no redirects will occur at login if the customer is currently viewing the listed path. Multiple paths should be separated by line breaks and the paths should be relative paths (e.g., /reserve-room, etc.).'),
      '#type' => 'textarea',
      '#default_value' => $config->get('dashboard_redirect_whitelist'),
    ];

    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();

    $form['dashboard_redirect_limit_roles'] = [
      '#title' => $this->t('Limit dashboard redirect to certain user roles'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#default_value' => $config->get('dashboard_redirect_limit_roles'),
      '#options' => array_map(function($role) { return $role->label(); }, $roles),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    $config = $this->config('intercept_core.settings');
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
