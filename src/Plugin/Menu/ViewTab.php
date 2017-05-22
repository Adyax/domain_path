<?php

namespace Drupal\domain_path\Plugin\Menu;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines a class for overriding the view tab.
 */
class ViewTab extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);

    if ($route_match->getRouteName() == 'domain_path.view') {
      $entity_type_id = !empty($this->pluginDefinition['entity_type_id']) ? $this->pluginDefinition['entity_type_id'] : NULL;
      if ($entity_type_id && $this->pluginDefinition['route_name'] == "entity.$entity_type_id.canonical") {
        $options['attributes']['class'][] = 'visually-hidden';
      }
    }

    return (array) $options;
  }

}
