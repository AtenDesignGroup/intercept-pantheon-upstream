<?php

namespace Drupal\flag\Hook;

use Drupal\views\ViewExecutable;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\flag\FlagServiceInterface;

/**
 * Hook implementations for flag.
 */
class FlagViewsExecutionHooks {

  public function __construct(
    protected FlagServiceInterface $flagService,
  ) {
  }

  /**
   * Implements hook_views_query_substitutions().
   */
  #[Hook('views_query_substitutions')]
  public function viewsQuerySubstitutions(ViewExecutable $view) {
    // Only act on views with flag relationships.
    if (!array_key_exists('flagging', $view->getBaseTables())) {
      return [];
    }
    // Allow replacement of current user's session id so we can cache these
    // queries.
    return [
      '***FLAG_CURRENT_USER_SID***' => $this->flagService->getAnonymousSessionId(),
    ];
  }

}
