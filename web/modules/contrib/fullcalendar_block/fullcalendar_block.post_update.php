<?php

/**
 * @file
 * Post update hooks for the Fullcalendar Block module.
 */

use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Update the Fullcalendar block configuration schema.
 */
function fullcalendar_block_post_update_update_block_data(&$sandbox = NULL) {
  /** @var \Drupal\Core\Config\Entity\ConfigEntityUpdater $config_entity_updater */
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (BlockInterface $block) {
    if ($block->getPluginId() === 'fullcalendar_block') {
      $settings = $block->get('settings');
      if ($settings && !empty($settings['advanced'])) {
        $settings['advanced'] = trim(str_replace(["\r\n", "\r"], "\n", $settings['advanced']));
        $block->set('settings', $settings);
      }
      // Resave the fullcalendar block instance.
      $block->save();
    }

    // Return false since we're updating the block ourselves.
    return FALSE;
  };

  $config_entity_updater->update($sandbox, 'block', $callback);
}

/**
 * Add new configurations to the default Fullcalendar blocks.
 */
function fullcalendar_block_post_update_add_new_settings(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('block.block.') as $block_config_name) {
    $block = $config_factory->getEditable($block_config_name);
    if ($block->get('plugin') === 'fullcalendar_block') {
      $settings = $block->get('settings');
      $settings += [
        'advanced_drupal' => '',
        'plugins' => [],
      ];
      $block->set('settings', $settings);
      // Cast values properly using the config schema.
      $block->save(FALSE);
    }
  }
}

/**
 * Add default 'use_token' configuration to existing Fullcalendar blocks.
 */
function fullcalendar_block_post_update_add_default_token_settings(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('block.block.') as $block_config_name) {
    $block = $config_factory->getEditable($block_config_name);
    if ($block->get('plugin') === 'fullcalendar_block') {
      $settings = $block->get('settings');
      $settings += [
        'use_token' => FALSE,
      ];
      $block->set('settings', $settings);
      // Cast and sort values properly using the config schema.
      $block->save();
    }
  }
}
