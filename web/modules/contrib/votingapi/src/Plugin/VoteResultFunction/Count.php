<?php

declare(strict_types=1);

namespace Drupal\votingapi\Plugin\VoteResultFunction;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\votingapi\Attribute\VoteResultFunction;
use Drupal\votingapi\VoteResultFunctionBase;

/**
 * A number of votes in a set of votes.
 */
#[VoteResultFunction(
  id: "vote_count",
  label: new TranslatableMarkup("Count"),
  description: new TranslatableMarkup("The number of votes cast.")
)]
class Count extends VoteResultFunctionBase {

  /**
   * {@inheritdoc}
   */
  public function calculateResult(array $votes): float {
    /** @var \Drupal\votingapi\VoteInterface[] $votes */
    return count($votes);
  }

}
