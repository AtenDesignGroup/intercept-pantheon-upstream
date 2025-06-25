<?php

namespace Drupal\intercept_event;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\duration_field\Service\DurationServiceInterface;

/**
 * Class EventCheckinPeriodController.
 */
class CheckinPeriodInvalidator implements CheckinPeriodInvalidatorInterface {

  use LoggerChannelTrait;

  use StringTranslationTrait;

  // Minimum amount of time to pass before attempting to
  // invalidate the checkin cache.
  const CHECKIN_PERIOD_MAX_AGE = 60;

  // The state API identifier.
  const STATE_ID = 'intercept_event_checkin_invalidate_last_run';

  /**
   * The checkin period configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity attendance provider.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The core time utility.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The duration field service.
   *
   * @var \Drupal\duration_field\Service\DurationServiceInterface
   */
  protected $durationService;

  /**
   * The core CacheTagsInvalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * EventsController constructor.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The duration field service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The core time utility.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The core time utility.
   * @param \Drupal\duration_field\Service\DurationServiceInterface $duration_service
   *   The duration field service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The core state service.
   */
  public function __construct(
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    ConfigFactoryInterface $config_factory,
    TimeInterface $time,
    DurationServiceInterface $duration_service,
    EntityTypeManagerInterface $entity_type_manager,
    StateInterface $state
  ) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->durationService = $duration_service;
    $this->time = $time;
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->config = $config_factory->get('intercept_event.checkin');
  }

  /**
   * {@inheritDoc}
   */
  public function updateCheckinPeriods(array $values) {
    // Here we'll invalidate cache tags for all nodes with check-in periods that
    // started or ended during either the old EventCheckinSettingsForm configs
    // or the new configs. We'll also set $lastRun to be $now minus the check-in
    // end new config.
    // Get the request time.
    $now = $this->time->getRequestTime();
    $events = [];

    // Create array of columns in node__field_date_time with which to concern
    // ourselves.
    $column = [
      'start' => 'field_date_time.value',
      'end' => 'field_date_time.end_value',
    ];

    // Invalidate cache tags.
    $query = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'event')
      ->condition('status', '1');

    // Initialize an orConditionGroup.
    $group = $query->orConditionGroup();

    // Add query conditions, looking for events with check-in start and end
    // points within whichever config (old, new) is wider.
    foreach (['start', 'end'] as $endpoint) {
      $comparisonArray = [];
      foreach (['old', 'new'] as $setting) {
        $current = DrupalDateTime::createFromTimestamp($now);
        $current->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
        $interval = ($setting == 'old')
          ? $this->durationService->getDateIntervalFromDurationString($this->config->get('checkin_' . $endpoint))
          : $values['checkin_' . $endpoint];
        $method = ($endpoint == 'start') ? 'add' : 'sub';
        $value = $current->$method($interval)->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
        $comparisonArray[$setting] = $value;
      }

      $current = DrupalDateTime::createFromTimestamp($now);
      $current->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));

      // Use different operators in comparing the $comparisonArray depending on
      // the $endpoint.
      $otherRangePoint = ($endpoint == 'start') ? max($comparisonArray['old'], $comparisonArray['new'])
        : min($comparisonArray['old'], $comparisonArray['new']);
      $range = [$current->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), $otherRangePoint];

      // Our db query needs these to be sorted ascending.
      sort($range);

      // Add an 'or' condition to the query.
      $group->condition($column[$endpoint], $range, 'BETWEEN');
    }

    $events = $query->condition($group)
      ->execute();

    if (!empty($events)) {
      // Generate cache tags from nids.
      $cacheTags = array_map(function ($nid) {
        return 'node:' . $nid;
      }, $events);

      $this->cacheTagsInvalidator->invalidateTags($cacheTags);

      // Log the invalidated cache tags.
      $logger = $this->getLogger('intercept_event');
      $logger->log(RfcLogLevel::INFO, $this->t('Cache tags invalidated due to change in check-in period status: @events', [
        '@events' => implode(', ', $cacheTags),
      ]));
    }

    $lastRun = $now - $this->durationService->getSecondsFromDurationString($this->config->get('checkin_end'));
    $this->state->set(self::STATE_ID, $lastRun);

    return array_values($events);
  }

  /**
   * {@inheritDoc}
   */
  public function invalidateCheckinPeriods() {
    // Get the request time.
    $now = $this->time->getRequestTime();
    // Get the last time this was run.
    $lastRun = $this->state->get(self::STATE_ID);

    // Default the last run to now minus the checkin start period.
    if (!$lastRun) {
      $lastRun = $now - $this->durationService->getSecondsFromDurationString($this->config->get('checkin_end'));
      $this->state->set(self::STATE_ID, $lastRun);
    }

    $events = [];

    if ($now - $lastRun > self::CHECKIN_PERIOD_MAX_AGE) {
      // Since the actual checkin period is a computed field, not stored
      // in the database, we need to manually adjust the start and end ranges
      // based on the checkin period durations so we can query the event start
      // and end dates directly.
      // Clone the dates so we don't manaipulate the originals.
      $previous = DrupalDateTime::createFromTimestamp($lastRun);
      $previous->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
      $current = DrupalDateTime::createFromTimestamp($now);
      $current->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));

      $startRange = [
        'start' => $previous->add($this->durationService->getDateIntervalFromDurationString($this->config->get('checkin_start')))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
        'end' => $current->add($this->durationService->getDateIntervalFromDurationString($this->config->get('checkin_start')))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      ];

      // Clone the dates so we don't manipulate the originals.
      $previous = DrupalDateTime::createFromTimestamp($lastRun);
      $previous->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
      $current = DrupalDateTime::createFromTimestamp($now);
      $current->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));

      // Range start date = last run add checkin period start duration.
      $endRange = [
        'start' => $previous->sub($this->durationService->getDateIntervalFromDurationString($this->config->get('checkin_end')))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
        'end' => $current->sub($this->durationService->getDateIntervalFromDurationString($this->config->get('checkin_end')))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      ];

      // Find all event nodes whose checkin periods have started or ended
      // since the last time we checked.
      $query = $this->entityTypeManager
        ->getStorage('node')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'event')
        ->condition('status', '1');

      $group = $query->orConditionGroup()
        ->condition('field_date_time.value', $startRange, 'BETWEEN')
        ->condition('field_date_time.end_value', $endRange, 'BETWEEN');

      $events = $query->condition($group)
        ->execute();

      if (!empty($events)) {
        // Generate cache tags from nids.
        $cacheTags = array_map(function ($nid) {
          return 'node:' . $nid;
        }, $events);

        $this->cacheTagsInvalidator->invalidateTags($cacheTags);

        // Log the invalidated cache tags.
        $logger = $this->getLogger('intercept_event');
        $logger->log(RfcLogLevel::INFO, $this->t('Cache tags invalidated due to change in check-in period status: @events', [
          '@events' => implode(', ', $cacheTags),
        ]));
      }

      $this->state->set(self::STATE_ID, $now);
    }

    return array_values($events);
  }

  /**
   * {@inheritDoc}
   */
  public function resetInvalidationPeriod() {
    $this->state->set(self::STATE_ID, NULL);
  }

}
