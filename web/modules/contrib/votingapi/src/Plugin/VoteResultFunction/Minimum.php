<?php

declare(strict_types=1);

namespace Drupal\votingapi\Plugin\VoteResultFunction;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\votingapi\Attribute\VoteResultFunction;
use Drupal\votingapi\VoteResultFunctionBase;

/**
 * The minimum of a set of votes.
 */
#[VoteResultFunction(
  id: "vote_minimum",
  label: new TranslatableMarkup("Minimum"),
  description: new TranslatableMarkup("The minimum vote value.")
)]
class Minimum extends VoteResultFunctionBase {

  /**
   * {@inheritdoc}
   */
  public function calculateResult(array $votes): float {
    /** @var \Drupal\votingapi\VoteInterface[] $votes */
    $min = reset($votes)->getValue();
    foreach ($votes as $vote) {
      $value = $vote->getValue();
      if ($value < $min) {
        $min = $value;
      }
    }
    return $min;
  }

}
