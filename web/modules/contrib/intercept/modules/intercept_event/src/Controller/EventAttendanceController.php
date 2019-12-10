<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EventAttendanceController.
 */
class EventAttendanceController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * EventsController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('form_builder')
    );
  }

  /**
   * Gets the EventAttendanceScanForm.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node to be created or edited.
   * @param string $type
   *   The operation identifying the form variation to be returned.
   *
   * @return array
   *   The processed form for the given entity and operation.
   */
  private function buildScanForm(NodeInterface $node, $type) {
    $values = [
      'field_event' => $node,
    ];

    $event_attendance = $this->entityTypeManager
      ->getStorage('event_attendance')
      ->create($values);

    return $this->entityFormBuilder->getForm($event_attendance, $type);
  }

  /**
   * Gets the EventAttendanceScanLookupForm.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node to be created or edited.
   *
   * @return array
   *   The processed form for the given entity and operation.
   */
  public function scanLookupForm(NodeInterface $node) {
    return $this->buildScanForm($node, 'scan_lookup');
  }

  /**
   * Gets the EventAttendanceScanGuestForm.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node to be created or edited.
   *
   * @return array
   *   The processed form for the given entity and operation.
   */
  public function scanGuestForm(NodeInterface $node) {
    return $this->buildScanForm($node, 'scan_guest');
  }

  /**
   * Gets the EventAttendanceScanForm.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node to be created or edited.
   *
   * @return array
   *   The processed form for the given entity and operation.
   */
  public function scanForm(NodeInterface $node) {
    return $this->buildScanForm($node, 'scan');
  }

  /**
   * Gets the EventAttendanceScanForm.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node to be created or edited.
   *
   * @return array
   *   The processed form for the given entity and operation.
   */
  public function overview(NodeInterface $node) {
    $values = [
      'field_event' => $node,
    ];

    $event_attendance = $this->entityTypeManager
      ->getStorage('event_attendance')
      ->create($values);

    return $this->entityFormBuilder->getForm($event_attendance, 'scan');
  }

}
