<?php

namespace Drupal\domain_path\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\domain_path\DomainPathInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Domain Path entity routes.
 */
class DomainPathViewController extends ControllerBase {

  /**
   * Redirect domain path view page to domain path edit page.
   *
   * @param \Drupal\domain_path\DomainPathInterface $domain_path
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function view(DomainPathInterface $domain_path) {
    return new RedirectResponse(Url::fromRoute('entity.domain_path.edit_form', ['domain_path' => $domain_path->id()])->toString());
  }

}
