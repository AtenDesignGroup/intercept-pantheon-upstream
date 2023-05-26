<?php

use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
/**
 * @file
 * Hooks related to quick_node_clone module and it's plugins.
 */

/**
 * Called when a node is cloned.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node being cloned.
 */
function hook_cloned_node_alter(NodeInterface &$node) {
  $node->setTitle('Old node cloned');
  $node->save();
}

/**
 * Called when a node is cloned with a paragraph field.
 *
 * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
 *   The paragraph entity.
 * @param string $pfield_name
 *   The paragraph field name.
 * @param mixed $pfield_settings
 *   The paragraph settings.
 */
function hook_cloned_node_paragraph_field_alter(Paragraph &$paragraph, $pfield_name, $pfield_settings) {

}
