<?php

declare(strict_types=1);

namespace Drupal\votingapi\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Hook implementations used to provide help.
 */
final class VotingApiHelpHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new VotingApiHelpHooks service.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    TranslationInterface $string_translation,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.votingapi':
        $output = '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('Voting API helps developers who want to use a standardized API and schema for storing, retrieving and tabulating votes for Drupal content.') . '</p>';
        $output .= '<p>' . $this->t('Please view the %url page for more details.', [
          '%url' => Link::fromTextAndUrl($this->t('Voting API'), Url::fromUri('https://www.drupal.org/project/votingapi'))->toString(),
        ]) . '</p>';
        $output .= '<h3>' . $this->t('Uses') . '</h3>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('General') . '</dt>';
        $output .= '<dd>' . $this->t('Voting API uses a flexible, easy-to-use framework for rating, voting, moderation, and consensus-gathering modules in Drupal. Module developers use it to focus on their ideas (say, providing a "rate this thread" widget at the bottom of each forum post) without worrying about the grunt work of storing votes, preventing ballot-stuffing, calculating the results, and so on.') . '</dd>';
        $output .= '<dt>' . $this->t('Basic Concepts and Features') . '</dt>';
        $output .= '<dd>' . $this->t('Voting API does NOT directly expose any voting mechanisms to end users. It is a framework designed to make life easier for other developers, and to standardize voting data for consumption by other modules (like Views).') . '</dd>';
        $output .= '<dd>' . $this->t('Two kinds of records are stored: individual votes by each user on each piece of content, and cached "result" records. The cached records aggregate calculated values like the average vote for a piece of content, how many people voted on it, etc. Each time a user votes, the cached result records are automatically recalculated. This means that no "on-the-fly" calculations have to be done when displaying content ratings.') . '</dd>';
        $output .= '</dl>';
        return $output;

      default:
    }
    return NULL;
  }

}
