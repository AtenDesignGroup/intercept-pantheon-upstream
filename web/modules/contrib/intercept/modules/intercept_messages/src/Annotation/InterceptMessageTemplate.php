<?php

namespace Drupal\intercept_messages\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Intercept message template item annotation object.
 *
 * @see \Drupal\intercept_messages\Plugin\InterceptMessageTemplateManager
 * @see plugin_api
 *
 * @Annotation
 */
class InterceptMessageTemplate extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A categorizing type for the message templates.
   *
   * @var string
   */
  public $type = '';

  /**
   * The category in the admin UI where the template will be listed.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category = '';

  /**
   * The plugin weight.
   *
   * Used when sorting the message configuration list in the UI.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * An array of context definitions describing the context used by the plugin.
   *
   * The array is keyed by context names.
   *
   * @var \Drupal\Core\Annotation\ContextDefinition[]
   */
  public $context_definitions = [];

}
