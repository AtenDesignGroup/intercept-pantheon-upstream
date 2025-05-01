<?php

declare(strict_types=1);

namespace Drupal\votingapi_test\Plugin\VoteResultFunction;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\votingapi\Attribute\VoteResultFunction;
use Drupal\votingapi\VoteResultFunctionBase;

/**
 * A test plugin for the Voting API module.
 */
#[VoteResultFunction(
  id: "zebra",
  label: new TranslatableMarkup("Zebra"),
  description: new TranslatableMarkup("A vote test plugin.")
)]
class Zebra extends VoteResultFunctionBase {

  /**
   * {@inheritdoc}
   */
  public function calculateResult(array $votes): float {
    return 10101;
  }

}
