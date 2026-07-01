<?php

namespace Drupal\date_popup\Hook;

use Drupal\date_popup\DatePopupSearchApi;
use Drupal\date_popup\DatetimePopup;
use Drupal\date_popup\DatePopup;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for date_popup.
 */
class DatePopupViewsHooks {

  /**
   * Implements hook_views_plugins_filter_alter().
   */
  #[Hook('views_plugins_filter_alter')]
  public static function viewsPluginsFilterAlter(&$info) {
    $info['date']['class'] = DatePopup::class;
    if (isset($info['datetime'])) {
      $info['datetime']['class'] = DatetimePopup::class;
    }
    if (isset($info['search_api_date'])) {
      $info['search_api_date']['class'] = DatePopupSearchApi::class;
    }
  }

}
