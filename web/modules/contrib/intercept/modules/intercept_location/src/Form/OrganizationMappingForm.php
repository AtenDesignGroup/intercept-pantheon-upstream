<?php

namespace Drupal\intercept_location\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\node\Entity\Node;

/**
 * Class OrganizationMappingForm.
 */
class OrganizationMappingForm extends FormBase {

  /**
   * The ILS client.
   *
   * @var object
   */
  protected $client;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityFormBuilder definition.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $entityFormBuilder;

  /**
   * Constructs a new OrganizationMappingForm object.
   */
  public function __construct(EntityTypeManager $entity_type_manager, EntityFormBuilder $entity_form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;

    $config_factory = \Drupal::service('config.factory');
    $settings = $config_factory->get('intercept_ils.settings');
    $intercept_ils_plugin = $settings->get('intercept_ils_plugin', '');
    if ($intercept_ils_plugin) {
      $ils_manager = \Drupal::service('plugin.manager.intercept_ils');
      $ils_plugin = $ils_manager->createInstance($intercept_ils_plugin);
      $this->client = $ils_plugin->getClient();
    }
  }

  /**
   * Create a new OrganizationMappingForm instance.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'organization_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node = $this->getNode();
    $form['name'] = [
      '#type' => 'item',
      '#title' => $this->t('Title'),
      '#markup' => $node->label(),
    ];

    $form['drupal_id'] = [
      '#type' => 'item',
      '#title' => $this->t('ID'),
      '#markup' => $node->id(),
    ];

    $form['ils_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ILS ID'),
      '#maxlength' => 64,
      '#size' => 64,
    ];

    $form['ils_id'] = [
      '#type' => 'select',
      '#empty_option' => $this->t(' - No mapping - '),
      '#title' => $this->t('Organizations'),
      '#options' => $this->getOptions(),
      '#default_value' => $node->get('field_polaris_id')->getString(),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node = $this->getNode();
    $node->field_polaris_id->setValue($form_state->getValue('ils_id'));
    $node->save();
  }

  /**
   * Gets a keyed array of ILS organizations.
   *
   * @return array
   *   The ILS organizations array, keyed by OrganizationID.
   */
  private function getOptions() {
    $options = [];
    if ($this->client) {
      $organizations = $this->client->organization->getAll();
      array_walk($organizations, function ($item, $key) use (&$options) {
        $options[$item->OrganizationID] = $item->Name;
      });
    }
    return $options;
  }

  /**
   * Gets the current Node.
   *
   * @return \Drupal\node\NodeInterface
   *   The loaded Node.
   */
  private function getNode() {
    $id = \Drupal::service('current_route_match')->getParameter('node');
    return $id ? Node::load($id) : FALSE;
  }

}
