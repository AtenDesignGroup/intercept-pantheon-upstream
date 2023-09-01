<?php

namespace Drupal\intercept_location_closing;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\intercept_location_closing\Entity\InterceptLocationClosingInterface;
use Drupal\node\NodeInterface;

/**
 * InterceptLocationClosingQuery service.
 */
class InterceptLocationClosingQuery
{

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an InterceptLocationClosingQuery object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Given a location and start and end dates, find a matching location closing.
   *
   * @param \Drupal\node\NodeInterface $location
   *  The location node.
   * @param string|int $start
   *  The start date timestamp.
   * @param string|int $end
   *  The end date timestamp.
   *
   * @return array
   *   An array of location closing ids.
   */
  public function locationClosings(NodeInterface $location, $start, $end) {
    $closing_query = $this->entityTypeManager
      ->getStorage('intercept_location_closing')
      ->getQuery()
      ->accessCheck(TRUE);
    $date_group = $closing_query
      ->andConditionGroup()
      ->condition('date.value', $end, '<=')
      ->condition('date.end_value', $start, '>');
    $closings = $closing_query
      ->condition('location', $location->id())
      ->condition('status', 1)
      ->condition($date_group)
      ->execute();

    return $closings;
  }

  /**
   * Given an Event node, find conflicting closings.
   *
   * @param \Drupal\node\NodeInterface $event
   *  The event node.
   *
   * @return array
   *  An array of location closing ids.
   */
  public function closingsConflictingWithEvent(NodeInterface $event) {
    $closings = [];
    $start = $event->field_date_time->value;
    $end = $event->field_date_time->end_value;
    $locations = $event->field_location->referencedEntities();
    foreach ($locations as $location) {
      $closings = array_merge($closings, $this->locationClosings($location, $start, $end));
    }
    return $closings;
  }

  /**
   * Given a location closing, find conflicting events.
   *
   * @param \Drupal\intercept_location_closing\Entity\InterceptLocationClosingInterface $closing
   *  The location closing entity.
   * @param bool $onlyPublished
   *  Whether to only return published events.
   *
   * @return array
   *  An array of event node ids.
   */
  public function eventsConflictingWithClosing(InterceptLocationClosingInterface $closing, $onlyPublished = FALSE) {
    $events = [];
    $start = $closing->getStartTime();
    $end = $closing->getEndTime();
    $locations = $closing->getLocationIds();
    $query = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE);

    if ($onlyPublished) {
      $query->condition('status', 1);
    }

    // Events at at least one of the closing locations.
    $query->condition('field_location', $locations, 'IN');
    // Events that start OR end between the closing start and end dates.
    $date_group = $query->orConditionGroup()
      ->condition('field_date_time', [$start, $end], 'BETWEEN')
      ->condition('field_date_time.end_value', [$start, $end], 'BETWEEN');
    $query->condition($date_group);
    $events = $query->execute();
    return $events;
  }

  /**
   * Given an event, display a warning if it conflicts with a closing.
   *
   * @param \Drupal\node\NodeInterface $event
   *  The event node.
   */
  public function eventClosingConflictPrompt(NodeInterface $event) {
    if (!$event->isPublished()) {
      return;
    }

    $conflicts = $this->closingsConflictingWithEvent($event);
    if (!empty($conflicts) ) {
      $dateTime = new DrupalDateTime($event->field_date_time->value);
      // Show different messages depending on whether or not the user can edit location closings.
      $canEditClosings = \Drupal::currentUser()->hasPermission('edit location closing entities');
      if ($canEditClosings) {
        $text =  \Drupal::translation()->formatPlural(
          count($conflicts),
          'The %event event on %date is scheduled while %location is closed. Please review the following closing period:',
          'The %event event on %date is scheduled while %location is closed. Please review the following closing periods:',
          [
            '%event' => $event->toLink()->toString(),
            '%date' => \Drupal::service('date.formatter')->format($dateTime->getTimestamp(), 'custom', 'F j, Y'),
            '%location' => $event->field_location->entity->label(),
          ]
        );
        $list = '<ul>';
        $closings = \Drupal::service('entity_type.manager')
          ->getStorage('intercept_location_closing')
          ->loadMultiple($conflicts);
        foreach ($closings as $closing) {
          $list .= '<li>' . $closing->toLink($closing->label(), 'event-conflicts')->toString() . '</li>';
        }
        $list .= '</ul>';
        $message = [
          '#markup' => $text . $list
        ];
      }
      else {
        $text =  \Drupal::translation()->translate(
          'The %event event on %date is scheduled while %location is closed. Please consider rescheduling or unpublishing this event.',
          [
            '%event' => $event->toLink()->toString(),
            '%date' => \Drupal::service('date.formatter')->format($dateTime->getTimestamp(), 'custom', 'F j, Y'),
            '%location' => $event->field_location->entity->label(),
          ]
        );
        $message = [
          '#markup' => $text
        ];
      }


      \Drupal::messenger()->addWarning($message);
    }
  }
}
