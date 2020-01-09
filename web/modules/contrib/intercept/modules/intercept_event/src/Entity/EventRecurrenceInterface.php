<?php

namespace Drupal\intercept_event\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Event Recurrence entities.
 *
 * @ingroup intercept_event
 */
interface EventRecurrenceInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Event Node.
   *
   * @return \Drupal\node\NodeInterface
   *   The event node.
   */
  public function getBaseEvent();

  /**
   * Gets the Event Node ID.
   *
   * @return int
   *   The event node ID.
   */
  public function getBaseEventId();

  /**
   * Gets the Event Recurrence date range string.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The date range string.
   */
  public function getDate();

  /**
   * Gets the recurring dates for this Event recurrence.
   *
   * @return \Generator|\Drupal\date_recur\DateRange[]
   *   The occurrences.
   *
   * @throws \Exception
   */
  public function getDateOccurrences();

  /**
   * Get the date storage format of this Event recurrence.
   *
   * @return string
   *   A date format string.
   */
  public function getDateStorageFormat();

  /**
   * Gets the Nodes referencing this Event recurrence.
   *
   * @return array
   *   The array of Nodes.
   */
  public function getEvents();

  /**
   * Delete all events associated with this recurrence entity.
   *
   * @return array
   *   The deleted events.
   */
  public function deleteEvents();

  /**
   * Gets the date_recur interpreted readable rule.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  public function getRecurReadable();

  /**
   * Gets the date_recur field.
   *
   * @return \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem
   *   The date_recur field.
   */
  public function getRecurField();

  /**
   * Gets the Event Recurrence creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Event Recurrence.
   */
  public function getCreatedTime();

  /**
   * Sets the Event Recurrence creation timestamp.
   *
   * @param int $timestamp
   *   The Event Recurrence creation timestamp.
   *
   * @return \Drupal\intercept_event\Entity\EventRecurrenceInterface
   *   The called Event Recurrence entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Event Recurrence revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Event Recurrence revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\intercept_event\Entity\EventRecurrenceInterface
   *   The called Event Recurrence entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Event Recurrence revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Event Recurrence revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\intercept_event\Entity\EventRecurrenceInterface
   *   The called Event Recurrence entity.
   */
  public function setRevisionUserId($uid);

}
