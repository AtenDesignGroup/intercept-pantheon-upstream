<?php

namespace Drupal\intercept_event\Controller;

use Psr\Log\LoggerInterface;
use Drupal\user\UserDataInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Flood\FloodInterface;
use Drupal\user\UserStorageInterface;
use Drupal\user\Controller\UserController;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns responses for Intercept Event routes.
 */
class InterceptEventUserController extends UserController {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   */
  public function __construct(DateFormatterInterface $date_formatter, UserStorageInterface $user_storage, UserDataInterface $user_data, LoggerInterface $logger, FloodInterface $flood) {
    $this->dateFormatter = $date_formatter;
    $this->userStorage = $user_storage;
    $this->userData = $user_data;
    $this->logger = $logger;
    $this->flood = $flood;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('user.data'),
      $container->get('logger.factory')->get('user'),
      $container->get('flood')
    );
  }

  /**
   * Validates user, hash, and timestamp; logs the user in if correct.
   *
   * @param int $uid
   *   User ID of the user requesting reset.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the user edit form if the information is correct.
   *   If the information is incorrect redirects to 'user.pass' route with a
   *   message for the user.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If $uid is for a blocked user or invalid user ID.
   */
  public function resetPassLogin($uid, $timestamp, $hash, Request $request) {
    // The current user is not logged in, so check the parameters.
    $current = \Drupal::time()->getRequestTime();
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($uid);

    // Verify that the user exists and is active.
    if ($user === NULL || !$user->isActive()) {
      // Blocked or invalid user ID, so deny access. The parameters will be in
      // the watchdog's URL for the administrator to check.
      throw new AccessDeniedHttpException();
    }

    // Time out, in seconds, until login URL expires.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');
    // No time out for first time login.
    if ($user->getLastLoginTime() && $current - $timestamp > $timeout) {
      $this->messenger()->addError($this->t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'));
      return $this->redirect('user.pass');
    } elseif ($user->isAuthenticated() && ($timestamp >= $user->getLastLoginTime()) && ($timestamp <= $current) && hash_equals($hash, user_pass_rehash($user, $timestamp))) {
      user_login_finalize($user);
      $this->logger->notice('User %name used evaluation login link at time %timestamp.', ['%name' => $user->getDisplayName(), '%timestamp' => $timestamp]);
      $message = 'We logged you in automatically.';
      if (array_key_exists('destination', $request->query->all()) && $request->query->get('destination') == '/account/events') {
        $message .= ' Thanks for sharing your feedback with us!';
      }
      $this->messenger()->addStatus($this->t($message));
      // Let the user's password be changed without the current password check.
      $token = Crypt::randomBytesBase64(55);
      $request->getSession()->set('pass_reset_' . $user->id(), $token);
      // Clear any flood events for this user.
      $this->flood->clear('user.password_request_user', $uid);
      return $this->redirect(
        'entity.user.edit_form',
        ['user' => $user->id()],
        [
          'query' => ['pass-reset-token' => $token],
          'absolute' => TRUE,
        ]
      );
    }

    $this->messenger()->addError($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'));
    return $this->redirect('user.pass');
  }

}
