<?php

namespace Drupal\office_hours;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;

/**
 * Defines some functions for use in caching.
 *
 * Unfortunately, max-age does not work for anonymous users
 * and the Drupal core Page Cache module.
 * For example, see these issues:
 * https://www.drupal.org/docs/drupal-apis/cache-api/cache-tags
 * https://www.drupal.org/docs/drupal-apis/cache-api/cache-max-age
 * https://www.drupal.org/docs/drupal-apis/cache-api/cache-max-age#s-limitations-of-max-age
 * https://www.drupal.org/docs/8/api/responses/cacheableresponseinterface
 * https://www.drupal.org/project/drupal/issues/2835068
 * https://www.drupal.org/project/drupal/issues/3304772
 * https://www.drupal.org/project/cache_control_override/issues/2962699
 * https://www.drupal.org/project/custom_cache
 * That is why a hook_cron is implemented.
 */
class OfficeHoursCacheHelper implements CacheableDependencyInterface {

  /**
   * Indicates that the item should never be removed unless explicitly deleted.
   *
   * Can have the following values:
   * 0: do nothing;
   * 1: invalidate all Entities with a status formatter (with generic tag);
   * 2: invalidateTagsUsingRenderCache() using entity-specific tag;
   * 3: invalidateTagsUsingStateService();
   */
  const INVALIDATE_MODE = 2;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The Office Hours formatter settings.
   *
   * @var array
   */
  protected $formatterSettings = [];

  /**
   * An Office Hours ItemList.
   *
   * @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface
   */
  protected $items = NULL;

  /**
   * An array of fromatted office_hours, according to formatter.
   *
   * @var array
   */
  protected $officeHours = [];

