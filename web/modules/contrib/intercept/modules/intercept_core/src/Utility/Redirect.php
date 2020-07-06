<?php

namespace Drupal\intercept_core\Utility;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

class Redirect {

  use DependencySerializationTrait;

  /**
   * The config factory service.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The immutable intercept_core.settings object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Current request from request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * User storage handler from entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Redirect constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param RedirectDestinationInterface $redirect_destination
   *   Destination service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('intercept_core.settings');
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * Form submit callback for user_login_form.
   *
   * @param array $form
   *   Associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function userLoginFormSubmitCallback(&$form, FormStateInterface $form_state) {
    $form_state->get('intercept_redirect_utility')->userLoginFormSubmit($form, $form_state);
  }

  /**
   * Add submit callback to user_login_form.
   *
   * @see intercept_core_form_user_login_form_alter()
   *
   * @param array $form
   *   Associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   */
  public function userLoginFormAlter(&$form, FormStateInterface $form_state) {
    if (!$this->config->get('enable_dashboard_redirect')) {
      return;
    }

    $form['#submit'][] = [static::class, 'userLoginFormSubmitCallback'];
    $form_state->set('intercept_redirect_utility', $this);
  }

  /**
   * Non-static form submit callback for the user_login_form.
   *
   * @param array $form
   *   Associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   */
  public function userLoginFormSubmit(&$form, FormStateInterface $form_state) {
    // The form build adds a destination to #action to redirect back to the page
    // they logged in from.
    // We want to override that, but not if the page landed on had an actual
    // destination set in the query string parameters.
    $request_uri = $this->currentRequest->getRequestUri();
    $path = $this->getUriPath($request_uri);
    $destination = $this->getUriDestinationPath($request_uri);
    if ($destination && ($path != $destination)) {
      return;
    }

    if (!$this->redirectAppliesForUserRoles($form_state->get('uid'))) {
      return;
    }

    if ($this->requestUriPathIsWhitelisted($path)) {
      return;
    }

    $dashboard = Url::fromRoute('entity.user.canonical', ['user' => $form_state->get('uid')]);
    $this->currentRequest->query->set('destination', $dashboard->toString());
  }

  /**
   * Parse uri for query param destination path.
   *
   * @param $uri
   *   Full string uri.
   *
   * @return string|null
   *   Path part of the parsed query param destination.
   */
  private function getUriDestinationPath($uri) {
    $parsed = UrlHelper::parse($uri);
    $destination = $parsed['query']['destination'] ?? NULL;
    if (!$destination) {
      return NULL;
    }
    // The destination itself might have a query string as well.
    return $this->getUriPath($destination);
  }

  /**
   * Parse uri for path.
   *
   * @param $uri
   *   Full string uri.
   *
   * @return string
   *   Path part of the parsed uri.
   */
  private function getUriPath($uri) {
    $parsed = UrlHelper::parse($uri);
    return $parsed['path'];
  }

  /**
   * Check to see if the redirect applies to this user.
   *
   * @param $uid
   *   User ID.
   *
   * @return bool
   *   TRUE if the redirect applies to this user.
   */
  private function redirectAppliesForUserRoles($uid) {
    $limit_roles = $this->config->get('dashboard_redirect_limit_roles');
    if (empty($limit_roles)) {
      return TRUE;
    }
    $user = $this->userStorage->load($uid);
    $user_roles = $user->getRoles(TRUE);
    $has = array_intersect($user_roles, $limit_roles);
    return !empty($has);
  }

  /**
   * Checks if path is whitelisted in config.
   *
   * @param string $path
   *   Uri string path.
   *
   * @return bool
   *   TRUE if path has been whitelisted.
   */
  protected function requestUriPathIsWhitelisted($path) {
    $whitelist = $this->config->get('dashboard_redirect_whitelist');
    $whitelist_array = explode("\r\n", $whitelist);
    return in_array($path, $whitelist_array);
  }
}
