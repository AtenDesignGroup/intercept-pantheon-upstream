<?php

namespace Drupal\intercept_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\intercept_core\Utility\Obfuscate;
use Drupal\intercept_core\HttpRequestTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller for user account routes.
 */
class UserAccount extends ControllerBase {

  use HttpRequestTrait;

  /**
   * ILS client object.
   *
   * @var object
   */
  private $client;

  /**
   * Constructs a new UserAccount controller.
   */
  public function __construct() {
    $config_factory = \Drupal::service('config.factory');
    $settings = $config_factory->get('intercept_ils.settings');
    $intercept_ils_plugin = $settings->get('intercept_ils_plugin', '');
    if ($intercept_ils_plugin) {
      $ils_manager = \Drupal::service('plugin.manager.intercept_ils');
      $ils_plugin = $ils_manager->createInstance($intercept_ils_plugin);
      $this->client = $ils_plugin->getClient();
    }
  }

  /**
   * Returns a redirect response object for the specified route.
   *
   * @param string $route_name
   *   The name of the route to which to redirect.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current Request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   */
  public function userRedirect($route_name, Request $request) {
    $params = \Drupal::service('current_route_match')->getParameters();
    $options = [];
    if (($return = $request->getRequestUri()) && strpos($return, '?return=')) {
      $options['query']['return'] = array_pop(explode('?return=', $return));
    }
    elseif (($type = $request->getRequestUri()) && strpos($type, '?type=')) {
      $options['query']['type'] = @array_pop(explode('?type=', $type));
    }
    elseif (($uid_current = $request->getRequestUri()) && strpos($uid_current, '?uid_current=')) {
      $options['query']['uid_current'] = @array_pop(explode('?uid_current=', $uid_current));
    }
    if (strstr($route_name, 'management') || strstr($route_name, 'account.save')) {
      $current_user = \Drupal::currentUser();
      $params->add(['user' => $current_user->id()]);
    }
    switch ($route_name) {
      case 'entity.user.customer_form':
      case 'entity.user.edit_form':
      case 'entity.user.equipment_reservations':
      case 'intercept_event.management.event_templates':
        $current_user = \Drupal::currentUser();
        $params->add(['user' => $current_user->id()]);
        break;
    }
    $params->remove('route_name');

    return $this->redirect($route_name, $params->all(), $options);
  }

  /**
   * Gets a user's uuid and name by barcode.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JsonResponse object with keys uuid and name.
   */
  public function customerRegisterApi(Request $request) {
    $params = $this->getParams($request);
    $user = FALSE;
    if (!empty($params['barcode'])) {
      $user = \Drupal::service('intercept_ils.association_manager')->loadByBarcode($params['barcode']);
    }
    return new JsonResponse(!empty($user) ? [
      'uuid' => $user->uuid(),
      'name' => $user->full_name,
    ] : [], 200);
  }

  /**
   * Searches for a customer's email.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JsonResponse object with obfuscated emails.
   */
  public function customerSearchApi(Request $request) {
    $params = $this->getParams($request);
    if ($this->client) {
      $search = $this->client->patron->searchBasic($params);
      foreach ($search as &$result) {
        $result['email'] = Obfuscate::email($result['email']);
      }
      return new JsonResponse($search, 200);
    }
    return new JsonResponse();
  }

  /**
   * Searches for a user by email.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JsonResponse object with user IDs.
   */
  public function userEmailExistsApi(Request $request) {
    $params = $this->getParams($request);
    if (!empty($params['email'])) {
      $user = $this->entityTypeManager()
        ->getStorage('user')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('mail', $params['email'])
        ->execute();
      return new JsonResponse($user, 200);
    }
    return new JsonResponse();
  }

}
