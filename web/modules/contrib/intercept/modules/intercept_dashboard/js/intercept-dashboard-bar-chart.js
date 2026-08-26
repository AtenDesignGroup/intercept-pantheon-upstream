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

          // Wrap long labels on the y-axis
          data.options.scales.y.ticks.callback = function (value, index, ticks) {
            const label = this.getLabelForValue(value);
            if (label.length > 20) {
              const words = label.match(/\([^)]*\)|\S+/g) || [];

              return words.reduce((lines, word) => {
                const lastLine = lines[lines.length - 1];

                if (lastLine && `${lastLine} ${word}`.length <= 20) {
                  lines[lines.length - 1] = `${lastLine} ${word}`;
                }
                else {
                  lines.push(word);
                }

                return lines;
              }, []);
            }
            return label;
          };

          data.options.onResize = (chart, size) => {
            const mobileView = size.width < 768;
            const newSize = mobileView ? 11 : 16;

            // Update tick font size dynamically on resize
            if (chart.options.scales.y.ticks.font.size !== newSize) {
              chart.options.scales.y.ticks.font.size = newSize;
            }
          }

          Drupal.Charts.Contents.update(id, data);
        });
      });
    },
  };
}(Drupal));
