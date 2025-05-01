<?php

declare(strict_types=1);

namespace Drupal\votingapi\Plugin\VoteResultFunction;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\votingapi\Attribute\VoteResultFunction;
use Drupal\votingapi\VoteResultFunctionBase;

/**
 * The maximum of a set of votes.
 */
#[VoteResultFunction(
  id: "vote_maximum",
  label: new TranslatableMarkup("Maximum"),
  description: new TranslatableMarkup("The maximum vote value.")
)]
class Maximum extends VoteResultFunctionBase {

  /**
   * {@inheritdoc}
   */
  public function calculateResult(array $votes): float {
    $max = 0;
    /** @var \Drupal\votingapi\VoteInterface[] $votes */
    foreach ($votes as $vote) {
      $value = $vote->getValue();
      if ($value > $max) {
        $max = $value;
      }
    }
    return $max;
  }

}
