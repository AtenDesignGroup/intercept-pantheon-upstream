<?php

namespace Drupal\intercept_profile_content\EventSubscriber;

use Drupal\default_content\Event\DefaultContentEvents;
use Drupal\default_content\Event\ImportEvent;
use Drupal\intercept_core\ReservationManagerInterface;
use Drupal\intercept_event\RecurringEventManager;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Theme test subscriber for controller requests.
 */
class EventImportSubscriber implements EventSubscriberInterface {

  /**
   * Manages reservations.
   *
   * @var \Drupal\intercept_core\ReservationManagerInterface
   */
  protected $reservationManager;

  /**
   * Manages recurring events.
   *
   * @var \Drupal\intercept_event\RecurringEventManager
   */
  protected $recurringEventManager;

  /**
   * EventImportSubscriber constructor.
   *
   * @param \Drupal\intercept_core\ReservationManagerInterface $reservation_manager
   *   The entity field manager.
   * @param \Drupal\intercept_event\RecurringEventManager $recurring_event_manager
   *   The recurring event manager.
   */
  public function __construct(ReservationManagerInterface $reservation_manager, RecurringEventManager $recurring_event_manager) {
    $this->reservationManager = $reservation_manager;
    $this->recurringEventManager = $recurring_event_manager;
  }

  /**
   * Changes event and reservation dates to current dates.
   */
  public function onImport(ImportEvent $event) {
    if ($event->getModule() === 'intercept_profile_content') {
      $entities = $event->getImportedEntities();
      $event_entities = array_filter($entities, function ($entity, $key) {
        return $entity instanceof NodeInterface && $entity->bundle() === 'event';
      }, ARRAY_FILTER_USE_BOTH);

      $now = new \DateTime();
      $modified_days = '0';
      // First, change all event dates to current times.
      foreach ($event_entities as $base_event) {
        /** @var \Drupal\node\NodeInterface $base_event */
        $event_date_start = new \DateTime($base_event->field_date_time->value);
        $event_date_end = new \DateTime($base_event->field_date_time->end_value);
        $storage_format = 'Y-m-d\TH:i:s';
        $event_date_start->setDate($now->format('Y'), $now->format('m'), $now->format('d'));
        $event_date_end->setDate($now->format('Y'), $now->format('m'), $now->format('d'));
        $event_date_start->modify("+$modified_days days");
        $event_date_end->modify("+$modified_days days");
        if ($event_recurrence = $this->recurringEventManager->getBaseEventRecurrence($base_event)) {
          $event_date_start->modify('-1 month');
          $event_date_end->modify('-1 month');
          $event_recurrence->field_event_rrule->first()->set('value', $event_date_start->format($storage_format));
          $event_recurrence->field_event_rrule->first()->set('end_value', $event_date_end->format($storage_format));
          $event_recurrence->save();
          $recurring_rule_item = $event_recurrence->getRecurField();
          $dates = $recurring_rule_item->getHelper()->getOccurrences();
          array_shift($dates);
          foreach ($dates as $date) {
            $new_event = $base_event->createDuplicate();
            $dates = [
              'value' => $date->getStart()->format($storage_format),
              'end_value' => $date->getEnd()->format($storage_format),
            ];
            $new_event->set('field_date_time', $dates);
            $new_event->set('event_recurrence', $event_recurrence->id());
            if ($base_event->field_must_register->value) {
              $new_event->field_event_register_period->end_value = $event_date_end->format($storage_format);
            }
            $new_event->save();
            $this->reservationManager->createEventReservation($new_event, [
              'field_dates' => $dates,
            ]);
          }
        }
        $base_event->set('field_date_time', [
          'value' => $event_date_start->format($storage_format),
          'end_value' => $event_date_end->format($storage_format),
        ]);
        if ($base_event->field_must_register->value) {
          $base_event->field_event_register_period->end_value = $event_date_end->format($storage_format);
        }
        $base_event->save();
        $this->reservationManager->createEventReservation($base_event, [
          'field_dates' => [
            'value' => $event_date_start->format($storage_format),
            'end_value' => $event_date_end->format($storage_format),
          ],
        ]);
        $modified_days += 1;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DefaultContentEvents::IMPORT] = ['onImport'];
    return $events;
  }

}
