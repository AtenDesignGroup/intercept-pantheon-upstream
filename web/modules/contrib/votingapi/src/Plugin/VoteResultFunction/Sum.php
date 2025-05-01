<?php

declare(strict_types=1);

namespace Drupal\votingapi\Plugin\VoteResultFunction;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\votingapi\Attribute\VoteResultFunction;
use Drupal\votingapi\VoteResultFunctionBase;

/**
 * A sum total of a set of votes.
 */
#[VoteResultFunction(
  id: "vote_sum",
  label: new TranslatableMarkup("Sum"),
  description: new TranslatableMarkup("The total of all vote values.")
)]
class Sum extends VoteResultFunctionBase {

  /**
   * {@inheritdoc}
   */
  public function calculateResult(array $votes): float {
    $total = 0;
    foreach ($votes as $vote) {
      $total += $vote->getValue();
    }
    return $total;
  }

}
