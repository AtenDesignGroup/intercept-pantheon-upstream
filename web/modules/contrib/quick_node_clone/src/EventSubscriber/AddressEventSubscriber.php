<?php

namespace Drupal\quick_node_clone\EventSubscriber;

use Drupal\address\Event\InitialValuesEvent;
use Drupal\address\Event\AddressEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Support for cloning address data.
 *
 * Provides an event subscriber to add initial values to address fields when
 * cloning. This method is needed because of the way address handles its fields,
 * otherwise we would be doing this* sort of thing inside the form builder when
 * cloning.
 */
class AddressEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events = [];

    if (!class_exists('\Drupal\address\Event\AddressEvents')) {
      return $events;
    }
    $events[AddressEvents::INITIAL_VALUES][] = ['onInitialValues'];
    return $events;
  }

  /**
   * Generate a set of initial values.
   *
   * @return array
   *   The initial values.
   */
  public function getInitialValues($event) {
    $tempstore = \Drupal::service('user.private_tempstore')->get('quick_node_clone');

    if ($tempstore->get('address_initial_value_delta') == NULL) {
      $tempstore->set('address_initial_value_delta', 0);
    }

    $node = \Drupal::service('quick_node_clone.node_finder')->findNodeFromCurrentPath();

    if ($node == NULL) {
      return [];
    }

    $address = [];

    $delta = $tempstore->get('address_initial_value_delta');

    foreach ($node->getFieldDefinitions() as $field_definition) {
      $field_storage_definition = $field_definition->getFieldStorageDefinition();
      $field_settings = $field_storage_definition->getSettings();
      $field_name = $field_storage_definition->getName();

      if ($field_storage_definition->getType() == "address") {

        if (!$node->get($field_name)->isEmpty()) {

          foreach ($node->get($field_name) as $key => $value) {
            if ($key == $delta) {
              $address = [
                'country_code' => $value->getCountryCode(),
                'postal_code' => $value->getPostalCode(),
                'administrative_area' => $value->getAdministrativeArea(),
                'locality' => $value->getLocality(),
                'dependent_locality' => $value->getDependentLocality(),
                'sorting_code' => $value->getSortingCode(),
                'address_line1' => $value->getAddressLine1(),
                'address_line2' => $value->getAddressLine2(),
                'organization' => $value->getOrganization(),
                'additional_name' => $value->getAdditionalName(),
                'given_name' => $value->getGivenName(),
                'family_name' => $value->getFamilyName(),
              ];
            }

          }
        }
      }
    }

    $delta++;
    $tempstore->set('address_initial_value_delta', $delta);
    return $address;
  }

  /**
   * Alters the initial values.
   *
   * @param \Drupal\address\Event\InitialValuesEvent $event
   *   The initial values event.
   */
  public function onInitialValues(InitialValuesEvent $event) {
    $event->setInitialValues($this->getInitialValues($event));
  }

}
