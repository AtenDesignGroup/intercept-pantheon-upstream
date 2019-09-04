<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EventAttendanceController.
 */
class EventAttendanceController extends ControllerBase {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var EntityFormBuilderInterface
   */
  protected $entityFormBuilder;
  /**
   * EventsController constructor.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   * @param EntityFormBuilderInterface $entity_form_builder
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

  private function buildScanForm(NodeInterface $node, $type) {
    $values = [
      'field_event' => $node,
    ];

    $event_attendance = $this->entityTypeManager
      ->getStorage('event_attendance')
      ->create($values);

    return $this->entityFormBuilder->getForm($event_attendance, $type);
  }

  public function scanLookupForm(NodeInterface $node) {
    return $this->buildScanForm($node, 'scan_lookup');
  }

  public function scanGuestForm(NodeInterface $node) {
    return $this->buildScanForm($node, 'scan_guest');
  }

  public function scanForm(NodeInterface $node) {
    return $this->buildScanForm($node, 'scan');
  }

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
