<?php

namespace Drupal\date_popup;

/**
 * A static class that allows adding a date popup to a form.
 */
class DatePopupHelper {

  use DatePopupTrait;

  /**
   * Expose the protected applyDatePopupToForm method, with view options.
   *
   * @param array $form
   *   The form array to which date popup(s) should be added.
   * @param array $options
   *   View options configurations.
   */
  public static function applyDatePopup(array &$form, array $options): void {
    static::applyDatePopupToForm($form, $options);
  }

}
