<?php

namespace Drupal\office_hours\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Processes a input array of office hours to the correct format for the field.
 *
 * The concat CSVOfficeHours is used to generate a well formed array of
 * opening hours for use in the Office hours field.
 *
 * Available configuration keys:
 * - slots_per_day: (optional) The time slots per day, defaults to 1.
 * - delimiter: (optional) Your time slots should be in the following format:
 *     00:00 - 00:00: Two times separated by a character. With this
 *     option you can set this delimiter, defaults to '-'.
 *
 * Examples:
 *
 * @code
 * process:
 *   new_office_hours_field:
 *     plugin: csv_office_hours
 *     slots_per_day: 2
 *     delimiter: '-'
 *     source:
 *        - 'Sunday 1'
 *        - 'Sunday 2'
 *        - 'Monday 1'
 *        - 'Monday 2'
 *        - 'Tuesday 1'
 *        - 'Tuesday 2'
 *        - 'Wednesday 1'
 *        - 'Wednesday 2'
 *        - 'Thursday 1'
 *        - 'Thursday 2'
 *        - 'Friday 1'
 *        - 'Friday 2'
 *        - 'Saturday 1'
 *        - 'Saturday 2'
 * @endcode
 *
 * This will import to a field with two time slots set per day.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "csv_office_hours"
 * )
 */
class CSVOfficeHoursField extends OfficeHoursField {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException(sprintf('%s is not an array', var_export($value, TRUE)));
    }

    $slots_per_day = isset($this->configuration['slots_per_day']) ? $this->configuration['slots_per_day'] : 1;
    $delimiter = isset($this->configuration['delimiter']) ? $this->configuration['delimiter'] : '-';

    if (count($value) !== ($slots_per_day * 7)) {
      throw new MigrateException(sprintf('%s does not have the correct size', var_export($value, TRUE)));
    }

    $office_hours = [];
    for ($i = 0; $i < count($value); $i++) {
      $time = explode($delimiter, trim($value[$i]));

      $office_hours[] = [
        'day' => floor($i / $slots_per_day),
        'starthours' => str_replace(':', '', $time[0]),
        'endhours' => str_replace(':', '', $time[1]),
      ];
    }

    return $office_hours;
  }

}
