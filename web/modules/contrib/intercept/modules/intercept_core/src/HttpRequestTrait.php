<?php

namespace Drupal\intercept_core;

use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a trait for HTTP Request objects.
 */
trait HttpRequestTrait {

  /**
   * Gets a Request object's parameters.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current Request object.
   *
   * @return array
   *   The Request object query and post parameters.
   */
  protected function getParams(Request $request) {
    // Accept query string params, and then also accept a post request.
    $params = $request->query->get('filter');

    if ($post = Json::decode($request->getContent())) {
      $params = empty($params) ? $post : array_merge($params, $post);
    }
    return $params;
  }

}
