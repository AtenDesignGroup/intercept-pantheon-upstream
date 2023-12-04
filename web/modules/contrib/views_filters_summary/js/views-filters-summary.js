(function ($, Drupal, window) {
  'use strict';
  Drupal.behaviors.viewsFiltersSummary = {
    attach: function (context) {

      /**
       * Reset all form inputs to an empty value/default state.
       */
      function reset(selector) {
        $(selector).find(':input').each(function () {
          switch (this.type) {
            case 'password':
            case 'select-multiple':
            case 'select-one':
            case 'text':
            case 'textarea':
              $(this).val('');
              break;
            case 'checkbox':
            case 'radio':
              this.checked = false;
              break;
          }
        });
      }

      /**
       * Check if the views exposed form uses AJAX.
       * @returns {boolean|jQuery|*}
       */
      function usesAjax() {
        return $('.views-filters-summary', context)
          .hasClass('views-filters-summary--use-ajax');
      }

      /**
       * Remove a specific filter from the views exposed filters.
       *
       * @param {Event} MouseEvent
       *   The click event.
       */
      function onRemoveClick(event) {
        event.preventDefault();
        const [selector, value] = $(this).data('removeSelector').split(':');

        // If there are filters in the URL, remove the specific key value pair
        // and refresh the page.
        if (window.location.search.length > 0) {
          const url = new URL(window.location);
          // Remove the single value selector.
          url.searchParams.delete(selector);
          // Remove keyed element.
          url.searchParams.delete(`${selector}[${value}]`);
          // Remove the multi value selector, then add back in the ones that don't match the value.
          const multiValueParams = url.searchParams.getAll(`${selector}[]`);
          multiValueParams.forEach((param) => {
            if (param !== value) {
              url.searchParams.append(`${selector}[]`, param);
            }
          });
          window.location = url.toString();
        }
        // Else, clear the specific input and submit the form.
        else {
          const $input = $(`[name^="${selector}"]`);

          if ($input !== undefined) {
            if ($input.is('input')) {
              switch ($input.attr('type')) {
                case 'radio':
                case 'checkbox':
                  $input.filter(`[value="${value}"]`).prop('checked', false);
                  break;
                default:
                  $input.val('');
                  break;
              }
            } else {
              $input.children(`[value="${value}"]`).prop('selected', false);
            }
            $('.views-exposed-form input[type="submit"]:nth-child(1)').trigger('click');
          }
        }
      }

      /**
       * Reset all views exposed form filters.
       *
       * @param {Event} MouseEvent
       *   The click event.
       */
      function onResetClick(event) {
        event.preventDefault();
        let uri = window.location.toString();
        if (window.location.search) {
          let base_url = uri.substring(0, uri.indexOf("?"));
          window.history.pushState({}, "", base_url);
          window.location.reload();
        } else {
          reset('form[id^="views-exposed-form"]');
          $('.views-exposed-form input[type="submit"]:nth-child(1)').trigger('click');
        }
      }

      /**
       * Bind the remove and reset click events.
       */
      $('.views-filters-summary a.remove-filter', context).one('click', onRemoveClick);
      $('.views-filters-summary a.reset', context).one('click', onResetClick);
    },
  };
})(jQuery, Drupal, window);
