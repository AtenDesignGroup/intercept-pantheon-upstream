<?php

declare(strict_types=1);

namespace Drupal\date_recur\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Attribute for defining an interpreter for Recurring date field.
 *
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class DateRecurInterpreter extends Plugin {

  private const USE_NAMED_PARAMETERS = 'Using this attribute without named parameters is not supported.';

  /**
   * @phpstan-param class-string<\Drupal\Component\Plugin\Derivative\DeriverInterface>|null $deriver
   */
  public function __construct(
    string $id,
    public readonly TranslatableMarkup $label,
    ?string $useNamedParameters = self::USE_NAMED_PARAMETERS,
    ?string $deriver = NULL,
  ) {
    parent::__construct($id, $deriver);

    if (self::USE_NAMED_PARAMETERS !== $useNamedParameters) {
      throw new \LogicException(self::USE_NAMED_PARAMETERS);
    }
  }

}
