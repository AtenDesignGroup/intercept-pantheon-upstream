(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.office_hours_status_update = {
    attach: function doUpdateStatus(context, settings) {
      const statusElements = once('office_hours_status_update', '[data-drupal-office-hours-status]');

      statusElements.forEach(function (element) {
        const statusMetadata = JSON.parse(element.getAttribute('data-drupal-office-hours-status'));
        const parentClass = [
          'js-office-hours', statusMetadata.entity_type, statusMetadata.entity_id, statusMetadata.field_name,
        ].join('-');
        const url = [
          drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix + 'office_hours/status_update',
          statusMetadata.entity_type, statusMetadata.entity_id, statusMetadata.field_name, statusMetadata.langcode, statusMetadata.view_mode,
        ].join('/');

        element.parentElement.classList.add(parentClass);

        fetch(url).then(function (response) {
          return response.text();
        }).then(function (html) {
          element.innerHTML = html;
        }).catch(function () {
        });
      });
    }
  };
})(Drupal, drupalSettings, once);