  /**
   * Constructs a CacheHelper object.
   */
  public function __construct(array $formatter_settings, OfficeHoursItemListInterface $items, array $office_hours) {
    $this->formatterSettings = $formatter_settings;
    $this->items = $items;
    $this->officeHours = $office_hours;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $entity = $this->items->getEntity();
    if (!$entity) {
      return [];
    }
    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();

    // Add a tag for the Entity itself,
    // and a tag for the hook_cron invalidation for anonymous users.
    switch (self::INVALIDATE_MODE) {
      case 0:
      case 3:
        return [
          "$entity_type:$entity_id",
        ];

      case 1:
        return [
          "$entity_type:$entity_id",
          "office_hours_status",
        ];

      case 2:
        return [
          "$entity_type:$entity_id",
          "office_hours_status:$entity_type:$entity_id",
        ];

    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // @see https://www.drupal.org/docs/drupal-apis/cache-api/cache-max-age
    // @todo Add CacheMaxAge when entity has Exception days in/out of horizon.
    // If there are no open days, cache forever.
    if ($this->items->isEmpty()) {
      return Cache::PERMANENT;
    }

    $date = new DrupalDateTime('now');
    $today = $date->format('w');
    $now = $date->format('Hi');
    $seconds = $date->format('s');
    $next_time = '0000';
    $add_days = 0;

    $formatter_settings = $this->formatterSettings;
    $cache_setting = $formatter_settings['show_closed'];
    switch ($cache_setting) {
      case 'all':
      case 'open':
      case 'none':
        // These caches never expire, since they are always correct.
        return Cache::PERMANENT;

      case 'current':
        // Cache expires at midnight. (Is this timezone proof?)
        /*
        $next_time = '0000';
        $add_days = 1;
        break;
         */
        return strtotime('tomorrow midnight') - strtotime('now');

      case 'next':
        // Get the first (and only) day of the list.
        // Make sure we only receive 1 day, only to calculate the cache.
        $office_hours = $this->officeHours;
        $next_day = array_shift($office_hours);
        if (!$next_day) {
          return Cache::PERMANENT;
        }

        // Get the difference in hours/minutes
        // between 'now' and next open/closing time.
        $first_time_slot_found = FALSE;
        foreach ($next_day['slots'] as $slot) {
          $start = $slot['start'];
          $end = $slot['end'];

          if ($next_day['day'] != $today) {
            // We will open tomorrow or later.
            $next_time = $start;
            $seven = OfficeHoursDateHelper::DAYS_PER_WEEK;
            $add_days = ($next_day['day'] - $today + $seven) % $seven;
            break;
          }
          elseif ($start > $now) {
            // We will open later today.
            $next_time = $start;
            $add_days = 0;
            break;
          }
          elseif (($start > $end)
            // We are open until after midnight.
            || ($start == $end)
            // We are open 24hrs per day.
            || (($start < $end) && ($end > $now))
            // We are open, normal times.
          ) {
            $next_time = $end;
            // Add 1 day if open until after midnight.
            $add_days = ($start < $end) ? 0 : 1;
            break;
          }
          else {
            // We were open today. Take the first slot of the day.
            if (!$first_time_slot_found) {
              $first_time_slot_found = TRUE;
              $next_time = $start;
              $add_days = OfficeHoursDateHelper::DAYS_PER_WEEK;
            }
            // Do not stop, but continue. A new slot might come along.
            continue;
          }
        }
        break;

      default:
        // We should have covered all options above.
        return Cache::PERMANENT;
    }

    // Set to 0 to avoid php error if time field is not set.
    $next_time = is_numeric($next_time) ? $next_time : '0000';
    // Calculate the remaining cache time.
    $time_left = $add_days * 24 * 3600;
    $time_left += ((int) substr($next_time, 0, 2) - (int) substr($now, 0, 2)) * 3600;
    $time_left += ((int) substr($next_time, 2, 2) - (int) substr($now, 2, 2)) * 60;
    // Correct for the current minute.
    $time_left -= $seconds;

    return $time_left;
  }

  /**
   * Clear cache for Anonymous users.
   *
   * This is needed when the Formatter reports a 'status' (open/closed)
   * or when an Exception date is listed in the future.
   * Standard render cach is not refreshed for Anonymous users.
   *
   * Below are the options outlines. We will use option 2.
   */
  public static function doCron() {
    $option = self::INVALIDATE_MODE;

    switch ($option) {
      case 0:
        // Do nothing.
        return;

      case 1:
        // Option 1: Just invalidate all Entities with a status formatter.
        self::invalidateTags();
        return;

      case 2:
        // Option 2: Mimicking DatabaseBackend~getMultiple(&$cids).
        self::invalidateTagsUsingRenderCache();
        return;

      case 3:
        self::invalidateTagsUsingStateService();
        return;
    }

  }

  /**
   * Option 1: Just invalidate all Entities with a status formatter.
   *
   * A tag is added in OfficeHoursFormatterBase~addCacheMaxAge().
   * If no status formatter is used, nothing is invalidated.
   *
   * @see https://www.drupal.org/docs/drupal-apis/cache-api/cache-tags#s-invalidating
   * "Tagged cache items are invalidated via their tags, 
   * using cache_tags.invalidator:invalidateTags()
   * (or, when you cannot inject the cache_tags.invalidator service:
   * Cache::invalidateTags()), which accepts a set of cache tags (string[])."
   */
  private static function invalidateTags() {
    Cache::invalidateTags(['office_hours_status']);
  }

  /**
   * Option 2: Mimicking DatabaseBackend~getMultiple(&$cids).
   *
   * Fetching $this->connection->query('
   *   SELECT [cid], [expire], [tags]
   *   FROM {' . $this->connection->escapeTable($this->bin)  . '}
   *   WHERE [expire] less then REQUEST_TIME; .
   */
  private static function invalidateTagsUsingRenderCache() {
    $bin = 'cache_render';
    // $cache = \Drupal::cache('render');
    $connection = Database::getConnection();

    // Get all tags with pattern 'office_hours_status:$entity_type:$entity_id'.
    $result = [];
    try {
      $now = strtotime('now');
      $result = $connection->select($bin)
        // Some fields are only needed for debugging.
        // ->fields($bin, ['cid', 'expire', 'tags'])
        ->fields($bin, ['tags'])
        // This first condition is superfluous, but may be faster.
        ->condition('expire', '-1', '<>')
        ->condition('expire', $now, '<')
        ->condition('tags', '%office_hours_status%', 'LIKE')
        ->execute()
        // ->fetchAllAssoc('cid')
        ->fetchAllAssoc('tags');
    }
    catch (\Exception $e) {
      throw $e;
    }

    foreach ($result as $tags => $data) {
      $offset = strpos($tags, 'office_hours_status');
      $tag = strstr(substr($tags, $offset), ' ', TRUE);
      Cache::invalidateTags([$tag]);
    }
  }

  /**
   * Option 3: Invalidate only the entities with invalid status.
   *
   * @see https://www.drupal.org/project/office_hours/issues/3312511#comment-14814745
   * This however will miss if entity has been open, closed and is open again.
   */
  private static function invalidateTagsUsingStateService() {
    $state_service = \Drupal::state();

    // Get a list of all fields.
    $fieldMap = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('office_hours');
    // Process all office_hours entities.
    foreach ($fieldMap as $entity_type => $field_info) {
      foreach ($field_info as $field_name => $field_details) {
        // @todo Filter on Formatter settings in ViewMode.
        foreach ($field_details['bundles'] as $bundle) {
          $entityDisplayRepository = \Drupal::service('entity_display.repository');
          $view_modes = $entityDisplayRepository->getViewModeOptionsByBundle($entity_type, $bundle);
          foreach (array_keys($view_modes) as $view_mode) {
            /** @var \Drupal\office_hours\Plugin\Field\FieldFormatter\OfficeHoursFormatterBase $formatter */
            $formatter = \Drupal::service('entity_type.manager')
              ->getStorage('entity_view_display')
              ->load($entity_type . '.' . $bundle . '.' . $view_mode)
              ->getRenderer($field_name);
            if ($formatter) {
              $cache_needed = $formatter->getSetting('cache');

              // Find all office_hours entities and check the current status.
              $entity_ids = \Drupal::entityQuery($entity_type)
                ->condition('type', $bundle)
                ->condition('status', 1)
                ->accessCheck(FALSE)
                ->execute();
              foreach ($entity_ids as $entity_id) {
                $entity = \Drupal::entityTypeManager()
                  ->getStorage($entity_type)
                  ->load($entity_id);

                // Continue when the formatter needs caching
                // or when entity has Exception days
                // (since they can getin the past, or come into horizon).
                if ($cache_needed ||
                  $entity->$field_name->hasExceptionDays()) {

                  // The following is not waterproof.
                  // The entity can be open AGAIN, when an anonymous user
                  // views the data, or cron runs.
                  // We need Option 2, reading cache_render table.
                  $state_service_key = "office_hours_status:$entity_type:$entity_id:$field_name";
                  // Store the is_open boolean as an integer.
                  $is_open = (int) $entity->$field_name->isOpen();
                  $was_open = $state_service->get($state_service_key, -1);
                  if ($was_open != $is_open) {
                    // When status changes, store it and invalidate the cache.
                    $state_service->set($state_service_key, $is_open);

                    // @todo Invalidate Render Cache, but how to know $cid?
                    $cid = $entity->getEntityTypeId() . ':' . $entity_id;
                    Cache::invalidateTags([$cid]);
                  }
                }
              }
            }
          }
        }
      }
    }
  }

}
