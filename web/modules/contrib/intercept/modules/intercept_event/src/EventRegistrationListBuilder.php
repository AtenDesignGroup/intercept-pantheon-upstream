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

  /**
   * The ILS client.
   *
   * @var object
   */
  protected $client;

  /**
   * Gets the plugin ID string.
   *
   * @var string
   */
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
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
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
    /** @var \Drupal\intercept_event\Entity\EventRegistrationInterface $entity */
    $row = [];

    $this->addEventRow($row, $entity);

    $row['name'] = $this->getRegistrantName($entity);

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
    if ($this->pluginId) {
      $authmap = \Drupal::service('externalauth.authmap');
      $authdata = $authmap->getAuthdata($uid, $this->pluginId);
      $authdata_data = unserialize($authdata['data']);

      return $authdata_data;
    }
    return NULL;
  }

  /**
   * Get user name for an Event Registration.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Event Registration entity.
   */
  protected function getRegistrantName(EntityInterface $entity) {
    /** @var \Drupal\intercept_event\Entity\EventRegistrationInterface $entity */
    if ($user = $entity->getRegistrant()) {
      if ($authdata = $this->getAuthdata($user->id())) {
        // Use the UID now to get the barcode and name of the customer.
        $email_link = Link::fromTextAndUrl($authdata->EmailAddress, Url::fromUri('mailto:' . $authdata->EmailAddress))->toString();
        return [
          'data' => [
            '#markup' => $authdata->NameFirst . ' ' . $authdata->NameLast . ' (' . $authdata->Barcode . ')<br>' . $authdata->PhoneNumber . ' ' . $email_link,
          ],
        ];
      }
      return $user->getDisplayName();
    }
    $name = [];
    if ($entity->hasField('field_guest_name') && $guest_name = $entity->field_guest_name->value) {
      $name[] = $guest_name;
    }
    elseif ($entity->hasField('field_guest_name_first') && $entity->hasField('field_guest_name_last')) {
      if ($entity->field_guest_name_first->value && $entity->field_guest_name_last->value) {
        $name[] = $entity->field_guest_name_first->value . ' ' . $entity->field_guest_name_last->value;
      }
      elseif ($entity->field_guest_name_first->value) {
        $name[] = $entity->field_guest_name_first->value;
      }
      elseif ($entity->field_guest_name_last->value) {
        $name[] = $entity->field_guest_name_last->value;
      }
    }
    if ($entity->hasField('field_guest_email') && $email = $entity->field_guest_email->value) {
      $name[] = Link::fromTextAndUrl($email, Url::fromUri('mailto:' . $email))->toString();
    }
    if ($entity->hasField('field_guest_phone_number') && $phone = $entity->field_guest_phone_number->value) {
      $name[] = $phone;
    }
    if (!empty($name)) {
      return [
        'data' => [
          '#markup' => implode('<br />', $name),
        ],
      ];
    }
    return $this->t('Unknown customer');
  }

}
