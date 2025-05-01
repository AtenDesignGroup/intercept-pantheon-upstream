<?php

declare(strict_types=1);

namespace Drupal\votingapi\Plugin\VoteResultFunction;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\votingapi\Attribute\VoteResultFunction;
use Drupal\votingapi\VoteResultFunctionBase;

/**
 * The average of a set of votes.
 */
#[VoteResultFunction(
  id: "vote_average",
  label: new TranslatableMarkup("Average"),
  description: new TranslatableMarkup("The average vote value.")
)]
class Average extends VoteResultFunctionBase {

  /**
   * {@inheritdoc}
   */
  public function calculateResult(array $votes): float {
    /** @var \Drupal\votingapi\VoteInterface[] $votes */
    $count = count($votes);
    if ($count === 0) {
      return 0;
    }

    $total = 0;
    foreach ($votes as $vote) {
      $total += $vote->getValue();
    }
    return ($total / $count);
  }

}
