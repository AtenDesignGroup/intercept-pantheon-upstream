<?php

namespace Drupal\charts\Plugin\chart\Library;

/**
 * Implement a method to retrieve the "site_default" library.
 */
trait LibraryRetrieverTrait {

  /**
   * Get the library.
   *
   * @param string $library
   *   The library.
   *
   * @return string
   *   The library.
   */
  private function getLibrary(string $library): string {
    $definitions = $this->chartsManager->getDefinitions();
    if (!$library || $library === 'site_default') {
      $charts_settings = $this->configFactory->get('charts.settings');
      $default_settings_library = $charts_settings->get('charts_default_settings.library');
      $library = !empty($default_settings_library) ? $default_settings_library : key($definitions);
    }
    elseif (!isset($definitions[$library])) {
      $library = key($definitions);
    }

    return $library;
  }

}
