<?php

namespace Drupal\domain_path;

/**
 * Loads Domain path records.
 */
class DomainPathLoader implements DomainPathLoaderInterface {

  /**
   * {@inheritdoc}
   */
  public function load($id, $reset = FALSE) {
    $controller = $this->getStorage();
    if ($reset) {
      $controller->resetCache(array($id));
    }
    return $controller->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties($properties) {
    $controller = $this->getStorage();

    return $controller->loadByProperties($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL, $reset = FALSE) {
    $controller = $this->getStorage();
    if ($reset) {
      $controller->resetCache($ids);
    }
    return $controller->loadMultiple($ids);
  }

  /**
   * Loads the storage controller.
   *
   * We use the loader very early in the request cycle. As a result, if we try
   * to inject the storage container, we hit a circular dependency. Using this
   * method at least keeps our code easier to update.
   */
  public function getStorage() {
    $storage = \Drupal::entityTypeManager()->getStorage('domain_path');
    return $storage;
  }

}
