<?php

namespace Drupal\intercept_core\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\intercept_ils\ILSManager;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\ProfileForm;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends the core user profile form.
 */
class UserProfileForm extends ProfileForm {

  /**
   * The profile entity.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $profileEntity;

  /**
   * ILS client object.
   *
   * @var object
   */
  private $client;

  /**
   * ILS plugin object.
   *
   * @var object
   */
  protected $interceptILSPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, ILSManager $ils_manager) {
    // Pass necessary info to parent constructor at \Drupal\user\AccountForm.
    parent::__construct($entity_repository, $language_manager, $entity_type_bundle_info, $time);

    $settings = $config_factory->get('intercept_ils.settings');
    $intercept_ils_plugin = $settings->get('intercept_ils_plugin', '');
    if ($intercept_ils_plugin) {
      $this->interceptILSPlugin = $ils_manager->createInstance($intercept_ils_plugin);
      $this->client = $this->interceptILSPlugin->getClient();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('language_manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.intercept_ils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = $form_state->getFormObject()->getEntity();
    $form['customer_profile'] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => 'profile',
      '#bundle' => 'customer',
      '#form_mode' => 'customer',
      '#save_entity' => TRUE,
      '#default_value' => $this->getProfileEntity($user),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Allows the profile form to be altered.
   *
   * @param array $entity_form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function alterProfileForm(array &$entity_form, FormStateInterface $form_state) {
    $user = $form_state->getFormObject()->getEntity();
    $profile = $this->getProfileEntity($user);
    if ($this->getInlineEntityFormDisplay($profile, $entity_form['#form_mode'])->getComponent('pin')) {
      $entity_form['pin']['#type'] = 'password';
      $entity_form['pin']['#title'] = $this->t('PIN');
      // Turn off autofill for username and PIN fields so that browser
      // doesn't fill these in if the customer doesn't want to change them.
      $entity_form['pin']['#attributes'] = [
        'autocomplete' => 'new-password',
        'class' => ['field--pin'],
      ];
      // Reposition the PIN field.
      $entity_form['pin']['#attached'] = [
        'library' => ['intercept_core/user_settings_form_helper'],
      ];
    }
    // Set the default values for profile and add a save handler for the pin.
    if ($this->client && $patron = $this->client->patron->getByUser($user)) {
      $this->populateName($entity_form, $patron, $profile);
      $form_state->set('patron', $patron);
      $entity_form['field_barcode']['widget'][0]['value']['#default_value'] = $patron->barcode;

      if (isset($patron->basicData()->Username)) {
        $entity_form['field_ils_username']['widget'][0]['value']['#default_value'] = $patron->basicData()->Username;
      }
      $entity_form['field_phone']['widget'][0]['value']['#default_value'] = $patron->basicData()->PhoneNumber ?? '';
      $entity_form['field_email_address']['widget'][0]['value']['#default_value'] = $patron->basicData()->EmailAddress ?? '';
      $entity_form['#element_validate'][] = [$this, 'validateInlineEntityForm'];
      $entity_form['#ief_element_submit'][] = [$this, 'saveInlineEntityForm'];
      foreach (['field_first_name', 'field_last_name'] as $field) {
        $entity_form[$field]['widget'][0]['#disabled'] = TRUE;
      }
      $this->populateAddress($patron, $entity_form['field_address']);
    }
    $entity_form['field_barcode']['widget']['#disabled'] = TRUE;
  }

  /**
   * Set the default first and last name values for the patron.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param object $patron
   *   An object representing the patron.
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The Profile entity.
   */
  private function populateName(array &$form, $patron, ProfileInterface $profile) {
    if ($patron->getFirstName() != $profile->get('field_first_name')->getString()) {
      $form['field_first_name']['widget'][0]['value']['#default_value'] = $patron->getFirstName();
    }
    if ($patron->getLastName() != $profile->get('field_first_name')->getString()) {
      $form['field_last_name']['widget'][0]['value']['#default_value'] = $patron->getLastName();
    }
  }

  /**
   * Set the default address for the patron.
   *
   * @param object $patron
   *   An object representing the patron.
   * @param array $address_field
   *   The form array for the address field.
   */
  protected function populateAddress($patron, array &$address_field) {
    $data = $patron->data();
    if (!empty($data->PatronAddresses[0])) {
      $address = $data->PatronAddresses[0];
      $address_field = &$address_field['widget'][0]['address'];
      $address_field['#default_value']['country_code'] = 'US';
      $replacements = [
        'address_line1' => 'StreetOne',
        'postal_code' => 'PostalCode',
        'locality' => 'City',
        'administrative_area' => 'State',
      ];
      foreach ($replacements as $drupal => $ils) {
        $address_field['#default_value'][$drupal] = $address->{$ils};
      }

      $address_field['#disabled'] = TRUE;
    }
  }

  /**
   * Returns the entity_form_display object used to build an entity form.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $entity
   *   The entity for which the form is being built.
   * @param string $view_mode
   *   The form mode.
   */
  protected function getInlineEntityFormDisplay(ProfileInterface $entity, $view_mode) {
    return EntityFormDisplay::collectRenderDisplay($entity, $view_mode);
  }

  /**
   * Custom validation callback for UserProfileForm.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateInlineEntityForm(array &$form, FormStateInterface $form_state) {
    $patron = $form_state->get('patron');
    // Update ILS username if requested.
    $ils_username = $form_state->cleanValues()->getValue([
      'customer_profile',
      'field_ils_username',
    ]);
    $ils_username = $ils_username[0]['value'];
    $response = $patron->updateUsername($ils_username);
    if (isset($response->PAPIErrorCode)) {
      if ($response->PAPIErrorCode == -3607) {
        $form_state->setError($form['field_ils_username'], 'The username you entered is unavailable. Please try another username.');
      }
      elseif ($response->PAPIErrorCode == -3606) {
        $form_state->setError($form['field_ils_username'], 'The username must be at least 4 characters but not longer than 50 characters.');
      }
    }
  }

  /**
   * Custom submit callback for #ief_element_submit.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function saveInlineEntityForm(array &$form, FormStateInterface $form_state) {
    $pin = $form_state->cleanValues()->getValue('pin');
    $email_address = $form_state->cleanValues()->getValue([
      'customer_profile',
      'field_email_address',
    ]);
    $email_address = $email_address[0]['value'];
    $phone = $form_state->cleanValues()->getValue([
      'customer_profile',
      'field_phone',
    ]);
    $phone = $phone[0]['value'];
    if (!empty($pin) || !empty($phone) || !empty($email_address)) {
      // If $patron is empty, this submit handler is never set.
      $patron = $form_state->get('patron');
      if (!empty($pin)) {
        $patron->Password = $pin;
        // Also update Drupal password to match.
        $user = $form_state->getFormObject()->getEntity();
        $user->setPassword($pin);
        $user->save();
      }
      if (!empty($phone)) {
        $patron->PhoneVoice1 = $phone;
      }
      if (!empty($email_address)) {
        $patron->EmailAddress = $email_address;
      }
      $patron->update();

      if (!empty($this->interceptILSPlugin)) {
        $plugin_id = $this->interceptILSPlugin->getId();
        // Also update externalauth authdata.
        $user = $form_state->getFormObject()->getEntity();
        $authmap = \Drupal::service('externalauth.authmap');

        // Update the authdata & user account based on the latest ILS info.
        if ($patron = $this->client->patron->getByUser($user)) {
          $plugin_id = $this->interceptILSPlugin->getId();
          $authmap->save($user, $plugin_id, $patron->barcode(), $patron->basicData());
        }
      }
    }
  }

  /**
   * Gets the profile entity for a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The Drupal user entity.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The profile entity.
   */
  protected function getProfileEntity(UserInterface $user) {
    $profile_storage = $this->entityTypeManager->getStorage('profile');
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile = $profile_storage->loadDefaultByUser($user, 'customer');
    if (!$profile) {
      $profile = $profile_storage->create([
        'type' => 'customer',
        'uid' => $user->id(),
      ]);
    }
    $this->profileEntity = $profile;
    return $profile;
  }

  /**
   * Helper function to compare entities by Name.
   *
   * @param object $a
   *   The first object.
   * @param object $b
   *   The second object.
   *
   * @return int
   *   The result of strcmp().
   */
  public function locationsSort($a, $b) {
    return strcmp($a->Name, $b->Name);
  }

}
