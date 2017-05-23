<?php

namespace Drupal\domain_path\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Listens to the dynamic route events.
 */
class DomainPathRouteSubscriber {

  public function routes() {
    $route_provider = \Drupal::service('router.route_provider');

    $route_collection = new RouteCollection();
    $domain_path_helper = \Drupal::service('domain_path.helper');
    $enabled_entity_types = $domain_path_helper->getConfiguredEntityTypes();

    foreach ($enabled_entity_types as $type) {
      $route = $route_provider->getRouteByName("entity.$type.canonical");
      $route->setPath('domain_path/{domain}/' . $type. '/{' . $type . '}');
      $route->addRequirements([
        '_custom_access' => '\Drupal\domain_path\DomainPathAccess::access',
      ]);

      // Add our route to the collection with a unique key.
      $route_collection->add('domain_path.view.' . $type, $route);
    }

    return $route_collection;
  }

}