<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\PluginSettingsBase;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\OfficeHoursFormatterTrait;
use Drupal\office_hours\OfficeHoursSeason;

/**
 * Represents an Office hours field.
 */
class OfficeHoursItemList extends FieldItemList implements OfficeHoursItemListInterface {

  use OfficeHoursFormatterTrait {
    getRows as getFieldRows;
  }

  /**
   * A list of seasons, for this ItemList.
   *
   * @var \Drupal\office_hours\OfficeHoursSeason[]
   */
  private $seasons = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
  }

  /**
   * Helper for creating a list item object of several class types.
   *
   * {@inheritdoc}
   */
  protected function createItem($offset = 0, $value = NULL) {
    $day = $value['day'] ?? NULL;

    switch (TRUE) {
      case is_null($day):
        // Empty Item from List Widget (or added item via AddMore button?).
      case OfficeHoursDateHelper::isWeekDay($day):
        // Add Weekday Item.
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

    // Add special Item (season, exception), using quasi Factory pattern.
    // Copied from FieldTypePluginManager->createInstance().
    $field_definition = $this->getFieldDefinition($field_type);
    $configuration = [
      'data_definition' => $field_definition->getItemDefinition(),
      'name' => $this->getName(),
      'parent' => $this,
    ];
    $item = $this->typedDataManager->createInstance("field_item:$field_type", $configuration);
    $item->setValue($value);

    // Pass item to parent, where it appears amongst Weekdays.
    return $item;
  }

  /**
   * {@inheritdoc}
   *
   * Make sure all (exception) days are in correct sort order,
   * independent of database order, so formatter is correct.
   * (Widget or other sources may store exceptions day in other sort order).
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);

    // Sort the database values by day number.
    // @todo In Formatter: itemList::getRows() or Widget: itemList::setValue().
    // $this->sort();
  }

  /**
   * Sorts the items on date, but leaves hours unsorted, as maintained by user.
   *
   * {@inheritdoc}
   */
  public function sort() {
    uasort($this->list, [OfficeHoursItem::class, 'sort']);
  }

  /**
   * {@inheritdoc}
   */
  public function getRows(array $settings, array $field_settings, array $third_party_settings, int $time = 0, PluginSettingsBase $plugin = NULL) {
    return $this->getFieldRows($this->getValue(), $settings, $field_settings, $third_party_settings, $time, $plugin);
  }

  /**
   * {@inheritdoc}
   *
   * Create a custom field definition for office_hours_* items.
   *
   * Ideally, we just use the basic 'office_hours' field definition.
   * However, this causes either:
   * 1- to display the 'technical' widgets (exception, season) in Field UI,
   *   (with annotation: field_types = {"office_hours"}), or
   * 2- to have the widget refused by WidgetPluginManager~getInstance().
   *   (with annotation: no_ui = TRUE),
   *   FieldType has annotation 'no_ui', FieldWidget and FieldFormatter haven't.
   * So, the Exceptions and Season widgets are now declared for their
   * specific type.
   *
   * @param string $field_type
   *   The field type, 'office_hours' by default.
   *   If set otherwise a new FieldDefinition is returned.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition. BaseField, not ConfigField,
   *   because easier to construct.
   */
  public function getFieldDefinition($field_type = '') {

    switch ($field_type) {
      case '':
      case 'office_hours':
        return parent::getFieldDefinition();

      case 'office_hours_exceptions':
      case 'office_hours_season_header':
      case 'office_hours_season_item':
      default:
        $field_definition = FieldConfig::create([
          'entity_type' => $this->getEntity()->getEntityTypeId(),
          'bundle' => $this->getEntity()->bundle(),
          'field_name' => $this->getName(),
          'field_type' => $field_type,
        ]);
        /*
        $field_definition = BaseFieldDefinition::create($field_type)
        ->setName($this->fieldDefinition->getName())
        ->setSettings($this->fieldDefinition->getSettings());
         */
    }

    return $field_definition;
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
      $season_id = $season_max + OfficeHoursSeason::SEASON_ID_FACTOR;
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
      if ($season_id == OfficeHoursItem::EXCEPTION_DAY && $item->isExceptionDay()) {
        return TRUE;
      }
      if ($season_id == 0 && $item->isExceptionDay()) {
        return FALSE;
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
    foreach($items as $item) {
      $exception_days[$item->day] = true;
    }
    return count($exception_days);
  }

  /**
   * {@inheritdoc}
   */
  public function isOpen(int $time = 0): bool {
    $current_item = $this->getCurrentSlot($time);
    return (bool) $current_item;
  }

}
