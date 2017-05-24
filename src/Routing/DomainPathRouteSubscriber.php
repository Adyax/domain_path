<?php

namespace Drupal\domain_path\Routing;

use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class DomainPathRouteSubscriber {

  /**
   * @return \Symfony\Component\Routing\RouteCollection
   */
  public function routes() {
    $route_provider = \Drupal::service('router.route_provider');

    $route_collection = new RouteCollection();
    $domain_path_helper = \Drupal::service('domain_path.helper');
    $enabled_entity_types = $domain_path_helper->getConfiguredEntityTypes();

    foreach ($enabled_entity_types as $enabled_entity_type) {
      $route = $route_provider->getRouteByName("entity.$enabled_entity_type.canonical");
      $route->setPath('domain_path/{domain}/' . $enabled_entity_type. '/{' . $enabled_entity_type . '}');
      $route->addRequirements([
        '_custom_access' => '\Drupal\domain_path\DomainPathAccess::access',
      ]);

      // Add our route to the collection
      $route_collection->add('domain_path.view.' . $enabled_entity_type, $route);
    }

    return $route_collection;
  }

}