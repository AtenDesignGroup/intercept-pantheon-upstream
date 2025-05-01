<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\PluginSettingsBase;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\OfficeHoursItemListFormatter;
use Drupal\office_hours\OfficeHoursSeason;

/**
 * Represents an Office hours field.
 */
class OfficeHoursItemList extends FieldItemList implements OfficeHoursItemListInterface {

  /**
   * An object that formats the office_hours for viewing.
   *
   * This is set after assigning a value to $items, otherwise empty.
   *
   * @var \Drupal\office_hours\OfficeHoursItemListFormatter
   */
  private $formatter = NULL;

  /**
   * A list of seasons, for this ItemList.
   *
   * @var \Drupal\office_hours\OfficeHoursSeason[]
   */
  private $seasons = NULL;

  /**
   * Helper for creating a list item object of several class types.
   *
   * {@inheritdoc}
   */
  public function createItem($offset = 0, $value = NULL) {
    $day = $value['day'] ?? NULL;

    static $pluginManager = NULL;
    // Avoid PHP8.2 Fatal error: Constant expression contains invalid operations
    $plugin_type = 'field_type';
    $pluginManager ??= \Drupal::service("plugin.manager.field.$plugin_type");

    switch (TRUE) {
      case is_null($day):
        // Empty Item from List Widget (or added item via AddMore button?).
      case OfficeHoursDateHelper::isWeekDay($day):
        // Add Weekday Item.
        $field_type = 'office_hours';
        $item = parent::createItem($offset, $value);
        return $item;

      case OfficeHoursDateHelper::isSeasonHeader($day):
        $field_type = 'office_hours_season_header';
        break;

      case OfficeHoursDateHelper::isSeasonDay($day):
        // Add (seasonal) Weekday item (including season header).
        $field_type = 'office_hours_season_item';
        break;

      case OfficeHoursDateHelper::isExceptionDay($day):
        // Add Exception Item.
        $field_type = 'office_hours_exceptions';
        break;
    }

    // Create special Item (season, exception).
    $plugin_definition = $pluginManager->getDefinition($plugin_id = $field_type);
    $class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    // Copied from FieldTypePluginManager->createInstance().
    $data_definition = $this->getItemDefinition();
    $item = $class::createInstance($data_definition, $this->getName(), $this);
    $item->setTypedDataManager($this->typedDataManager);
    $item->setValue($value);

    // Pass item to parent, where it appears amongst Weekdays.
    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);

    // Make sure all (exception) days are in correct sort order,
    // independent of database order, so formatter is correct.
    // (Widget or other sources may store exceptions day in other sort order).
    // Sort the database values by day number.
    // @todo In Formatter: itemList::getRows() or Widget: itemList::setValue().
    // $this->sort();

