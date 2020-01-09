<?php

namespace Drupal\intercept_ils\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_ils\ILSManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The ILS settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Constructs a new ILSManager object.
   *
   * @param \Drupal\intercept_ils\ILSManager $manager
   *   The plugin.manager.intercept_ils service.
   */
  public function __construct(ILSManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.intercept_ils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'intercept_ils_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['intercept_ils.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $config = $this->config('intercept_ils.settings');

    // List all plugins of type ILS.
    $plugins = $this->manager->getDefinitions();
    $ils_plugins = [];

    foreach ($plugins as $ils) {
      $instance = $this->manager->createInstance($ils['id']);
      $plugin_name = $instance->getName();
      $ils_plugins[$ils['id']] = $plugin_name;
    }

    $form['intercept_ils_plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Which ILS will you be using with Intercept?'),
      '#description' => $this->t('Note: The plugin/module must be installed and enabled before the ILS integration will work. If you don\'t see any options here, you will need to install an ILS integration plugin/module like the <a href="https://drupal.org/project/polaris">Polaris module</a> and then return to this settings screen to complete the connection to the ILS.'),
      '#default_value' => $config->get('intercept_ils_plugin', ''),
      '#required' => TRUE,
      '#options' => $ils_plugins,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('intercept_ils.settings');
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'intercept_ils_') !== FALSE) {
        $config->set($key, $value);
      }
    }
    $config->save();
    \Drupal::messenger()->addMessage($this->t('Configuration was saved.'));
  }

}
