<?php

/**
 * @file
 * Contains \Drupal\intercept_ils_sip2\Plugin\ILS\SIP2ILS.
 * Defines a plugin of type "ILS" for use with the intercept_ils module which
 * is part of the Intercept project at https://drupal.org/project/intercept
 */

namespace Drupal\intercept_ils_sip2\Plugin\ILS;

use Drupal\externalauth\Authmap;
use Drupal\key\KeyRepository;
use Drupal\intercept_ils\ILSBase;
use lordelph\SIP2\SIP2Client;
use lordelph\SIP2\Request\LoginRequest;
// The container factory plugin interface is crucial if passing a service
// using dependency injection.
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides access to the ILS for use in Intercept.
 *
 * @ILS(
 *   id = "sip2",
 *   name = "SIP2",
 * )
 */
class SIP2ILS extends ILSBase implements ContainerFactoryPluginInterface {

  /**
   * lordelph\SIP2\SIP2Client definition.
   *
   * @var \lordelph\SIP2\SIP2Client
   */
  protected $client;

  /**
   * Drupal\key\KeyRepository definition.
   *
   * @var \Drupal\key\KeyRepository
   */
  protected $keyRepository;

  /**
   * @var \Drupal\externalauth\Authmap
   */
  protected $authmap;

  /**
   * Constructs a new SIP2ILS object.
   *
   * @param \lordelph\SIP2\SIP2Client $client
   *   intercept_ils_sip2.client service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SIP2Client $client, KeyRepository $keyRepository, Authmap $authmap) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
    $this->keyRepository = $keyRepository;
    $this->authmap = $authmap;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('intercept_ils_sip2.client'),
      $container->get('key.repository'),
      $container->get('externalauth.authmap')
    );
  }

  /**
   *
   */
  public function getClient() {

    $host = $this->keyRepository->getKey('intercept_ils_sip2_host');
    if ($host) {
      $host = $this->keyRepository->getKey('intercept_ils_sip2_host')->getKeyValue();
      $port = $this->keyRepository->getKey('intercept_ils_sip2_port')->getKeyValue();
      $username = $this->keyRepository->getKey('intercept_ils_sip2_username')->getKeyValue();
      $password = $this->keyRepository->getKey('intercept_ils_sip2_password')->getKeyValue();
      $institution_id = $this->keyRepository->getKey('intercept_ils_sip2_institution_id')->getKeyValue();

      // From: https://github.com/lordelph/php-sip2
      // Instantiate client & set any defaults used for all requests.
      $this->client->setDefault('SIPLogin', $username);
      $this->client->setDefault('SIPPassword', $password);
      $this->client->setDefault('InstitutionId', $institution_id);

      // Connect to the SIP server.
      $this->client->connect($host . ':' . $port);

      // Log in to the SIP server.
      $loginRequest = new LoginRequest();
      $this->client->sendRequest($loginRequest);
    }
    else {
      \Drupal::messenger()->addError('SIP2 Client Error: Host is not configured. See README on how to set up the intercept_ils_sip2 module.');
    }

    return $this->client;
  }

  /**
   * @return \Drupal\externalauth\Authmap
   */
  public function authmap() {
    return $this->authmap;
  }

}
