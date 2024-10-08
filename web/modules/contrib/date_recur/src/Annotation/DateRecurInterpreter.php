<?php

declare(strict_types=1);

namespace Drupal\date_recur\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a Date recur occurrence handler item annotation object.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class DateRecurInterpreter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

}
