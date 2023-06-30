(function ($, Drupal, window) {
  'use strict';
  Drupal.behaviors.viewsFiltersSummary = {
    attach: function (context) {

      /**
       * Reset form input to an empty value/default state.
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
       * Click the views exposed form submit button or reload the page if
       * filters existed in the URL.
       */
      function clickHandler() {
        let uri = window.location.toString();

        if (uri.indexOf("?") > 0) {
          let base_url = uri.substring(0, uri.indexOf("?"));
          window.history.replaceState({}, document.title, base_url);
          window.location.reload();
        } else {
          reset('form[id^="views-exposed-form"]');
          $('.views-exposed-form input[type="submit"]:nth-child(1)').trigger('click');
        }
      }

      /**
       * Remove a specific filter from the views exposed filters.
       */
      $('.views-filters-summary a.remove-filter', context).one('click', function (event) {
        event.preventDefault();
        let [selector, value] = $(this).data('removeSelector').split(':');
        let $input = $(`[name^="${selector}"]`);

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
          clickHandler()
        }
      });

      /**
       * Reset all views exposed form filters.
       */
      $('.views-filters-summary a.reset', context).one('click', function (event) {
        event.preventDefault();
        clickHandler()
      });
    },
  };
})(jQuery, Drupal, window);
