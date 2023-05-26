/**
 * @file
 * Prevents date picker month selection from auto submitting an AJAX form.
 *
 */

(function ($, Drupal) {
  Drupal.behaviors.intercept_material_pickadate = {
    attach: function (context, settings) {
      $(context).find('.form-type-date').once('intercept_material_pickadate').each(function () {
        $(this).on('change', function(e) {
          e.stopPropagation();
        });
      });
    }
  };
}(jQuery, Drupal));
