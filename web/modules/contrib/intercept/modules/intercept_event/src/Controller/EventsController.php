<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\intercept_event\EventEvaluationManager;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\intercept_event\SuggestedEventsProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EventsController.
 */
class EventsController extends ControllerBase {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Intercept event evaluation manager.
   *
   * @var \Drupal\intercept_event\EventEvaluationManager
   */
  protected $evaluationManager;

  /**
   * The Intercept suggested events provider.
   *
   * @var \Drupal\intercept_event\SuggestedEventsProvider
   */
  protected $suggestedEventsProvider;

  /**
   * Constructs an EventsController object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user interface.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\intercept_event\EventEvaluationManager $evaluation_manager
   *   The Intercept event evaluation manager.
   * @param \Drupal\intercept_event\SuggestedEventsProvider $suggested_events_provider
   *   The Intercept suggested events provider.
   */
  public function __construct(AccountProxyInterface $currentUser, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entityTypeManager, EventEvaluationManager $evaluation_manager, SuggestedEventsProvider $suggested_events_provider) {
    $this->currentUser = $currentUser;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entityTypeManager;
    $this->evaluationManager = $evaluation_manager;
    $this->suggestedEventsProvider = $suggested_events_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('intercept_event.evaluation_manager'),
      $container->get('intercept_event.suggested_events_provider'),
    );
  }

  /**
   * List.
   *
   * @return string
   *   Return a render array containing the events list block.
   */
  public function list() {
    $suggested_events = $this->suggestedEventsProvider->getSuggestedEventIds();

    $build = [
      '#theme' => 'intercept_event_list',
      '#attached' => [
        'drupalSettings' => [
          'intercept' => [
            'events' => [
              'recommended' => $suggested_events,
            ],
          ],
        ],
        'library' => [
          'intercept_event/eventList',
        ],
      ],
    ];
    $this->attachFieldSettings($build);
    return $build;
  }

  /**
   * Check bundle access and permissions.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Event node to check registration access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The AccessResult object.
   */
  public function registerAccess(NodeInterface $node) {
    $access_handler = $this->entityTypeManager()->getAccessControlHandler('event_registration');
    if (!$access_handler->createAccess('event_registration')) {
      return AccessResult::forbidden();
    }
    if (!$this->isEventBundle($node)) {
      return AccessResult::forbidden();
    }
    if ($node->hasField('field_must_register') && !$node->field_must_register->value) {
      return AccessResult::forbidden();
    }

    // See if the current user is registered for this event.
    $query = $this->entityTypeManager->getStorage('event_registration')->getQuery()
      ->condition('field_user', $this->currentUser->Id())
      ->condition('status', 'active')
      ->condition('field_event', $node->Id());
    $results = $query->execute();

    $accessResult = !empty($results) ? AccessResult::allowed() : AccessResult::forbidden();

    switch ($node->registration->status) {
      case 'expired':
      case 'closed':
        return $accessResult;

      case 'full':
      case 'open_pending':
        return AccessResult::forbidden();
    }
    $access = $this->moduleHandler()->invokeAll('event_registration_event_create_access', [$node]);
    if (!empty($access)) {
      $result = array_shift($access);
      if ($result->isForbidden()) {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::allowed();
  }

  /**
   * Check bundle access and permissions.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Event node to check Registrations tab access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The AccessResult object.
   */
  public function registrationsAccess(NodeInterface $node) {
    $has_permission = $this->currentUser()->hasPermission('access event registrations tab');
    return AccessResult::allowedIf($this->isEventBundle($node) && $has_permission);
  }

  /**
   * Check bundle access and permissions.
   *
   * * @param \Drupal\node\NodeInterface $node
   *   The Event node to check Attendance tab access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The AccessResult object.
   */
  public function attendanceAccess(NodeInterface $node) {
    $has_permission = $this->currentUser()->hasPermission('access event attendance tab');
    return AccessResult::allowedIf($this->isEventBundle($node) && $has_permission);
  }

  /**
   * Whether the node is an Event type.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node entity to check.
   *
   * @return bool
   *   Whether the node is an Event type.
   */
  private function isEventBundle(NodeInterface $node) {
    return $node->bundle() == 'event';
  }

  /**
   * Gets the list builder for a Node.
   *
   * @param string $entity_type_id
   *   The entity type ID for this view builder.
   * @param \Drupal\node\NodeInterface $node
   *   The Node entity.
   *
   * @return \Drupal\Core\Entity\EntityViewBuilderInterface
   *   A view builder instance.
   */
  protected function getListBuilder($entity_type_id, NodeInterface $node = NULL) {
    $list_builder = $this->entityTypeManager()->getListBuilder($entity_type_id);
    if ($node) {
      $list_builder->setEvent($node);
    }
    return $list_builder;
  }

  /**
   * Exposes certain field config to drupalSettings.
   */
  protected function attachFieldSettings(array &$build) {
    // Load field_event_designation options.
    $event_fields = $this->entityFieldManager->getFieldStorageDefinitions('node', 'event');
    if (array_key_exists('field_event_designation', $event_fields)) {
      $options = options_allowed_values($event_fields['field_event_designation']);
      $build['#attached']['drupalSettings']['intercept']['events']['field_event_designation']['options'] = $options;
    }
  }

  /**
   * Gets the node_event_registrations build array.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node entity.
   *
   * @return array
   *   The build render array.
   */
  public function registrations(NodeInterface $node) {
    $build = [
      '#theme' => 'node_event_registrations',
      '#content' => [],
    ];
    $content = &$build['#content'];
    if ($node->hasField('field_event_user_reg_max')) {
      $content['max_registrations_per_user'] = [
        '#type' => 'item',
        '#title' => 'Maximum registrations per user:',
        '#markup' => $node->field_event_user_reg_max->value ?: "No maximum",
      ];
    }
    $properties = $node->registration->getItemDefinition()->getSetting('properties');
    $field = $node->registration;
    foreach ($properties as $name => $property) {
      // This property doesn't need to be seen by staff
      // when viewing Registrations tab.
      if ($name == 'status_user' || $name == 'status') {
        continue;
      }
      $content['details'][$name] = [
        '#type' => 'item',
        '#title' => $property->getLabel(),
        '#markup' => $field->{$name},
      ];
    }
    $content['add'] = [
      '#title' => 'Add event registration',
      '#type' => 'link',
      '#url' => Url::fromRoute('entity.event_registration.event_form', [
        'node' => $node->id(),
        'destination' => Url::fromRoute('<current>')->toString(),
      ]),
      '#attributes' => [
        'class' => ['button button-action'],
      ],
    ];
    $content['export'] = [
      '#title' => 'Export Registrant Email Addresses',
      '#type' => 'link',
      '#url' => Url::fromRoute('view.intercept_event_registration.rest_export_1', ['nid' => $node->id()]),
      '#attributes' => [
        'class' => ['button button-action'],
      ],
      '#prefix' => '&nbsp;&nbsp;'
    ];
    $content['list'] = $this->getListBuilder('event_registration', $node)->render();
    return $build;
  }

  /**
   * Gets the event_attendance list build array.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node entity.
   *
   * @return array
   *   The build render array.
   */
  public function attendance(NodeInterface $node) {
    $build = [];
    $build['export'] = [
      '#title' => 'Export Attendee Email Addresses',
      '#type' => 'link',
      '#url' => Url::fromRoute('view.intercept_event_attendance.rest_export_1', ['nid' => $node->id()]),
      '#attributes' => [
        'class' => ['button button-action'],
      ],
    ];
    $build['list'] = $this->getListBuilder('event_attendance', $node)->render();
    return $build;
  }

  /**
   * Node analysis task callback.
   */
  public function analysis(NodeInterface $node) {
    $event_uuid = $node->uuid();
    $event_nid = $node->id();
    $view_builder = $this->entityTypeManager()->getViewBuilder('node');

    return [
      '#theme' => 'node_event_analysis',
      '#content' => [
        'header' => $view_builder->view($node, 'header'),
        'attendance' => [
          'title' => $this->t('Number of Attendees'),
          'form' => $this->entityFormBuilder()->getForm($node, 'attendance'),
        ],
        'staff_evaluation' => [
          'title' => $this->t('Evaluate Your Event'),
          'form' => $this->evaluationManager->getStaffForm($node),
        ],
        'attendance_list' => [
          '#markup' => '<div id="eventAttendanceListRoot" data-event-uuid="' . $event_uuid . '" data-event-nid="' . $event_nid . '"></div>',
          '#attached' => [
            'library' => ['intercept_event/eventAttendanceList'],
          ],
        ],
        'customer_evaluations' => [
          '#markup' => '<div class="js-event-evaluations--attendee" data-event-uuid="' . $event_uuid . '" data-event-nid="' . $event_nid . '"></div>',
          '#attached' => [
            'library' => ['intercept_event/eventCustomerEvaluations'],
          ],
        ],
      ],
    ];
  }

}
