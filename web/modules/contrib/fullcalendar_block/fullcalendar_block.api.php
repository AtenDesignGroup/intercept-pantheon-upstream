<?php

/**
 * @file
 * Hooks for the fullcalendar_block module.
 */

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\Core\Block\BlockPluginInterface;

/**
 * Alter the Fullcalendar block settings for a specific block.
 *
 * @param array $block_settings
 *   The current fullcalendar block settings.
 * @param array $block_content
 *   The block render array.
 * @param \Drupal\Core\Block\BlockPluginInterface $block
 *   The current block instance.
 */
function hook_fullcalendar_block_settings_alter(array &$block_settings, array &$block_content, BlockPluginInterface $block) {
  // Specify some custom default advanced options.
  $block_settings['advanced'] += [
    'dialog_type' => 'modal',
    'dialog_options' => [
      // Disable Drupal's default autoResize feature on all blocks.
      'autoResize' => FALSE,
    ],
    'draggable' => TRUE,
    'draggable_options' => [],
    'resizable' => TRUE,
    'resizable_options' => [],
    // Enable description popups.
    'description_popup' => TRUE,
    // Field to use for the description field popup.
    'description_field' => 'des',
  ];

  if ($block->getPluginId() === 'my_block_id') {
    // Specify the initial date (although this might be inherently uncacheable).
    // \Drupal::service('page_cache_kill_switch')->trigger();
    $block_settings['calendar_options']['initialDate'] = date(DATE_ATOM, \Drupal::time()->getRequestTime());
  }

  if ($block->getPluginId() === 'multiple_events_block') {
    // Provide multiple event sources in addition to the current one.
    $block_settings['calendar_options']['events'] = [
      $block_settings['calendar_options']['events'],
      '/alternative-event-source-2',
      '/alternative-event-source-3',
    ];
  }
}

/**
 * @} End of "addtogroup hooks".
 */
