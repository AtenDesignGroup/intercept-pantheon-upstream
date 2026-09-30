((Drupal, once) => {
  Drupal.behaviors.flagsSummary = {
    attach(context) {
      once(
        'flags-summary',
        'details[data-drupal-selector="edit-flag"]',
        context,
      ).forEach((details) => {
        const summary = details.querySelector('span[class*="summary"]');

        if (!summary) {
          return;
        }

        const updateSummary = () => {
          const checked = details.querySelectorAll(
            'input[type="checkbox"]:checked',
          );

          summary.textContent = checked.length
            ? ` ${Array.from(checked)
                .map((checkbox) => checkbox.title)
                .join(', ')}`
            : ` ${Drupal.t('No flags')}`;
        };

        details.addEventListener('change', updateSummary);
        updateSummary();
      });
    },
  };
})(Drupal, once);
