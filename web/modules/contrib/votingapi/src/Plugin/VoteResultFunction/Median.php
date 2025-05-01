<?php

declare(strict_types=1);

namespace Drupal\votingapi\Plugin\VoteResultFunction;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\votingapi\Attribute\VoteResultFunction;
use Drupal\votingapi\VoteResultFunctionBase;

/**
 * The median of a set of votes.
 */
#[VoteResultFunction(
  id: "vote_median",
  label: new TranslatableMarkup("Median"),
  description: new TranslatableMarkup("The median vote value.")
)]
class Median extends VoteResultFunctionBase {

  /**
   * {@inheritdoc}
   */
  public function calculateResult(array $votes): float {
    /** @var \Drupal\votingapi\VoteInterface[] $votes */
    $count = count($votes);
    if ($count === 0) {
      return 0;
    }

    $halfway = intdiv($count, 2);
    usort($votes, function ($a, $b) {
      $av = $a->getValue();
      $bv = $b->getValue();
      if ($av == $bv) {
        return 0;
      }
      return ($av < $bv) ? -1 : 1;
    });

    if ($count & 1) {
      // Count is odd.
      return $votes[$halfway]->getValue();
    }
    else {
      // Count is even.
      // Assumes numerically indexed array starting with 0, and that's exactly
      // what we have here because the usort() call replaces the original keys.
      return ($votes[$halfway - 1]->getValue() + $votes[$halfway]->getValue()) / 2;
    }
  }

}
