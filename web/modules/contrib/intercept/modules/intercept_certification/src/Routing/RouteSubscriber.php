<?php

namespace Drupal\intercept_certification\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Disable caching for this views page so that validation messages
    // can always be seen by staff.
    if ($route = $collection->get('view.intercept_certifications.page')) {
      $options = $route->getOptions();
      $options['no_cache'] = TRUE;
      $route->setOptions($options);
    }
  }

}
