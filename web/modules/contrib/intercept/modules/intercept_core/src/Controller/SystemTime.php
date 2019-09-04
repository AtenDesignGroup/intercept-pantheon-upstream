<?php

namespace Drupal\intercept_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\HttpFoundation\JsonResponse;

class SystemTime extends ControllerBase {

  public function response() {
    $date = new DrupalDateTime();
    $response = JsonResponse::create(200);
    $response->setData(['timestamp' => $date->format('U')]);
    return $response;
  }
}
