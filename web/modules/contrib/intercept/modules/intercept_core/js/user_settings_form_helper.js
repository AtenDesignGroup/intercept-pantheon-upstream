/**
 * @file
 * Provides JavaScript for user settings form.
 */

(function ($, Drupal) {

/**
 * Provides JS helper functions for user settings form.
 */
Drupal.behaviors.userSettingsFormHelper = {
  attach: function (context, settings) {

    // Move the PIN field into the correct position.
    $('.form-item-pin').detach().appendTo('#edit-customer-profile-field-ils-username-wrapper');

  },
};

})(jQuery, Drupal);
