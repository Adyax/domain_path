<?php

namespace Drupal\domain_path;

use Drupal\node\NodeAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\CustomAccessCheck;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

class DomainPathAccess {

  /**
   * Check if current path has alias
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access() {
/*    $node = \Drupal::routeMatch()->getParameter('node');
    $entity_type = \Drupal::routeMatch()->getParameter('entity_type');*/

    $domain = \Drupal::routeMatch()->getParameter('domain');
    $domain_current = \Drupal::service('domain.negotiator')->getActiveDomain();
    $current_path = \Drupal::service('path.current')->getPath();
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $path_alias = \Drupal::service('path.alias_storage')->lookupPathAlias($current_path, $language);
    if ($domain === $domain_current->id() && $path_alias) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }
}
