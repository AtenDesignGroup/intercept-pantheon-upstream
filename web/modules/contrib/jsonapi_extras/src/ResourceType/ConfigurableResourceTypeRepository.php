<?php

namespace Drupal\jsonapi_extras\ResourceType;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerManager;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a repository of JSON:API configurable resource types.
 */
class ConfigurableResourceTypeRepository extends ResourceTypeRepository {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Plugin manager for enhancers.
   *
   * @var \Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerManager
   */
  protected $enhancerManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A list of all resource types.
   *
   * @var \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType[]
   */
  protected $resourceTypes;

  /**
   * A list of only enabled resource types.
   *
   * @var \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType[]
   */
  protected $enabledResourceTypes;

  /**
   * A list of all resource configuration entities.
   *
   * @var \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig[]
   */
  protected static $resourceConfigs;

  /**
   * Builds the resource config ID from the entity type ID and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return string
   *   The ID of the associated ResourceConfig entity.
   */
  protected static function buildResourceConfigId($entity_type_id, $bundle) {
    return sprintf(
      '%s--%s',
      $entity_type_id,
      $bundle
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(...$arguments) {
    parent::__construct(...$arguments);

    // This is needed, as the property is added in Drupal 8.8 and it is not
    // yet present in 8.7 or the contrib version of JSON:API at the time.
    if (property_exists($this, 'cacheTags')) {
      $this->cacheTags = array_merge($this->cacheTags, [
        'config:jsonapi_extras.settings',
        'config:jsonapi_resource_config_list',
      ]);
    }
  }

  /**
   * Injects the entity repository.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function setEntityRepository(EntityRepositoryInterface $entity_repository) {
    $this->entityRepository = $entity_repository;
  }

  /**
   * Injects the resource enhancer manager.
   *
   * @param \Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerManager $enhancer_manager
   *   The resource enhancer manager.
   */
  public function setEnhancerManager(ResourceFieldEnhancerManager $enhancer_manager) {
    $this->enhancerManager = $enhancer_manager;
  }

  /**
   * Injects the configuration factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   *
   * Mostly the same as the parent implementation, with three key differences:
   * 1. Different resource type class.
   * 2. Every resource type is assumed to be mutable.
   * 2. Field mapping not based on logic, but on configuration.
   */
  protected function createResourceType(EntityTypeInterface $entity_type, $bundle) {
    $resource_type = parent::createResourceType($entity_type, $bundle);

    $configurable_resource_type = new ConfigurableResourceType(
      $resource_type->getEntityTypeId(),
      $resource_type->getBundle(),
      $resource_type->getDeserializationTargetClass(),
      $resource_type->isInternal(),
      $resource_type->isLocatable(),
      $resource_type->isMutable(),
      $resource_type->isVersionable(),
      $resource_type->getFields()
    );

    $resource_config_id = static::buildResourceConfigId(
          $entity_type->id(),
          $bundle
      );
    $resource_config = $this->getResourceConfig($resource_config_id);

    // Inject additional services through setters. By using setter injection
    // rather that constructor injection, we prevent most future BC breaks.
    $configurable_resource_type->setJsonapiResourceConfig($resource_config);
    $configurable_resource_type->setEnhancerManager($this->enhancerManager);
    $configurable_resource_type->setConfigFactory($this->configFactory);

    return $configurable_resource_type;
  }

  /**
   * Get a single resource configuration entity by its ID.
   *
   * @param string $resource_config_id
   *   The configuration entity ID.
   *
   * @return \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig
   *   The configuration entity for the resource type.
   */
  protected function getResourceConfig($resource_config_id) {
    $null_resource = new NullJsonapiResourceConfig(
      ['id' => $resource_config_id],
      'jsonapi_resource_config'
    );
    try {
      $resource_configs = $this->getResourceConfigs();
      return isset($resource_configs[$resource_config_id]) ?
        $resource_configs[$resource_config_id] :
        $null_resource;
    }
    catch (PluginException $e) {
      return $null_resource;
    }
  }

  /**
   * Load all resource configuration entities.
   *
   * @return \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig[]
   *   The resource config entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getResourceConfigs() {
    if (!static::$resourceConfigs) {
      $resource_config_ids = [];
      foreach ($this->getEntityTypeBundleTuples() as $tuple) {
        list($entity_type_id, $bundle) = $tuple;
        $resource_config_ids[] = static::buildResourceConfigId(
          $entity_type_id,
          $bundle
        );
      }
      static::$resourceConfigs = $this->entityTypeManager
        ->getStorage('jsonapi_resource_config')
        ->loadMultiple($resource_config_ids);
    }
    return static::$resourceConfigs;
  }

  /**
   * Entity type ID and bundle iterator.
   *
   * @return array
   *   A list of entity type ID and bundle tuples.
   */
  protected function getEntityTypeBundleTuples() {
    $entity_type_ids = array_keys($this->entityTypeManager->getDefinitions());
    // For each entity type return as many tuples as bundles.
    return array_reduce($entity_type_ids, function ($carry, $entity_type_id) {
      $bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type_id));
      // Get all the tuples for the current entity type.
      $tuples = array_map(function ($bundle) use ($entity_type_id) {
        return [$entity_type_id, $bundle];
      }, $bundles);
      // Append the tuples to the aggregated list.
      return array_merge($carry, $tuples);
    }, []);
  }

