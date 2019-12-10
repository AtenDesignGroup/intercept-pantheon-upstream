<?php

namespace Drupal\intercept_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines a controller to return the current timestamp.
 */
class SystemTime extends ControllerBase {

  /**
   * Returns the current timestamp as a JsonResponse.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JsonResponse with the current timestamp.
   */
  public function response() {
    $date = new DrupalDateTime();
    $response = JsonResponse::create(200);
    $response->setData(['timestamp' => $date->format('U')]);
    return $response;
  }

}
