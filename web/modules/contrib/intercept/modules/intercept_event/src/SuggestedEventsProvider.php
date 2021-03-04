<?php

namespace Drupal\intercept_event;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Default implementation of the event registration provider.
 */
class SuggestedEventsProvider implements SuggestedEventsProviderInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $eventStorage;

  /**
   * The Profile entity storage handler.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected $profileStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Intercept event attendance provider.
   *
   * @var \Drupal\intercept_event\EventAttendanceProviderInterface
   */
  protected $eventAttendanceProvider;

  /**
   * The Intercept event registration provider.
   *
   * @var \Drupal\intercept_event\EventRegistrationProviderInterface
   */
  protected $eventRegistrationProvider;

  /**
   * Constructs a new SuggestedEventsProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\intercept_event\EventAttendanceProviderInterface $event_attendance_provider
   *   The Intercept event attendance provider.
   * @param \Drupal\intercept_event\EventRegistrationProviderInterface $event_registration_provider
   *   The Intercept event registration provider.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, EventAttendanceProviderInterface $event_attendance_provider, EventRegistrationProviderInterface $event_registration_provider) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventStorage = $entity_type_manager->getStorage('node');
    $this->profileStorage = $entity_type_manager->getStorage('profile');
    $this->currentUser = $current_user;
    $this->eventAttendanceProvider = $event_attendance_provider;
    $this->eventRegistrationProvider = $event_registration_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggestedEvents(AccountInterface $account = NULL) {
    return $this->eventStorage->loadMultiple($this->getSuggestedEventIds());
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggestedEventIds(AccountInterface $account = NULL) {
    $event_ids = [];

    // Get user's attended, registered, and saved events.
    $past_event_ids = $this->getUserAttendedEventIds() + $this->getUserRegisteredEventIds() + $this->getUserSavedEvents();
    // Figure out the event types and locations of the past $nids.
    $past_events = $this->eventStorage->loadMultiple($past_event_ids);
    $event_types = $locations = $audiences = [];
    foreach ($past_events as $event) {
      /** @var \Drupal\node\NodeInterface $event */
      $audience = $this->simplifyValues($event->get('field_audience_primary')->getValue());
      if (!empty($audience)) {
        $audiences = $audiences + $audience;
      }
      $location = $this->simplifyValues($event->get('field_location')->getValue());
      if (!empty($location)) {
        $locations = $locations + $location;
      }
      $type = $this->simplifyValues($event->get('field_event_type_primary')->getValue());
      if (!empty($type)) {
        $event_types = $event_types + $type;
      }
    }

    // RECOMMENDATIONS.
    $customer = $this->profileStorage->loadByUser($this->currentUser, 'customer');
    $query = $this->futureEventsQuery();

    // Exclude attended, saved, and registered events.
    if (count($past_event_ids) > 0) {
      $query->condition('nid', $past_event_ids, 'NOT IN');
    }

    if ($customer) {
      // Preferred Audiences.
      if ($audience_preferences = $this->simplifyValues($customer->field_audiences->getValue())) {
        $audiences = $audiences + $audience_preferences;
      }
      // Preferred Locations.
      if ($location_preferences = $this->simplifyValues($customer->field_preferred_location->getValue())) {
        $locations = $locations + $location_preferences;
      }
      // Preferred Event Types.
      if ($type_preferences = $this->simplifyValues($customer->field_event_types->getValue())) {
        $event_types = $event_types + $type_preferences;
      }
    }
    if (!empty($audiences)) {
      $query->sortExpression('field_event_audience', $audiences);
    }
    if (!empty($locations)) {
      $query->sortExpression('field_location', $locations);
    }
    if (!empty($event_types)) {
      $query->sortExpression('field_event_type', $event_types);
    }
    // If the customer has no preferences of any kind, show featured events.
    if (empty($audiences) && empty($locations) && empty($event_types)) {
      $query->condition('field_featured', 1);
    }

    $event_ids = $query->execute();

    return array_values($event_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggestedEventsByEvent(EntityInterface $event) {
    $query = $this->futureEventsQuery();

    /** @var \Drupal\node\NodeInterface $event */
    $audiences = $this->simplifyValues($event->get('field_audience_primary')->getValue()) + $this->simplifyValues($event->get('field_event_audience')->getValue());
    if (!empty($audiences)) {
      $query->sortExpression('field_event_audience', $audiences);
      $query->sortExpression('field_audience_primary', $audiences);
    }
    $types = $this->simplifyValues($event->get('field_event_type_primary')->getValue()) + $this->simplifyValues($event->get('field_event_type')->getValue());
    if (!empty($types)) {
      $query->sortExpression('field_event_type', $types);
      $query->sortExpression('field_event_type_primary', $types);
    }
    $query->condition('nid', $event->id(), '!=');
    $event_ids = $query->execute();
    return $this->eventStorage->loadMultiple($event_ids);
  }

  /**
   * Convert from sub-arrays with target_id to simple arrays.
   */
  private function simplifyValues($values) {
    return array_column($values, 'target_id');
  }

  /**
   * Gets the current DrupalDateTime.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The current DrupalDateTime.
   */
  private function currentDate() {
    return new DrupalDateTime();
  }

  /**
   * Gets the future events query.
   *
   * @return \Drupal\intercept_event\SuggestedEventsQuery
   *   The future events query.
   */
  protected function futureEventsQuery() {
    $node = $this->entityTypeManager->getDefinition('node');
    $current_date = $this->currentDate()->setTimezone(new \DateTimeZone('UTC'));
    $query = new SuggestedEventsQuery($node, 'AND', \Drupal::service('database'), ['Drupal\Core\Entity\Query\Sql']);

    $query->condition('type', 'event', '=')
      ->condition('field_date_time', $current_date->format('c'), '>=')
      ->condition('status', 1, '=')
      ->condition('field_event_designation', 'events', '=')
      ->range(0, 100);
    $event_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'event');
    if (array_key_exists('field_event_status', $event_fields)) {
      $query->condition('field_event_status', 'canceled', '!=');
    }
    return $query;
  }

  /**
   * Gets the user's attended events.
   *
   * @todo Refactor again.
   *
   * @return array
   *   An array of user's attended event nids.
   */
  protected function getUserAttendedEventIds() {
    $attendances = array_filter($this->eventAttendanceProvider->getEventAttendances(), function ($attendance) {
      return $attendance->getCreatedTime() > strtotime('-1 year');
    });
    return array_map(function ($attendance) {
      return $attendance->getEventId();
    }, $attendances);
  }

  /**
   * Gets the user's registered events.
   *
   * @todo Refactor again.
   *
   * @return array
   *   An array of user's registered event nids.
   */
  protected function getUserRegisteredEventIds() {
    $registrations = array_filter($this->eventRegistrationProvider->getEventRegistrations(), function ($registration) {
      return ($registration->getCreatedTime() > strtotime('-1 year')) && $registration->status !== 'canceled';
    });
    return array_map(function ($registration) {
      return $registration->getEventId();
    }, $registrations);
  }

  /**
   * Gets the user's saved events.
   *
   * @todo Refactor again.
   *
   * @return array
   *   An array of user's saved event nids.
   */
  protected function getUserSavedEvents() {
    // SAVED EVENTS
    // Get nodes flagged by current user.
    $flagStorage = $this->entityTypeManager->getStorage('flagging');
    $flag_ids = $flagStorage->getQuery()
      ->condition('flag_id', 'saved_event')
      ->condition('uid', $this->currentUser->id())
      ->condition('created', strtotime('-1 year'), '>')
      ->execute();
    $flags = $flagStorage->loadMultiple($flag_ids);
    return array_map(function ($flag) {
      return $flag->getFlaggableId();
    }, $flags);
  }

}
