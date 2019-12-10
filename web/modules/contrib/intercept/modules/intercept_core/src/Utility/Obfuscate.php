<?php

namespace Drupal\intercept_core\Utility;

/**
 * A helper utility class for obfuscating strings.
 */
class Obfuscate {

  /**
   * Obfuscates email strings.
   *
   * @param string $email
   *   The email string.
   *
   * @return string
   *   The obfuscated email string.
   */
  public static function email($email) {
    if (empty($email)) {
      return '';
    }
    $pos = strpos($email, '@');
    return substr_replace($email, str_repeat('*', $pos - 1), 1, $pos - 1);
  }

  /**
   * Obfuscates barcode strings.
   *
   * @param string $barcode
   *   The barcode string.
   *
   * @return string
   *   The obfuscated barcode string.
   */
  protected static function barcode($barcode) {
    if (empty($barcode)) {
      return '';
    }
    $replace = str_repeat('*', strlen($barcode) - 4);
    return substr_replace($barcode, $replace, 0, strlen($barcode) - 4);
  }

}
