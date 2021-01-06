<?php

namespace Drupal\intercept_messages;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for managing scheduled messages.
 */
interface InterceptScheduledMessageManagerInterface {

  /**
   * Sets the configuration to use for creating messages.
   *
   * @param array $configuration
   *   The configuration to use.
   */
  public function setConfiguration(array $configuration);

  /**
   * Gets the scheduled message send timestamp.
   *
   * @return int
   *   The scheduled message UNIX timestamp.
   */
  public function getSendTime();

  /**
   * Whether an entity has scheduled messages.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The source entity.
   * @param string $plugin_id
   *   The Intercept message template plugin ID.
   *
   * @return bool
   *   TRUE if submission has scheduled email.
   */
  public function hasScheduledEmail(EntityInterface $entity, $plugin_id);

  /**
   * Load scheduled messages for specified submission and handler.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The source entity.
   * @param string $plugin_id
   *   The Intercept message template plugin ID.
   *
   * @return object|null
   *   The scheduled message, or NULL.
   */
  public function load(EntityInterface $entity, $plugin_id);

  /**
   * Scheduled an email to be send at a later date.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The source entity.
   * @param string $plugin_id
   *   The Intercept message template plugin ID.
   *
   * @return string|false
   *   The status of scheduled emails. FALSE if send date is invalid.
   *   (EMAIL_SCHEDULED, EMAIL_RESCHEDULED, or EMAIL_ALREADY_SCHEDULED)
   */
  public function create(EntityInterface $entity, $plugin_id);

  /**
   * Reschedule a message that is not yet sent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The source entity.
   * @param string|null $plugin_id
   *   The Intercept message template plugin ID.
   */
  public function reschedule(EntityInterface $entity, $plugin_id = NULL);

  /**
   * Deletes scheduled messages for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The source entity.
   */
  public function delete(EntityInterface $entity);

  /**
   * Cron task for scheduling and sending emails.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The source entity.
   * @param string|null $plugin_id
   *   A Intercept message template plugin ID.
   * @param int $schedule_limit
   *   The maximum number of emails to be scheduled.
   *   If set to 0 no emails will be scheduled.
   * @param int $send_limit
   *   The maximum number of emails to be sent.
   *   If set to 0 no emails will be sent.
   *
   * @return array
   *   An associative array containing cron task stats.
   *   Includes:
   *   - self::EMAIL_SCHEDULED
   *   - self::EMAIL_RESCHEDULED
   *   - self::EMAIL_ALREADY_SCHEDULED
   *   - self::EMAIL_UNSCHEDULED
   *   - self::EMAIL_SENT
   */
  public function cron(EntityInterface $entity = NULL, $plugin_id = NULL, $schedule_limit = 1000, $send_limit = NULL);

}
