<?php

namespace Drupal\domain_path;

class DomainPathHelper {

  /**
   * Helper function for retrieving configured entity types.
   *
   * @return array|mixed|null
   */
  public function getConfiguredEntityTypes() {
    $config = \Drupal::config('domain_path.settings');
    $enabled_entity_types = $config->get('entity_types');
    $enabled_entity_types = array_filter($enabled_entity_types);

    return array_keys($enabled_entity_types);
  }

  public function getConfiguredEntityCanonical() {
    $enabled_entity_types = $this->getConfiguredEntityTypes();
    $enabled_entity_canonical = [];

    foreach ($enabled_entity_types as $enabled_entity_type) {
      $enabled_entity_canonical["entity.$enabled_entity_type.canonical"] = $enabled_entity_type;
    }

    return $enabled_entity_canonical;
  }

}