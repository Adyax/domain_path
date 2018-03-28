<?php

namespace Drupal\domain_path\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Custom access control handler for the domain path overview page.
 */
class DomainPathListCheck {

  /**
   * Handles route permissions on the domain path list page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account making the route request.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public static function viewDomainPathList(AccountInterface $account) {
    if ($account->hasPermission('administer domain paths')
      || $account->hasPermission('view domain paths')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
