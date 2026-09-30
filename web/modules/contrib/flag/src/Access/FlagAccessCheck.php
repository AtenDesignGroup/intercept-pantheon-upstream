<?php

namespace Drupal\flag\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagServiceInterface;

/**
 * Provides routes with the ability to check access to the 'flag' action.
 *
 * @ingroup flag_access
 */
class FlagAccessCheck implements AccessInterface {

  public function __construct(
    protected FlagServiceInterface $flagService,
  ) {
  }

  /**
   * Checks access to the 'flag' action.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(RouteMatchInterface $route_match, FlagInterface $flag, AccountInterface $account) {
    $flaggable_id = $route_match->getParameter('entity_id');
    $flaggable = $this->flagService->getFlaggableById($flag, $flaggable_id);
    return $flag->actionAccess('flag', $account, $flaggable);
  }

}
