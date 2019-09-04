<?php

namespace Drupal\intercept_core\Utility;

class Obfuscate {

  public static function email($email) {
    if (empty($email)) {
      return '';
    }
    $pos = strpos($email, '@');
    return substr_replace($email, str_repeat('*', $pos - 1), 1, $pos - 1);
  }

  protected static function barcode($barcode) {
    if (empty($barcode)) {
      return '';
    }
    $replace = str_repeat('*', strlen($barcode) - 4);
    return substr_replace($barcode, $replace, 0, strlen($barcode) - 4);
  } 
}
