<?php

namespace Drupal\intercept_messages\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\intercept_messages\Plugin\InterceptMessageTemplateManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides alter functions for the event registration settings form.
 */
class MessagesSettingsBase implements ContainerInjectionInterface {

  use ConfigFormBaseTrait;
  use StringTranslationTrait;

  /**
   * The message config.
   *
   * @var Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Intercept message template manager.
   *
   * @var \Drupal\intercept_messages\Plugin\InterceptMessageTemplateManager
   */
  protected $messageManager;

  /**
   * MessagesSettingsBase constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\intercept_messages\Plugin\InterceptMessageTemplateManager $message_manager
   *   The Intercept message template manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, InterceptMessageTemplateManager $message_manager) {
    $this->configFactory = $config_factory;
    $this->messageManager = $message_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.intercept_message_template')
    );
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * Sets the editable config.
   */
  protected function setConfig($config) {
    $this->config = $config;
  }

  /**
   * Gets the message template types that will be used in this form.
   *
   * @return array
   *   An array of message template names.
   */
  protected function getMessageTemplateTypes() {
    return [];
  }

  /**
   * Gets the message template plugins that will be used in this form.
   *
   * @return array
   *   An array of InterceptMessageTemplate plugin instances.
   */
  protected function getMessageTemplatePlugins() {
    $types = $this->getMessageTemplateTypes();
    $definitions = $this->messageManager->getDefinitionsByTypes($types);
    $plugins = array_map(function ($template_definition) {
      $template_id = $template_definition['id'];
      return $this->messageManager->createInstance($template_id);
    }, $definitions);
    foreach ($plugins as $plugin_name => $plugin) {
      if (strstr($plugin_name, 'sms')) {
        unset($plugins[$plugin_name]);
      }
    }
    return $plugins;
  }

  /**
   * Performs the needed alterations to the settings form.
   */
  public function alterSettingsForm(array &$form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Emails'),
      '#tree' => TRUE,
    ];

    foreach ($this->getMessageTemplatePlugins() as $template) {
      /** @var \Drupal\intercept_messages\InterceptMessageTemplateInterface $template */
      $template_id = $template->getPluginId();
      $template_label = $template->label();
      $config = $this->config->get('email.' . $template_id) ?: [];
      $template->setConfiguration($config);
      $form['email'][$template_id] = [];
      $subform_state = SubformState::createForSubform($form['email'][$template_id], $form['email'], $form_state);
      $form['email'][$template_id] = [
        '#type' => 'details',
        '#title' => $template_label,
        '#group' => 'email',
        '#tree' => TRUE,
      ] + $template->buildConfigurationForm($form['email'][$template_id], $subform_state);
    }
    $form['actions']['submit']['#submit'][] = [$this, 'submitSettingsForm'];
  }

  /**
   * Submit callback for settings form.
   */
  public function submitSettingsForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getMessageTemplatePlugins() as $template) {
      /** @var \Drupal\intercept_messages\InterceptMessageTemplateInterface $template */
      $template_id = $template->getPluginId();
      $subform_state = SubformState::createForSubform($form['email'][$template_id], $form, $form_state);
      $template->submitConfigurationForm($form['email'][$template_id], $subform_state);
      $this->config
        ->set('email.' . $template_id, $template->getConfiguration());
    }
    $this->config
      ->save();
  }

}
