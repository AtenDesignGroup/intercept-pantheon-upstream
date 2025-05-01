<?php

namespace Drupal\office_hours\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList;

/**
 * Contains Field and Help hooks.
 *
 * Class is declared as a service in services.yml file.
 *
 * @see https://drupalize.me/blog/drupal-111-adds-hooks-classes-history-how-and-tutorials-weve-updated
 */
class OfficeHoursHooks {

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

    $replacements = [];

    if (empty($data['field_property'])) {
      return $replacements;
    }
    if (!isset($data['field_name']) || !isset($data[$data['field_name']])) {
      return $replacements;
    }

    foreach ($tokens as $name => $original) {
      $list = $data[$data['field_name']];
      if (!$list instanceof OfficeHoursItemList) {
        continue;
      }

      // @todo Update patch once multiple values support added to Token module:
      // @see https://www.drupal.org/project/token/issues/3115486
      $parts = explode(':', $name);
      $resulting_items = [];
      if (is_numeric($parts[0])) {
        if (is_null($list->get($parts[0]))) {
          return $replacements;
        }
        $list = [$list->get($parts[0])];
        $property = $parts[1];
      }
      else {
        $property = $parts[0];
      }
      foreach ($list as $item) {
        /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
        switch ($property) {
          case 'day':
            $day = $item->{$property};
            $resulting_items[] = OfficeHoursDateHelper::weekDaysByFormat('long', $day);
            break;

          case 'day-untranslated':
            $day = $item->day;
            $resulting_items[] = OfficeHoursDateHelper::weekDaysByFormat('long_untranslated', $day);
            break;

          case 'starthours':
          case 'endhours':
            $resulting_items[] = OfficeHoursDateHelper::format($item->{$property}, "H:i:s", FALSE);
            break;
        }
      }
      $replacements[$original] = implode(',', $resulting_items);
    }
    return $replacements;
  }

}
