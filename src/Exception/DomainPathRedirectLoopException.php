<?php

/**
 * @file
 * Contains \Drupal\domain_path\Exception\DomainPathRedirectLoopException
 */

namespace Drupal\domain_path\Exception;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Exception for when a redirect loop is detected.
 */
class DomainPathRedirectLoopException extends \RuntimeException {

  /**
   * Formats a redirect loop exception message.
   *
   * @param string $path
   *   The path that results in a redirect loop.
   * @param int $rid
   *   The redirect ID that is involved in a loop.
   */
  public function __construct($domain_id, $entity_type, $id) {
    parent::__construct(new FormattableMarkup('Redirect loop identified for %entity_type nid:%id on domain:%domain', ['%entity_type' => $entity_type, '%id' => $id, '%domain' => $domain_id]));
  }

}