    // Create the formatter AFTER setting the value.
    $this->formatter ??= new OfficeHoursItemListFormatter($this);
  }

  /**
   * Sorts the items on date, but leaves hours unsorted, as maintained by user.
   *
   * {@inheritdoc}
   */
  public function sort() {
    usort($this->list, [OfficeHoursItem::class, 'sort']);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRows(array $settings, array $field_settings, array $third_party_settings, int $time = 0, ?PluginSettingsBase $plugin = NULL) {
    // The formatter is only set when entity has values.
    // But still render the field, due to setting 'display even when empty'.
    $this->formatter ??= new OfficeHoursItemListFormatter($this);
    return $this->formatter->getRows($settings, $field_settings, $third_party_settings, $time, $plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getSeasons($add_weekdays_as_season = FALSE, $add_new_season = FALSE, $sort = '', $from = 0, $to = 0) {
    $season_max = 0;

    // Use static, to avoid recursive calling of getSeasons()/getSeason().
    if (!isset($this->seasons)) {
      $seasons = [];
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
      foreach ($this->list as $item) {
        if ($item->isSeasonHeader()) {
          $season_id = $item->getSeasonId();
          $seasons[$season_id] = new OfficeHoursSeason($item);
          // $season_max is needed later on.
          $season_max = max($season_max, $season_id);
        }
      }
      $this->seasons = $seasons;
    }
    $seasons = $this->seasons;

    // Remove past seasons.
    /** @var \Drupal\office_hours\OfficeHoursSeason[] $seasons */
    foreach ($seasons as $id => $season) {
      if (!$season->isInRange($from, $to)) {
        unset($seasons[$id]);
      }
    }

    // Sort seasons by start date.
    if (!empty($sort)) {
      uasort($seasons, [OfficeHoursSeason::class, 'sort']);
      if ($sort == 'descending') {
        array_reverse($seasons, TRUE);
      }
    }

    // Add Weekdays at top of sorted list.
    if ($add_weekdays_as_season) {
      $season_id = 0;
      $seasons = [$season_id => new OfficeHoursSeason($season_id)] + $seasons;
    }

    // Add New season at bottom of sorted list.
    if ($add_new_season) {
      // Add 'New season', until we have a proper 'Add season' button.
      $season_id = $season_max + OfficeHoursDateHelper::SEASON_ID_FACTOR;
      $seasons[$season_id] = new OfficeHoursSeason($season_id);
    }

    return $seasons;
  }

  /**
   * {@inheritdoc}
   */
  public function getExceptionItems() {
    $list = clone $this;

    $list->filter(function ($item) {
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
      if ($item->isExceptionDay()) {
        return TRUE;
      }
    });

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeasonItems(int $season_id) {
    $list = clone $this;

    $list->filter(function ($item) use ($season_id) {
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
      if ($item->isExceptionDay()) {
        if (OfficeHoursDateHelper::isExceptionHeader($season_id)) {
          return TRUE;
        }
        if ($season_id == 0) {
          return FALSE;
        }
        return $season_id == $item->getSeasonId();
      }
      return $season_id == $item->getSeasonId();
    });

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function countExceptionDays() {
    $items = $this->getExceptionItems();
    $exception_days = [];
    foreach ($items as $item) {
      $exception_days[$item->day] = TRUE;
    }
    return count($exception_days);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(int $time = 0): int {
    return $this->{'status'} ?? OfficeHoursStatus::NEVER;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentSlot(int $time = 0) {
    $this->formatter ??= new OfficeHoursItemListFormatter($this);
    return $this->formatter->getCurrentSlot($time);
  }

  /**
   * {@inheritdoc}
   */
  public function getNextDay(int $time = 0) {
    $this->formatter ??= new OfficeHoursItemListFormatter($this);
    return $this->formatter->getNextDay($time);
  }

  /**
   * {@inheritdoc}
   */
  public function isOpen(int $time = 0): bool {
    $this->formatter ??= new OfficeHoursItemListFormatter($this);
    $current_item = $this->formatter->getCurrentSlot($time);
    return (bool) $current_item;
  }

  /**
   * Instantiate the widget/formatter object from the stored properties.
   *
   * @param string $plugin_type
   *   The plugin type to retrieve: 'widget' or 'formatter'.
   * @param string $plugin_id
   *   The plugin id.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The plugin settings.
   *
   * @return \Drupal\Core\Field\PluginSettingsInterface|null
   *   A widget or formatter plugin or NULL if the field does not exist.
   */
  private function getPlugin($plugin_type, $plugin_id, $view_mode, FieldDefinitionInterface $field_definition, array $settings) {
    static $plugins;

    $id = $field_definition->id();
    if (!isset($plugins[$plugin_type][$plugin_id][$view_mode][$id])) {
      // @todo Keep aligned between WebformOfficeHours and ~Widget.
      $pluginManager = \Drupal::service("plugin.manager.field.$plugin_type");
      $configuration = [
        'type' => $plugin_id,
        'field_definition' => $field_definition,
        'view_mode' => $view_mode,
        'label' => '',
        // No need to prepare, defaults have been merged in setComponent().
        'prepare' => FALSE,
        'settings' => $settings,
        'third_party_settings' => [],
      ];
      $plugins[$plugin_type][$plugin_id][$view_mode][$id] = $pluginManager->createInstance($plugin_id, $configuration) ?? NULL;
    }
    return $plugins[$plugin_type][$plugin_id][$view_mode][$id];
  }

  /**
   * @see $this->getPlugin().
   */
  public function getFormatter(string $plugin_id, $view_mode, array $settings) {
    return $this->getPlugin(
      'formatter',
      $plugin_id,
      $view_mode,
      $this->getFieldDefinition(),
      $settings
    );
  }

  /**
   * @see $this->getPlugin().
   */
  public function getWidget(string $plugin_id, array $settings) {
    return $this->getPlugin(
      'widget',
      $plugin_id,
      '',
      $this->getFieldDefinition(),
      $settings
    );
  }

}
