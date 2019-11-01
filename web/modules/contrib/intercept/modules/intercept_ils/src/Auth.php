<?php

namespace Drupal\intercept_ils;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\externalauth\Authmap;
use Drupal\externalauth\ExternalAuth;
use Drupal\intercept_ils\ILSManager;
use Drupal\user\UserAuth;
use Symfony\Component\DependencyInjection\Reference;

class Auth extends UserAuth {

  private $userAuth;

  private $client;

  private $externalAuth;

  protected $interceptILSPlugin;

  public function __construct(EntityManagerInterface $entity_manager, PasswordInterface $password_checker, UserAuth $user_auth, ExternalAuth $external_auth, Authmap $external_authmap, ConfigFactoryInterface $config_factory, ILSManager $ils_manager) {
    $this->userAuth = $user_auth;
    $settings = $config_factory->get('intercept_ils.settings');
    $intercept_ils_plugin = $settings->get('intercept_ils_plugin', '');
    if ($intercept_ils_plugin) {
      $this->interceptILSPlugin = $ils_manager->createInstance($intercept_ils_plugin);
      $this->client = $this->interceptILSPlugin->getClient();
    }
    $this->externalAuth = $external_auth;
    $this->externalAuthmap = $external_authmap;
    parent::__construct($entity_manager, $password_checker);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($username, $password) {
  
    // 1) Let Drupal authenticate first to speed up authentication.
    $auth = parent::authenticate($username, $password);
    if ($auth) {
      return $auth;
    }
    if (!empty($this->interceptILSPlugin)) {
      $plugin_id = $this->interceptILSPlugin->getId();
      // 2) Check if it's a valid username, and just an incorrect password
      // (and also not an account that was built using externalauth).
      // If so, return FALSE and do not check ILS.
      if ($user = user_load_by_name($username)) {
        $authmap = \Drupal::service('externalauth.authmap');
        $authdata = $authmap->getAuthdata($user->id(), $plugin_id);
        $authdata_data = unserialize($authdata['data']);
        if (empty($authdata_data)) {
          return FALSE;
        }
      }
      // 3) Check ILS to see if it's valid.
      if ($this->client->patron->authenticate($username, $password)) {
        $patron = $this->client->patron->validate($username);
        // First get user if stored in authmap.
        if ($user = $this->externalAuth->load($patron->barcode(), $plugin_id)) {
          $this->externalAuthmap->save($user, $plugin_id, $patron->barcode(), $patron->basicData());
        }
        else {
          $data = $patron->basicData();
          $account_data = [
            'name' => $patron->barcode(),
            'mail' => $data->EmailAddress,
            'init' => $data->EmailAddress,
            'pass' => $password,
          ];
          // Create a Drupal user automatically and return the new user_id.
          $user = $this->externalAuth->register($patron->barcode(), $plugin_id, $account_data, $data);
        }
        return $user->id();
      }
    }
    return $auth;
  }

  private function isValidUsername($username) {
    return \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $username]);
  }
  /**
   * Automatically inherit methods if they are public.
   */
  public function __call($method, $args) {
    return call_user_func_array(array($this->innerService, $method), $args);
  }
}