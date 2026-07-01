<?php

namespace Drupal\existing_values_autocomplete_widget\Controller;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for handling autocomplete requests for entity field values.
 */
class AutocompleteController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var entityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The EntityFieldManager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The EntityDisplayRepository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new AutocompleteController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The EntityTypeManager manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The EntityFieldManager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepository $entityDisplayRepository
   *   The EntityDisplayRepository service.
   */
  public function __construct(EntityTypeManager $entityTypeManager, Connection $database, EntityFieldManager $entityFieldManager, EntityDisplayRepository $entityDisplayRepository) {
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository'),
    );
  }

  /**
   * Returns autocomplete values for the given field.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request made to the controller.
   * @param string $entity_type_id
   *   The entity type ID that the field belongs to.
   * @param string $bundle
   *   The bundle that the field belongs to.
   * @param string $field_name
   *   The machine name of the field.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The autocomplete values in JSON format.
   */
  public function handleAutocomplete(Request $request, $entity_type_id, $bundle, $field_name): JsonResponse {
    $existing_values = [];

    $field_widget = $this->entityDisplayRepository
      ->getFormDisplay($entity_type_id, $bundle)
      ->getComponent($field_name);
    if ($field_widget['type'] !== 'existing_autocomplete_field_widget') {
      return new JsonResponse([]);
    }

    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = mb_strtolower(array_pop($typed_string));

      $table_mapping = $this->entityTypeManager->getStorage($entity_type_id)->getTableMapping();
      $field_table = $table_mapping->getFieldTableName($field_name);
      $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id)[$field_name];
      $field_column = $table_mapping->getFieldColumnName($field_storage_definitions, 'value');

      $field_widget_settings = $field_widget['settings'];

      $query = $this->database->select($field_table, 'f');
      $query->addExpression('MIN(f.entity_id)', 'entity_id');
      $query->addField('f', $field_column, 'value');
      $query->condition($field_column, $query->escapeLike($typed_string) . '%', 'LIKE');
      $query->range(0, ((int) $field_widget_settings['suggestions_count']) ?? 15);
      $query->distinct(TRUE);
      $query->groupBy($field_column);
      $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

      foreach ($results as $result) {
        /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
        $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($result['entity_id']);
        if ($entity->access('view') && $entity->get($field_name)->access('view')) {
          $value = $result['value'];
          // Ensure values are unique:
          $existing_values[$value] = [
            'value' => $value,
            'label' => $value,
          ];
        }
      }
    }

    return new JsonResponse(array_values($existing_values));
  }

}
