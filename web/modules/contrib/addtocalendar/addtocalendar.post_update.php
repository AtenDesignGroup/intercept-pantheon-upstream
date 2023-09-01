<?php

/**
 * @file
 * Post-update functions for addtocalendar module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityDisplayInterface;

/**
 * #3092765: Re-save third party settings with new schema.
 */
function addtocalendar_post_update_third_party_settings(&$sandbox) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $formatter_callback = function (EntityDisplayInterface $display) {
    foreach ($display->getComponents() as $field_name => $component) {
      if (isset($component['third_party_settings']['addtocalendar'])) {
        // Note that normally the pattern is to use a $needs_save variable.
        // However, the ConfigEntityUpdater::update() method ends up saving
        // with "trusted data" such that the schema is not used to cast values.
        // Using the schema to cast values is all we want to do here!
        $display->save();
        break;
      }
    }
    return NULL;
  };

  $config_entity_updater->update($sandbox, 'entity_view_display', $formatter_callback);
}
