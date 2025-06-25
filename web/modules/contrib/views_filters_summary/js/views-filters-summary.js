((Drupal, once) => {
  Drupal.behaviors.viewsFiltersSummary = {
    attach(context) {
      /**
       * Reset all exposed form inputs to an empty value/default state.
       *
       * @param {Element} exposedForm
       *   The exposed form to reset.
       */
      function reset(exposedForm) {
        const inputs = exposedForm.querySelectorAll('input');
        inputs.forEach((input) => {
          switch (input.type) {
            case 'password':
            case 'select-multiple':
            case 'select-one':
            case 'text':
            case 'textarea':
            case 'date':
              input.value = '';
              break;
            case 'checkbox':
            case 'radio':
              input.checked = false;
              break;
            case 'hidden':
              // BEF links widget handling.
              input.remove();
              break;
            default:
              break;
          }
        });
        const selects = exposedForm.querySelectorAll('select');
        selects.forEach((select) => {
          Array.from(select.options).forEach((option) => {
            option.selected = false;
          });
        });
      }

      /**
       * Check if the views exposed form uses AJAX.
       *
       * @return {boolean}
       *   true if Ajax is used, false otherwise.
       */
      function usesAjax() {
        const summary = context.querySelector('.views-filters-summary');
        return summary.classList.contains('views-filters-summary--use-ajax');
      }

      /**
       * Returns the current view exposed form.
       *
       * @return {Element|*}
       *   The view exposed form.
       */
      function getExposedForm() {
        const summaryElt = context.querySelector('[data-exposed-form-id]');
        const exposedFormId = summaryElt.getAttribute('data-exposed-form-id');
        let form = context.querySelector(`form[id^="${exposedFormId}"]`);
        if (!form) {
          // When the form is not found in the context (e.g. as a block), use document instead.
          form = document.querySelector(`form[id^="${exposedFormId}"]`);
        }
        return form;
      }

      /**
       * Returns the exposed form filter button.
       *
       * @param {Element} exposedForm
       *   The exposed form jQuery object.
       * @return {Element|*}
       *   The exposed form filter button.
       */
      function getFilterSubmit(exposedForm) {
        // Some exposed forms can have multiple "filter" buttons.
        return exposedForm.querySelector(
          ':is(button, input)[type="submit"]:first-of-type',
        );
      }

      /**
       * Execute exposed form Ajax submit.
       */
      function ajaxSubmit() {
        const exposedForm = getExposedForm();
        const submit = getFilterSubmit(exposedForm);
        submit.click();
      }

      /**
       * Simulate a form submit using history and reload.
       *
       * @param {Element} exposedForm
       *   The exposed form element.
       */
      function historySubmit(exposedForm) {
        const formData = new FormData(exposedForm); // Gather form data.
        const { action } = exposedForm; // Get the action URL.
        const params = new URLSearchParams(formData).toString(); // Convert form data to query string.
        const url = action + (action.includes('?') ? '&' : '?') + params;
        window.history.replaceState({}, document.title, url);
        window.location.reload();
      }

      /**
       * Click the views exposed form submit button or reload the page if
       * filters existed in the URL.
       *
       * @param {MouseEvent} event
       *   The click event.
       */
      function onRemoveClick(event) {
        event.preventDefault();
        const removeSelector = this.getAttribute('data-remove-selector');
        const colonIndex = removeSelector.indexOf(':');
        const selector = removeSelector.substring(0, colonIndex);
        const value = removeSelector.substring(colonIndex + 1);
        const form = getExposedForm();
        const inputs = form.querySelectorAll(`[name^="${selector}"]`);
        inputs.forEach((input) => {
          if (input.tagName === 'INPUT') {
            switch (input.type) {
              case 'radio':
              case 'checkbox':
                if (input.value === value) {
                  input.checked = false;
                }
                break;
              case 'hidden':
                // BEF links widget handling.
                if (input.value === value) {
                  input.remove();
                }
                break;
              default:
                // Handle the special case of an autocomplete field.
                if (input.hasAttribute('data-autocomplete-path')) {
                  const originalValues = input.value.split(',');
                  const updatedValues = originalValues.filter((v) => {
                    const match = v.match(/.+\s\(([^)]+)\)/);
                    return match && match[1] !== value;
                  });
                  input.value = updatedValues.join(', ');
                } else {
                  input.value = '';
                }
                break;
            }
          } else {
            input.querySelectorAll(`[value="${value}"]`).forEach((element) => {
              element.selected = false;
            });
          }
        });
        if (usesAjax()) {
          ajaxSubmit();
        } else {
          historySubmit(form);
        }
      }

      /**
       * Reset all views exposed form filters.
       *
       * @param {MouseEvent} event
       *   The click event.
       */
      function onResetClick(event) {
        event.preventDefault();
        const form = getExposedForm();
        reset(form);
        if (usesAjax()) {
          ajaxSubmit();
        } else {
          historySubmit(form);
        }
      }

      /**
       * Bind the remove and reset click events.
       */
      once(
        'views-filters-summary-remove-filter',
        '.views-filters-summary a.remove-filter',
        context,
      ).forEach((element) => {
        element.addEventListener('click', onRemoveClick);
      });
      once(
        'views-filters-summary-reset',
        '.views-filters-summary a.reset',
        context,
      ).forEach((element) => {
        element.addEventListener('click', onResetClick);
      });
    },
  };
})(Drupal, once);
