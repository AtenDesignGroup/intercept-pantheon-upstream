<?php

namespace Drupal\intercept_core\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\intercept_core\Utility\Obfuscate;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserAccount extends ControllerBase {

  private $client;

  public function __construct() {
    $config_factory = \Drupal::service('config.factory');
    $settings = $config_factory->get('intercept_ils.settings');
    $intercept_ils_plugin = $settings->get('intercept_ils_plugin', '');
    $ils_manager = \Drupal::service('plugin.manager.intercept_ils');
    $ils_plugin = $ils_manager->createInstance($intercept_ils_plugin);
    $this->client = $ils_plugin->getClient();
  }

  public function userRedirect($route_name, Request $request) {
    $params = \Drupal::service('current_route_match')->getParameters();
    $options = [];
    if (($return = $request->getRequestUri()) && strpos($return, '?return=')) {
      $options['query']['return'] = array_pop(explode('?return=', $return));
    }
    $params->add(['user' => $this->currentUser()->id()]);
    $params->remove('route_name');

    return $this->redirect($route_name, $params->all(), $options);
  }

  public function customerRegisterApi(Request $request) {
    $params = $this->getParams($request);
    $user = FALSE;
    if (!empty($params['barcode'])) {
      $user = \Drupal::service('intercept_ils.mapping_manager')->loadByBarcode($params['barcode']);
    }
    return JsonResponse::create(!empty($user) ? [
      'uuid' => $user->uuid(),
      'name' => $user->full_name,
    ] : [], 200);
  }

  public function customerSearchApi(Request $request) {
    $params = $this->getParams($request);
    $search = $this->client->patron->searchBasic($params);
    foreach ($search as &$result) {
      $result['email'] = Obfuscate::email($result['email']);
    }
    return JsonResponse::create($search, 200);
  }

  protected function getParams(Request $request) {
    // Accept query sring params, and then also accept a post request.
    $params = $request->query->get('filter');

    if ($post = Json::decode($request->getContent())) {
      $params = empty($params) ? $post : array_merge($params, $post);
    }
    return $params;
  }

}
