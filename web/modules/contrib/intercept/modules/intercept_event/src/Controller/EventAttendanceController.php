<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\intercept_event\Entity\EventAttendanceInterface;
use Drupal\intercept_event\EventAttendanceProviderInterface;
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
   * The entity attendance provider.
   *
   * @var \Drupal\intercept_event\EventAttendanceProviderInterface
   */
  protected $eventAttendanceProvider;

  /**
   * EventsController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\intercept_event\EventAttendanceProviderInterface $event_attendance_provider
   *   The entity attendance provider.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, EventAttendanceProviderInterface $event_attendance_provider) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->eventAttendanceProvider = $event_attendance_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('intercept_event.event_attendance_provider'),
    );
  }

  /**
   * Check if the attendance exists by field_event and field_user.
   *
   * @param int $nid
   *   Event node id.
   * @param int $uid
   *   User id.
   *
   * @return bool|\Drupal\intercept_event\Entity\EventAttendanceInterface
   *   The Event Attendance entity, or FALSE.
   */
  protected function createAttendance($nid, $uid) {
    $data = [
      'type' => 'event_attendance',
    ];
    $entity = $this->entityTypeManager()
      ->getStorage('event_attendance')
      ->create($data);
    $this->populateAttendance($entity, $nid, $uid);
    return $entity->save();
  }

  /**
   * Populate event attendance from registration if applicable and set user.
   *
   * @param \Drupal\intercept_event\Entity\EventAttendanceInterface $attendance
   *   An attendance entity.
   * @param int $nid
   *   The event id.
   * @param int $uid
   *   The user id.
   */
  protected function populateAttendance(EventAttendanceInterface &$attendance, $nid, $uid) {
    $attendance->set('field_event', $nid);
    $attendance->set('field_user', $uid);
    $storage = $this->entityTypeManager->getStorage('event_registration');
    $registrations = $storage->loadByProperties(['field_event' => $nid, 'field_user' => $uid]);
    if (!empty($registrations) && ($registration = reset($registrations))) {
      $value = $registration->field_registrants->getValue();
      if (!empty($value)) {
        $attendance->field_attendees->setValue($value);
      }
    }
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
   * Gets the selfCheckinForm.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node to be created or edited.
   *
   * @return array
   *   The processed form for the given entity and operation.
   */
  public function selfCheckinForm(NodeInterface $node) {
    switch ($node->checkin_period->status) {
      case 'open':
        // If user is anonymous,
        // redirect to login form.
        if ($this->currentUser()->isAnonymous()) {
          return $this->redirect(
            'user.login',
            [],
            [
              'query' => [
                'destination' => Url::fromRoute('<current>')->toString(),
              ],
            ],
          );
        }

        // If user has existing attendance,
        // add existing attendance message.
        if (!$this->eventAttendanceProvider->getEventAttendance($node->id())) {
          $this->createAttendance($node->id(), $this->currentUser()->id());
        }
        $this->messenger()->addMessage($this->t("You're all checked in! Enjoy the event"), MessengerInterface::TYPE_STATUS);
        break;

      case 'open_pending':
        $this->messenger()->addMessage($this->t("The check-in period has not yet open. Please try again later."), MessengerInterface::TYPE_ERROR);
        break;

      case 'expired':
        $this->messenger()->addMessage($this->t("We're sorry. The check-in period has closed."), MessengerInterface::TYPE_ERROR);
        break;

      case 'closed':
        $this->messenger()->addMessage($this->t("We're sorry. The check-in period is closed"), MessengerInterface::TYPE_ERROR);
        break;

      default:
        $this->messenger()->addMessage($this->t("Unable to check-in for this event"), MessengerInterface::TYPE_ERROR);
        break;

    }

    return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
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
