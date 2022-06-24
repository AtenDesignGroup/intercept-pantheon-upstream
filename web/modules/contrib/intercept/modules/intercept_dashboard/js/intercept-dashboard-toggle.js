/* eslint-disable prefer-arrow-callback */
/* eslint-disable no-param-reassign */
/* eslint-disable func-names */
(function (Drupal) {
  Drupal.behaviors.interceptDashboardtoggle = {
    attach(context) {
      const chartToggles = context.querySelectorAll('.js-intercept-chart-toggle');

      chartToggles.forEach((element) => {
        element.addEventListener('click', function(event) {
          const button = event.currentTarget;
          const tableElement = document.getElementById(button.getAttribute('aria-controls'));

          if (button.getAttribute('data-action') === 'show-table') {
            tableElement.classList.remove('visually-hidden');
            button.innerHTML = Drupal.t('Hide Table');
            button.setAttribute('data-action', 'hide-table');
          }
          else {
            tableElement.classList.add('visually-hidden');
            button.innerHTML = Drupal.t('Show Table');
            button.setAttribute('data-action', 'show-table');
          }
        });
      });
    },
  };
}(Drupal));
