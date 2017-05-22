<?php

/**
 * @file
 * Contains \Drupal\domain_path\Plugin\Derivative\DynamicDomainPathViewLocalTasks.
 */

namespace Drupal\domain_path\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic domain path view local tasks.
 */
class DynamicDomainPathViewLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $enabled_entity_types = _domain_path_get_configured_entity_types();
    if ($enabled_entity_types) {
      foreach (array_keys($enabled_entity_types) as $entity_type) {
        $this->derivatives["domain_path.$entity_type.view"] = $base_plugin_definition;
        $this->derivatives["domain_path.$entity_type.view"]['title'] = 'View';
        $this->derivatives["domain_path.$entity_type.view"]['route_name'] = 'domain_path.view';
        $this->derivatives["domain_path.$entity_type.view"]['base_route'] = "entity.$entity_type.canonical";
      }
    }

    return $this->derivatives;
  }

}
