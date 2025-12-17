/**
 * @file
 * JavaScript integration between Billboard and Drupal.
 */

/* global bb */
(function (Drupal, once) {
  Drupal.behaviors.chartsBillboard = {
    attach(context) {
      const contents = new Drupal.Charts.Contents();
      once('charts-billboard', '.charts-billboard', context).forEach(
        function (element) {
          const config = contents.getData(element.id);
          if (config.title && config.title.text) {
            const title = config.title.text;
            // If the title contains '\\n', convert it to a line break.
            if (title.includes('\\n')) {
              config.title.text = title.replace(/\\n/g, '\n');
            }
          }
          bb.generate(config);
          if (
            element.nextElementSibling &&
            element.nextElementSibling.hasAttribute(
              'data-charts-debug-container',
            )
          ) {
            element.nextElementSibling.querySelector('code').innerText =
              JSON.stringify(config, null, ' ');
          }
        },
      );
    },
  };
})(Drupal, once);
