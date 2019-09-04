<?php

namespace Drupal\intercept_event;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Event Registration entities.
 *
 * @ingroup intercept_event
 */
class EventRegistrationListBuilder extends EntityListBuilder {

  use EventListBuilderTrait;

  protected $client;

  protected $pluginId;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new EventRegistrationListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatter $date_formatter) {
    parent::__construct($entity_type, $storage);

    $config_factory = \Drupal::service('config.factory');
    $settings = $config_factory->get('intercept_ils.settings');
    $intercept_ils_plugin = $settings->get('intercept_ils_plugin', '');
    if ($intercept_ils_plugin) {
      $ils_manager = \Drupal::service('plugin.manager.intercept_ils');
      $ils_plugin = $ils_manager->createInstance($intercept_ils_plugin);
      $this->client = $ils_plugin->getClient();
      $this->pluginId = $intercept_ils_plugin;
    }
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $this->addEventHeader($header);
    $header['name'] = $this->t('Customer');
    $header['count'] = $this->t('Total');
    $header['status'] = $this->t('Status');
    $header['user'] = $this->t('Registered By');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $authdata = [];
    $this->addEventRow($row, $entity);
    $user = $entity->get('field_user')->entity;
    $guest_name = $entity->get('field_guest_name')->getValue();
    if ($user) {
      // Use the $entity information to pull the customer's actual name instead of
      // the name of the event registration.
      $uid = $entity->get('field_user')->entity->id();
      // Use the UID now to get the barcode and name of the customer.
      $authdata = $this->getAuthdata($uid);
    }
    if ($authdata) {
      $email_link = Link::fromTextAndUrl($authdata->EmailAddress, Url::fromUri('mailto:' . $authdata->EmailAddress))->toString();
      $row['name'] = [
        'data' => [
          '#markup' => $authdata->NameFirst . ' ' . $authdata->NameLast . ' (' . $authdata->Barcode . ')<br>' . $authdata->PhoneNumber . ' ' . $email_link,
        ],
      ];
    }
    else if (!empty($uid)) {
      // Backup info can come from $user if it's a non-customer.
      $user = \Drupal\user\Entity\User::load($uid);
      $row['name'] = $user->getUsername();
    }
    else if (!empty($guest_name)) {
      $guest_email = $entity->get('field_guest_email')->getValue();
      $guest_phone_number = $entity->get('field_guest_phone_number')->getValue();
      $email_link = Link::fromTextAndUrl($guest_email[0]['value'], Url::fromUri('mailto:' . $guest_email[0]['value']))->toString();
      $row['name'] = [
        'data' => [
          '#markup' => $guest_name[0]['value'] . '<br>' . $guest_phone_number[0]['value'] . ' ' . $email_link,
        ],
      ];
    }
    else {
      $row['name'] = 'Unknown customer';
    }

    $row['count'] = $entity->total();
    $row['status'] = $entity->status->getString();
    $row['user'] = strip_tags($this->getUserLink($entity));
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'custom', 'm-d-Y g:i A');

    return $row + parent::buildRow($entity);
  }

  /**
   * Get authdata for user in the row in order to display customer info.
   */
  protected function getAuthdata($uid) {
    $debug = true;
    if ($this->pluginId) {
      $authmap = \Drupal::service('externalauth.authmap');
      $authdata = $authmap->getAuthdata($uid, $this->pluginId);
      $authdata_data = unserialize($authdata['data']);
      if (isset($authdata_data->Barcode)) {
        $barcode = $authdata_data->Barcode;
        $result = $this->client->patron->searchByBarcode($barcode);
      }
      return $authdata_data;
    }
    return null;
  }

}
