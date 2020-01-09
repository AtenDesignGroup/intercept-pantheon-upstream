<?php

namespace Drupal\intercept_event;

/**
 * Provides form functions for customer searching.
 */
trait CustomerSearchFormTrait {

  /**
   * Convert form element keys if they differ from the search query.
   *
   * @return array
   *   The form element.
   */
  protected function mapValues(array $values) {
    $output = [];
    foreach ($this->map() as $key => $value) {
      if (empty($values[$key])) {
        continue;
      }
      $output[$value] = $values[$key];
    }
    return $output;
  }

  /**
   * Search client for first, last and email.
   *
   * @return array
   *   The customer value array.
   */
  protected function searchQuery(array $values = []) {
    $query = [];
    if (!empty($values['first_name'])) {
      $query['PATNF'] = $values['first_name'] . '*';
    }
    if (!empty($values['last_name'])) {
      $query['PATNL'] = $values['last_name'] . '*';
    }
    if (!empty($values['email'])) {
      $query['EM'] = $values['email'];
    }
    if (!empty($values['barcode'])) { 
      $query['PATB'] = $values['barcode'];
    }
    if ($this->client()) {
      $results = $this->client()->patron->searchAnd($query);
    }
    else {
      return [];
    }
    return !empty($results->PatronSearchRows) ? $results->PatronSearchRows : [];
  }

  /**
   * Get ILS Client.
   *
   * @return object
   *   The ILS client.
   */
  protected function client() {
    $config_factory = \Drupal::service('config.factory');
    $settings = $config_factory->get('intercept_ils.settings');
    $intercept_ils_plugin = $settings->get('intercept_ils_plugin', '');
    if ($intercept_ils_plugin) {
      $ils_manager = \Drupal::service('plugin.manager.intercept_ils');
      $ils_plugin = $ils_manager->createInstance($intercept_ils_plugin);
      $client = $ils_plugin->getClient();
      return $client;
    }
    return FALSE;
  }

  /**
   * Build form element "tableselect" and populate options.
   *
   * @param array $results
   *   The results array.
   *
   * @return array
   *   The form element array.
   */
  protected function buildTableElement(array $results = []) {
    $element = [
      '#type' => 'tableselect',
      '#multiple' => FALSE,
      '#header' => [
        'name' => $this->t('Name'),
        'barcode' => $this->t('Barcode'),
        'email' => $this->t('Email'),
      ],
      '#options' => [],
      '#empty' => $this->t('No results found'),
    ];
    foreach ($results as $result) {
      $patron = $this->client()->patron->get($result->Barcode);
      $element['#options'][$result->Barcode] = [
        'name' => $this->formatName($result->PatronFirstLastName),
        'barcode' => $this->obfuscateBarcode($result->Barcode),
        'email' => $this->obfuscateEmail($patron->basicData()->EmailAddress),
      ];
    }
    return $element;
  }

  /**
   * Obfuscate the email.
   *
   * @param string $email
   *   The email to obfuscate.
   *
   * @return string
   *   The obfuscated email.
   */
  protected function obfuscateEmail($email) {
    if (empty($email)) {
      return '';
    }
    $pos = strpos($email, '@');
    return substr_replace($email, str_repeat('*', $pos - 1), 1, $pos - 1);
  }

  /**
   * Obfuscate the barcode.
   *
   * @param string $barcode
   *   The barcode to obfuscate.
   *
   * @return string
   *   The obfuscated barcode.
   */
  protected function obfuscateBarcode($barcode) {
    if (empty($barcode)) {
      return '';
    }
    $replace = str_repeat('*', strlen($barcode) - 4);
    return substr_replace($barcode, $replace, 0, strlen($barcode) - 4);
  }

  /**
   * Format ILS-returned name to readable.
   *
   * @param string $name
   *   The name to format.
   *
   * @return string
   *   The formatted ILS name.
   */
  protected function formatName($name) {
    if (empty($name)) {
      return '';
    }
    $name = array_reverse(explode(',', $name));
    $name = array_map('trim', $name);
    return implode(' ', $name);
  }

}
