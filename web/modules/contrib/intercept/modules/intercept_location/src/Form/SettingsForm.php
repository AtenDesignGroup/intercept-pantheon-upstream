<?php

namespace Drupal\intercept_location\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\intercept_location\LocationListBuilder;

/**
 * The intercept_location settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->setConfigFactory($config_factory);
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
    return ['intercept_location.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'intercept_location_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('intercept_location.settings');
    $form['mapping_enabled'] = [
      '#title' => $this->t('Enable ILS location mapping'),
      '#type' => 'checkbox',
      '#default_value' => $settings->get('mapping_enabled', 0),
    ];
    $form['mapping_integration_type'] = [
      '#title' => $this->t('Import organizations as locations from ILS'),
      '#type' => 'select',
      '#options' => [
        'once' => $this->t('Run once'),
        'cron' => $this->t('Run regularly with cron'),
      ],
      '#default_value' => $settings->get('mapping_integration_type', NULL),
    ];

    $entity_type = $this->entityTypeManager->getDefinition('node');
    $form['list'] = $this->entityTypeManager->createHandlerInstance(LocationListBuilder::class, $entity_type)->render();

    $form['actions']['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run import'),
      '#weight' => 10,
      '#submit' => [$this, '::runImport'],
    ];

    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove mapped locations'),
      '#weight' => 11,
      '#submit' => [$this, '::runDelete'],
      '#attributes' => ['class' => ['button--danger']],
    ];

    // @TODO: Change the autogenerated stub.
    return parent::buildForm($form, $form_state);
  }

  /**
   * Imports ILS Organizations.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function runImport(array &$form, FormStateInterface $form_state) {
    $config = $this->config('intercept_core.settings')->get('location');
    if (!empty($config['mapping_enabled'])) {
      \Drupal::service('intercept_ils.mapping')->pullOrganizations();
    }
  }

  /**
   * Deletes a Location.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function runDelete(array &$form, FormStateInterface $form_state) {
    $storage = $this->entityTypeManager->getStorage('node');
    $nodes = $storage->getQuery()
      ->condition('field_polaris_id', NULL, 'IS NOT')
      ->condition('type', 'location', '=')
      ->execute();
    $nodes = $storage->loadMultiple(array_values($nodes));
    $storage->delete($nodes);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    $config = $this->config('intercept_location.settings');
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
