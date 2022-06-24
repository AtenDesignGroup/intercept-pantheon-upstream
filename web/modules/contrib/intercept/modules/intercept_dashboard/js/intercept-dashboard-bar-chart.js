/* eslint-disable prefer-arrow-callback */
/* eslint-disable no-param-reassign */
/* eslint-disable func-names */
(function (Drupal) {
  Drupal.intercept_dashboard_barchart = Drupal.intercept_dashboard_barchart || {};

  Drupal.behaviors.interceptDashboardBarChart = {
    attach(context) {
      const chartcontainers = context.querySelectorAll('.intercept-dashboard-chart [data-chart]');

      chartcontainers.forEach(function (element) {

        element.addEventListener('drupalChartsConfigsInitialization', function (event) {
          const data = event.detail;
          const id = data.drupalChartDivId;

          data.options.scales.x.ticks.callback = function (value, index, ticks) {
            // Only label integers
            return value % 1 === 0 ? this.getLabelForValue(value) : '';
          };

          Drupal.Charts.Contents.update(id, data);
        });
      });
    },
  };
}(Drupal));
