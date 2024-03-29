<?php

namespace Drupal\office_hours;

use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;

/**
 * Generates a sorted ['date' => [$item]] list.
 */
class OfficeHoursItemListSorter {

  /**
   * An integer representing the next open day.
   *
   * @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList
   */
  protected $itemList = NULL;

  /**
   * A list of sorted items, keyed by request time and item date.
   *
   * @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem[][]
   */
  protected $sortedItemList = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(OfficeHoursItemListInterface $items) {
    $this->itemList = $items;
  }

  /**
   * Returns a sorted list of items, keyed by Date[day_index].
   */
  public function getSortedItemList(int $time): array {

    // Read the cache.
    if (isset($this->sortedItemList[$time])) {
      return $this->sortedItemList[$time];
    }

    $date = OfficeHoursDateHelper::format($time, 'Y-m-d');
    // Start with last week, to get complete current week. Last for 2 weeks.
    $past = 8;
    $horizon = 14;
    $start_date = strtotime($date . " -$past day");
    $end_date = (strtotime($date . " +$horizon day"));
    $seasons = $this->itemList->getSeasons(TRUE, FALSE, 'ascending', $start_date, $end_date);

    // Build a list of open next days. Then pick the first day.
    // This is needed instead of picking the open day directly,
    // since while processing the list, seasons might override weekdays,
    // and (closed) exception might override weekdays or season days.
    // At the end, we pick the first day of the list.
    $sorted_list = [];

    // Assume that all days are ordered on key = day number.
    $iterator = $this->itemList->getIterator();
    for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
      $item = $iterator->current();

      // Do not add item in past season to sorted list.
      if (!isset($seasons[$item->getSeasonId()])) {
        continue;
      }

      // Process each slot.
      // Per determined date, unset closed days, or add an array of slots.
      // Exclude dates in the far past and the far future.
      switch (TRUE) {
        case $item->isSeasonHeader():
          // Must be parsed before $item->isSeasonDay().
          // But is processed after the days of the each season.

          $season = $item->getSeason();
          $season_startdate = max($start_date, $season->getFromDate());
          // For future seasons only, fill the upcoming empty dates,
          // removing weekdays and earlier season days.
          // The open days are already set by the other SeasonDays.
          for ($i = 0; $i < ($past + $horizon); $i++) {
            $slot_date = strtotime("+$i day", $season_startdate);
            if ($slot_date <= $season->getToDate()) {
              // Clear previously set date from other seasons.
              $set_slot = $sorted_list[$slot_date][0] ?? NULL;
              if (!$set_slot) {
                $this->removeItem($sorted_list, $slot_date);
              }
              elseif ($set_slot->getSeasonId() !== $item->getSeasonId()) {
                $this->removeItem($sorted_list, $slot_date);
              }
            }
          }
          break;

        case $item->isSeasonDay():
        case $item->isWeekDay():
          // Calculate 'next weekday after (day before) start of season'.
          $season = $item->getSeason();
          $slot_weekday = $item->getWeekday();
          $weekday_label = OfficeHoursDateHelper::weekDaysByFormat('long_untranslated', $slot_weekday);
          // Use '-1 day' to be able to use 'next Monday' later on.
          $season_startdate = strtotime("-1 day", max($start_date, $season->getFromDate()));
          $slot_date = strtotime("next $weekday_label", $season_startdate);
          $this->addItem($sorted_list, $item, $slot_date);
          $this->addItem($sorted_list, $item, strtotime('+7 days', $slot_date));
          $this->addItem($sorted_list, $item, strtotime('+14 days', $slot_date));
          $this->addItem($sorted_list, $item, strtotime('+21 days', $slot_date));
          break;

        case $item->isExceptionDay():
          $slot_date = $item->day;
          $this->addItem($sorted_list, $item, $slot_date);
          break;
      }

    }

    // Sort items on date.
    ksort($sorted_list);
    $this->sortedItemList[$time] = $sorted_list;

    return $this->sortedItemList[$time];
  }

  /**
   * Adds an item to the list, or closes a date.
   *
   * @param mixed $sorted_list
   *   A reference to the list.
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem|null $item
   *   The time slot to be added.
   * @param mixed $slot_date
   *   The date of the slot to work with.
   */
  protected function addItem(array &$sorted_list, OfficeHoursItem|NULL $item, int $slot_date) {

    if ($item === NULL) {
      // No time slot given, clear the date.
      $sorted_list[$slot_date] = [];
    }
    else {
      if ($item->isSeasonDay()
      && !$item->getSeason()->isInRange($slot_date)) {
        // Do not add to list. Outside range.
      }
      elseif ($item->isEmpty()) {
        // Clear the date. Closed all day.
        // Assume that no other items exist for this day.
        $sorted_list[$slot_date] = [];
      }
      else {
        // Clear the date if this is a new season day or exception date.
        $set_slot = $sorted_list[$slot_date][0] ?? NULL;
        if ($set_slot && $set_slot->day !== $item->day) {
          $this->removeItem($sorted_list, $slot_date);
        }
        // A valid time slot, add to the date.
        $sorted_list[$slot_date][] = $item;
      }
    }
  }

  /**
   * Removes an item from the list.
   *
   * @param mixed $sorted_list
   *   A reference to the list.
   * @param mixed $slot_date
   *   The date of the slot to work with.
   */
  protected function removeItem(array &$sorted_list, int $slot_date) {
    $this->addItem($sorted_list, NULL, $slot_date);
  }

  /**
   * Gets NextDay.
   *
   * @param int $time
   *   A timestamp, representing a day-date.
   *
   * @return array
   *   The date's time slots: [date => [day_index => $item].
   */
  public function getNextDay(int $time): array {
    $sorted_list = $this->getSortedItemList($time);

    $today = OfficeHoursDateHelper::today($time);
    $yesterday = strtotime('-1 day', $today);

    // Pick the next/current open day number.
    foreach ($sorted_list as $date => $day) {
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
      foreach ($day as $day_index => $item) {
        if ($item) {
          if ($date == $yesterday || $date == $today) {
            $status = $item->getStatus($time);
            if (in_array($status, [OfficeHoursItem::IS_OPEN, OfficeHoursItem::WILL_OPEN])) {
              // We are open or will open later today.
              return [$date => $day];
            }
          }
          elseif ($date > $today) {
            return [$date => $day];
          }
        }
      }
    }
    return [];
  }

}
