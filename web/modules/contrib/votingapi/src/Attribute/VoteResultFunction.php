<?php

declare(strict_types=1);

namespace Drupal\votingapi\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a voting result function attribute object.
 *
 * Plugin Namespace: Plugin\votingapi\VoteResultFunction.
 *
 * For a working example, see
 * \Drupal\votingapi\Plugin\VoteResultFunction\Sum
 *
 * @see hook_vote_result_info_alter()
 * @see \Drupal\votingapi\VoteResultFunctionInterface
 * @see \Drupal\votingapi\VoteResultFunctionBase
 * @see \Drupal\votingapi\VoteResultFunctionManager
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class VoteResultFunction extends Plugin {

  /**
   * Constructs a VoteResultFunction attribute object.
   *
   * @param string $id
   *   The plugin ID. The machine-name of the function plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the widget.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   A short description of the widget.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly TranslatableMarkup $description,
    public readonly ?string $deriver = NULL,
  ) {}

}
