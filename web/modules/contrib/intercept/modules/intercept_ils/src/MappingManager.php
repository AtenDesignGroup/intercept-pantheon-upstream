<?php

namespace Drupal\intercept_ils;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\intercept_ils\ILSManager;

/**
 * The ILS mapping manager.
 */
class MappingManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The ILS client.
   *
   * @var object
   */
  private $client;

  /**
   * The query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  private $queryFactory;

  /**
   * The Intercept ILS configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $interceptILSPlugin;

  /**
   * Mapping constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory, ConfigFactoryInterface $config_factory, ILSManager $ils_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $settings = $config_factory->get('intercept_ils.settings');
    $intercept_ils_plugin = $settings->get('intercept_ils_plugin', '');
    if ($intercept_ils_plugin) {
      $this->interceptILSPlugin = $ils_manager->createInstance($intercept_ils_plugin);
      $this->client = $this->interceptILSPlugin->getClient();
    }
    $this->queryFactory = $query_factory;
  }

  /**
   * Loads a mapping by Location or User.
   *
   * @var \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity.
   */
  public function loadByEntity(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    if ($this->client) {
      if ($entity_type == 'node' && $bundle == 'location') {
        return $this->loadByNodeLocation($entity);
      }
      if ($entity_type == 'user') {
        return $this->loadByUser($entity);
      }
    }
    return FALSE;
  }

  /**
   * Load a mapping by Node Location.
   *
   * @var \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity.
   */
  private function loadByNodeLocation(EntityInterface $entity) {
    $organization = $this->client->organization->getByNode($entity);
    $data = [
      'id' => !empty($organization) ? $organization->OrganizationID : NULL,
      'data' => !empty($organization) ? $organization : [],
    ];
    return $this->createMappingInstance($data);
  }

  /**
   * Load a mapping by User.
   *
   * @var \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity.
   */
  private function loadByUser(EntityInterface $entity) {
    if (!$patron = $this->client->patron->getByUser($entity)) {
      return FALSE;
    }
    $basic_data = $patron->data();
    $data = [
      'id' => !empty($basic_data) ? $basic_data->PatronID : NULL,
      'data' => !empty($basic_data) ? $basic_data : [],
    ];
    return $this->createMappingInstance($data);
  }

  /**
   * Creates a mapping instance.
   */
  private function createMappingInstance($data) {
    return new class($data) {
      /**
       * The mapping data.
       *
       * @var array
       */
      private $data;

      /**
       * Mapping instance constructor.
       *
       * @param mixed $data
       *   The mapping data.
       */
      public function __construct($data) {
        $this->data = (array) $data;
      }

      /**
       * Gets the mapping data.
       */
      public function data() {
        return !empty($this->data['data']) ? $this->data['data'] : [];
      }

      /**
       * Gets the mapping ID.
       */
      public function id() {
        return !empty($this->data['id']) ? $this->data['id'] : FALSE;
      }

    };
  }

  /**
   * Loads a Patron by barcode.
   *
   * @var mixed $barcode
   *   The barcode.
   */
  public function loadByBarcode($barcode) {
    // First load from mapping.
    if ($this->client) {
      $user = $this->client->patron->getUserByBarcode($barcode);
    }
    else {
      $user = NULL;
    }

    // Then try Drupal as a regular username.
    /** @var UserStorage $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage('user');
    if (!$user && ($users = $storage->loadByProperties(['name' => $barcode]))) {
      $user = reset($users);
    }
    // Then try the ILS directly.
    if (!$user && ($patron = $this->client->patron->validate($barcode))) {
      // Try again with the 'actual' barcode, because the original value
      // could have been a username.
      $user = $this->client->patron->getUserByBarcode($patron->barcode);
      // Finally go through registration process.
      if (!$user) {
        // @see Auth::authenticate()
        $data = $patron->basicData();
        $account_data = [
          'name' => $patron->barcode(),
          'mail' => $data->EmailAddress,
          'init' => $data->EmailAddress,
        ];
        // Create a Drupal user automatically and return the new user_id.
        $plugin_id = $this->interceptILSPlugin->getId();
        $user = \Drupal::service('externalauth.externalauth')->register($patron->barcode(), $plugin_id, $account_data, $data);
      }
    }

    return $user;
  }

  /**
   * This will change heavily.
   *
   * @see intercept_ils_cron()
   */
  public function pullOrganizations() {
    foreach ($this->getNewOrganizations() as $id) {
      $org = $this->client->organization->getById($id);
      // Match by name if possible.
      $query = $this->queryFactory->get('node')
        ->condition('type', 'location')
        ->condition('title', $org->Name, '=')
        ->execute();
      if ($query) {
        $node = $this->entityTypeManager->getStorage('node')->load(reset($query));
      }
      else {
        $node = $this->entityTypeManager->getStorage('node')->create(['type' => 'location']);
        $node->setTitle($org->DisplayName);
      }
      $node->field_ils_id->setValue($org->OrganizationID);
      $node->save();
    }
  }

  /**
   * Gets new organizations added to the ILS.
   */
  private function getNewOrganizations() {
    // Get array of the organization ids.
    $ids = array_map(function ($org) {
      return $org->OrganizationID;
    }, $this->client->organization->getAll());

    $query = $this->queryFactory->get('node')
      ->condition('type', 'location')
      ->condition('field_ils_id', $ids, 'IN')
      ->execute();
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple(array_values($query));

    return array_filter($ids, function ($id) use ($nodes) {
      // Then filter out which ones already have corresponding nodes.
      foreach ($nodes as $node) {
        if ($node->field_ils_id->getString() == $id) {
          return FALSE;
        }
      }
      return TRUE;
    });
  }

}
