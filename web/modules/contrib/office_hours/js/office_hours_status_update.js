(function updateOfficeHoursField(Drupal, drupalSettings, once) {
  function update(element) {
    const statusMetadata = JSON.parse(
      element.getAttribute('js-office-hours-status-data'),
    );

    // Make sure that the field is not generated again, if this was an
    // original request, not a refresh (<10 seconds ago).
    const refreshTime = Math.floor(Date.now() / 1000);
    if (refreshTime - statusMetadata.request_time < 10) {
      return;
    }

    const parentClass = [
      'js-office-hours',
      statusMetadata.entity_type,
      statusMetadata.entity_id,
      statusMetadata.field_name,
    ].join('-');

    // eslint-disable-next-line no-useless-concat
    const url = [
      `${
        drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix
      }office_hours/status_update`,
      statusMetadata.entity_type,
      statusMetadata.entity_id,
      statusMetadata.field_name,
      statusMetadata.langcode,
      statusMetadata.view_mode,
    ].join('/');

    element.parentElement.classList.add(parentClass);

    fetch(url)
      .then((response) => response.text())
      .then((html) => {
        element.innerHTML = html;
      })
      .catch(() => {});
  }

  Drupal.behaviors.office_hours_status_update = {
    attach: function doUpdateStatus() {
      const statusElements = once(
        'office_hours_status_update',
        '[js-office-hours-status-data]',
      );
      statusElements.forEach(update);
    },
  };
})(Drupal, drupalSettings, once);
