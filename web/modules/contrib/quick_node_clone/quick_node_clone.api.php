<?php

/**
 * @file
 * Hooks related to quick_node_clone module and it's plugins.
 */

/**
 * @param $node
 */
function hook_cloned_node_alter(&$node){
  $node->setTitle('Old node cloned');
  $node->save();
}


/**
 * @param $paragraph
 * @param $pfield_name
 * @param $pfield_settings
 */
function hook_cloned_node_paragraph_field_alter(&$paragraph, $pfield_name, $pfield_settings)
{

}