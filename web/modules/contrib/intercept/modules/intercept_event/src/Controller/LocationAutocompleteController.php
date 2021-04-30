<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller for event location abbreviation autocomplete fields.
 *
 * See https://www.lucius.digital/en/blog/drupal-module-conditional-redirect-released-on-drupal-org.
 */
class LocationAutocompleteController extends ControllerBase {

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {

    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * Handler for location autocomplete request.
   */
  public function handleAutocomplete(Request $request, $field_name, $count) {
    $results = [];
    $input = $request->query->get('q');

    if (!$input) {
      return new JsonResponse($results);
    }

    $input = Xss::filter($input);

    $query = $this->nodeStorage->getQuery();
    $group = $query
      ->orConditionGroup()
      ->condition('field_location_abbreviation', $input, 'CONTAINS')
      ->condition('title', $input, 'CONTAINS');

    $query->condition($group)
      ->condition('type', 'location')
      ->condition('status', 1)
      ->sort('title', ASC)
      ->range(0, 10);

    $nodeIds = $query->execute();

    $nodes = $nodeIds ? $this->nodeStorage->loadMultiple($nodeIds) : [];

    foreach ($nodes as $node) {
      // Remove the words "Richland Library" from the autocomplete suggestions.
      $label = str_replace('Richland Library ', "", $node->label());

      // Set the value to the abbreviation.
      $results[] = [
        'value' => $node->field_location_abbreviation->value,
        'label' => $label . ' (' . $node->field_location_abbreviation->value . ')',
      ];
    }
    return new JsonResponse($results);
  }

}
