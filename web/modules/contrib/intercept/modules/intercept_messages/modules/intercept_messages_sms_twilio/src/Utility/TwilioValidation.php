<?php

namespace Drupal\intercept_messages_sms_twilio\Utility;

use Drupal\Core\Url;
use Drupal\sms\Plugin\SmsGatewayPluginInterface;
use Symfony\Component\HttpFoundation\Request;
use Twilio\Security\RequestValidator;

/**
 * Methods for validating incoming webhook POST events from Twilio.
 *
 * @package Drupal\intercept_messages_sms_twilio\Utility
 */
class TwilioValidation {

  /**
   * Validate an incoming message using Twilio SDK.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\sms\Plugin\SmsGatewayPluginInterface $sms_gateway
   *   The Twilio plugin.
   *
   * @return bool
   *   TRUE if the request validates, FALSE if not.
   *
   * @see https://www.twilio.com/docs/api/security
   */
  public static function validateIncoming(Request $request, SmsGatewayPluginInterface $sms_gateway) {
    $url = Url::fromRoute('sms.incoming.receive.' . $sms_gateway->getPluginId())
      ->setAbsolute()
      ->toString();
    $signature = $request->headers->get('x-twilio-signature');
    $token = $sms_gateway->getConfiguration()['auth_token'];

    $validator = new RequestValidator($token);

    return $validator->validate($signature, $url, $request->request->all());
  }

}