  /**
   * Resets the internal caches for resource types and resource configs.
   */
  public static function reset() {
    static::$resourceConfigs = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getByTypeName($type_name) {
    $resource_types = $this->all();

    if (isset($resource_types[$type_name])) {
      return $resource_types[$type_name];
    }

    if (strpos($type_name, '--') !== FALSE) {
      list($entity_type_id, $bundle) = explode('--', $type_name);
      return static::lookupResourceType($resource_types, $entity_type_id, $bundle);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove this code when Drupal 8 support is dropped
   */
  protected function getRelatableResourceTypesFromFieldDefinition(FieldDefinitionInterface $field_definition, array $resource_types) {
    $item_definition = $field_definition->getItemDefinition();
    $entity_type_id = $item_definition->getSetting('target_type');
    $handler_settings = $item_definition->getSetting('handler_settings');
    $target_bundles = empty($handler_settings['target_bundles']) ? $this->getAllBundlesForEntityType(
      $entity_type_id
    ) : $handler_settings['target_bundles'];
    $relatable_resource_types = [];

    foreach ($target_bundles as $target_bundle) {
      if ($resource_type = static::lookupResourceType(
        $resource_types,
        $entity_type_id,
        $target_bundle
      )) {
        $relatable_resource_types[] = $resource_type;
      }
      else {
        trigger_error(
          sprintf(
            'The "%s" at "%s:%s" references the "%s:%s" entity type that does not exist. Please take action.',
            $field_definition->getName(),
            $field_definition->getTargetEntityTypeId(),
            $field_definition->getTargetBundle(),
            $entity_type_id,
            $target_bundle
          ),
          E_USER_WARNING
        );
      }
    }

    return $relatable_resource_types;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove this code when Drupal 8 support is dropped
   */
  protected function getAllBundlesForEntityType($entity_type_id) {
    // Ensure all keys are strings, because numeric values are allowed
    // as bundle names and "array_keys()" will cast "42" to 42.
    return array_map('strval', array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type_id)));
  }

  /**
   * Lookups resource type by the internal and public identifiers.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The list of resource types to do a lookup.
   * @param string $entity_type_id
   *   The entity type of a seekable resource.
   * @param string $bundle
   *   The entity bundle of a seekable resource.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType|null
   *   The resource type or NULL if it cannot be found.
   *
   * @todo Remove this code when Drupal 8 support is dropped
   */
  protected static function lookupResourceType(array $resource_types, $entity_type_id, $bundle) {
    if (isset($resource_types["$entity_type_id--$bundle"])) {
      return $resource_types["$entity_type_id--$bundle"];
    }

    foreach ($resource_types as $resource_type) {
      if ($resource_type->getEntityTypeId() === $entity_type_id && $resource_type->getBundle() === $bundle) {
        return $resource_type;
      }
    }

    return NULL;
  }

}
