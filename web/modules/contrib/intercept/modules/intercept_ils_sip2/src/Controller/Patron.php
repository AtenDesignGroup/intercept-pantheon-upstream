<?php

namespace Drupal\intercept_ils_sip2\Controller;

use Drupal\Core\Entity\EntityBase;
use Drupal\user\UserInterface;
use lordelph\SIP2\SIP2Client;
use lordelph\SIP2\Request\PatronInformationRequest;

/**
 * Defines functions specific to the library's patrons/customers.
 */
class Patron extends EntityBase {

  /**
   * @var Client
   */
  protected $client;

  /**
   * Request constructor.
   *
   * @param Client $client
   */
  public function __construct(SIP2Client $client) {
    $this->client = $client;
  }

  /**
   * Current Patron's barcode.
   *
   * @var int
   */
  public $barcode;

  public function patronInformationRequest($barcode) {
    // Grab the PIN.
    $tempstore = \Drupal::service('tempstore.private')->get('intercept_ils_sip2');
    $pin = $tempstore->get('pin');

    $request = new PatronInformationRequest();
    $request->setVariable('PatronIdentifier', $barcode);
    $request->setVariable('PatronPassword', $pin);
    $request->setType('none'); // General info about patron is the "none" type.
    $response = $this->client->sendRequest($request);
    return $response;
  }

  /**
   * Returns an array of name parts from "Lastname, Firstname Middlename"
   *
   * @param string $name
   *
   * @return array
   */
  public function parseName($name) {
    $name = str_replace(', ', ' ', $name);
    $parts = explode(' ', $name);
    return $parts;
  }

  // Does not return barcode so cannot start a new patron object.
  public function authenticate($barcode, $pin) {

    $this->barcode = $barcode;

    // The PIN is needed for subsequent PatronInformationRequest
    // ...so we need to store it. No other choice.
    $tempstore = \Drupal::service('tempstore.private')->get('intercept_ils_sip2');
    $tempstore->set('pin', $pin);

    $patron_info = $this->patronInformationRequest($barcode);
    $name = $this->parseName($patron_info->getPersonalName());
    $validPatron = $patron_info->getValidPatron();
    $validPatronPassword = $patron_info->getValidPatronPassword();
    $email = $patron_info->getEmailAddress();
    $phone = $patron_info->getHomePhoneNumber();
    if ($validPatron == 'Y' && $validPatronPassword == 'Y') {
      // This is a valid customer account.
      $this->PatronInfo = $patron_info;
      $this->NameFirst = $name[1];
      $this->NameLast = $name[0];
      $this->NameMiddle = $name[2];
      $this->PhoneVoice1 = $phone;
      $this->EmailAddress = $email;
      $this->Barcode = $barcode;
      // Return the updated patron object with info gleaned from SIP2 client.
      return $this;
    }
    // else if ($validPatron == 'Y') {
    //   The barcode is right but the PIN is wrong.
    // }
    // else if ($validPatronPassword == 'Y') {
    //   The barcode is wrong but the PIN is right.
    // }
    // else {
    //   This is not a customer account.
    // }
    return FALSE;
  }

  public function barcode() {
    return $this->barcode;
  }

  /**
   * Returns a basic data object of patron information.
   */
  public function basicData() {
    $patron_info = $this->patronInformationRequest($this->barcode);

    $data = new \stdClass();
    // First and last name come back in a single value so it'll need to be split.
    $name = $this->parseName($patron_info->getPersonalName());
    $data->NameLast = $name[0];
    $data->NameFirst = $name[1];
    $data->NameMiddle = $name[2];
    $data->Barcode = $this->barcode;
    $data->PhoneNumber = $patron_info->getHomePhoneNumber();
    $data->EmailAddress = $patron_info->getEmailAddress();
    $data->Username = $this->barcode;
    return $data;
  }

  public function get($barcode) {
    return new Entity($this, ['barcode' => $barcode]);
  }

  /**
   * Load a Patron object by a Drupal user object.
   *
   * @param \Drupal\user\UserInterface $user
   *   The User object.
   *
   * @return bool|static
   */
  public function getByUser(UserInterface $user) {
    $authmap = \Drupal::service('externalauth.authmap');
    $authdata = $authmap->getAuthData($user->id(), 'intercept_ils_sip2');
    if (empty($authdata['data'])) {
      return FALSE;
    }
    $authdata = unserialize($authdata['data']);
    return !empty($authdata->Barcode) ? $this->get($authdata->Barcode) : FALSE;
  }

  /**
   * Load a User object from a barcode.
   *
   * @param $barcode
   *
   * @return bool|\Drupal\user\Entity\User
   */
  public function getUserByBarcode($barcode) {
    $authmap = \Drupal::service('externalauth.authmap');
    if ($uid = $authmap->getUid($barcode, 'intercept_ils_sip2')) {
      return $this->client->entityTypeManager()->getStorage('user')->load($uid);
    }
    return FALSE;
  }

  public function searchAnd(array $array) {
    return NULL;
  }

  public function searchBasic(array $values = []) {
    return NULL;
  }

  /**
   * @alias for self::authenticate()
   */
  public function validate($barcode) {
    // Grab the PIN.
    $tempstore = \Drupal::service('tempstore.private')->get('intercept_ils_sip2');
    $pin = $tempstore->get('pin');
    return $this->authenticate($barcode, $pin);
  }
}
