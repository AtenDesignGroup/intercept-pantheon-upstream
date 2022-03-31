/**
 * @file
 * Contains jsonapi_views_filter_block.js.
 */

var URI_CHANGE_EVENT_TYPE = 'jsonApiViewsUriChange';
var DATA_CHANGE_EVENT_TYPE = 'jsonApiViewsDataChange';

(function ($, Drupal) {

  Drupal.behaviors.jsonApiViewsBlock = {
    /**
     * Attaches a submit handler to a views exposed filter block that prevents
     * the form from submitting and dispatches an update event.
     * @param {Node} context
     *   The DOM node scope.
     */
    attach: function (context) {

      var $formBlocks = $('.views-exposed-form[data-view-id][data-view-display]', context);

      $formBlocks.each(function(index, formBlock) {
        var view = formBlock.getAttribute('data-view-id');
        var display = formBlock.getAttribute('data-view-display');
        var $form = $(formBlock).find('form');

        var uri = buildUriFromFormBlock(formBlock);
        window.dispatchEvent(getUriEvent(view, display, uri));

        $form.on('submit', function(e) {
          e.preventDefault();

          var values = $form.serializeArray();
          var uri = buildUri(view, display, values);
          var queryUrl = buildUrlQuery(values);
          console.log({queryUrl})
          window.history.pushState(null, '', queryUrl);
          window.dispatchEvent(getUriEvent(view, display, uri));

          // TODO: Add an option to fetch the data rather than just triggering an event change.
          // $.getJSON(uri, function (data) {
          //   window.dispatchEvent(getDataEvent(view, display, data));
          // });
        })
      });
    },

    /**
     * Returns the JSON:API Resource uri from the given forms exposed filters.
     *
     * @param {Node} formBlock
     *   Exposed form wrapper DOM node.
     *
     * @return {string}
     *   A fully formed uri.
     */
    getUri: function(view, display) {
      var $formBlock = $('.views-exposed-form[data-view-id="' + view + '"][data-view-display="' + display + '"]');
      if ($formBlock.length > 0) {
        return buildUriFromFormBlock($formBlock[0]);
      }

      console.warn('Exposed filters for the given view display combination not found. View: "' + view + '" Display: "' + display + '"');
      return null;
    }
  };

  /**
   * Creates a JSON:API Data Change event to be dispatched
   *
   * @param {string} view
   *   Views view ID.
   * @param {string} display
   *   Views display ID.
   * @param {string} data
   *   The JSON:API views resource response data
   * @return {CustomEvent}
   *   A Custom Event object.
   */
  function getDataEvent(view, display, data) {
    var event = new CustomEvent(DATA_CHANGE_EVENT_TYPE, {
      detail: {
        view: view,
        display: display,
        data: data,
      }
    });

    return event;
  }

  /**
   * Creates a JSON:API URI Change event to be dispatched
   *
   * @param {string} view
   *   Views view ID.
   * @param {string} display
   *   Views display ID.
   * @param {string} uri
   *   The JSON:API views resource uri
   * @return {CustomEvent}
   *   A Custom Event object.
   */
  function getUriEvent(view, display, uri) {
    var event = new CustomEvent(URI_CHANGE_EVENT_TYPE, {
      detail: {
        view: view,
        display: display,
        uri: uri,
      }
    });

    return event;
  }

  /**
   * Constucts a uri search query string from the provided form values.
   *
   * @param {object} formValues
   *   Exposed form input values.
   *
   * @return {string}
   *   A uri search query.
   */
  function getParams(formValues) {
    if (!formValues) {
      return '';
    }



    const activeFilters = formValues
      .filter(function (filter) {
        return filter.value !== '';
      })
      .map(function (filter) {
        // Ex. Matches 'value' in the string 'type[value]';
        const multiValueNameRegEx = new RegExp('\\S+\\[' + filter.value + '\\]$', 'g');

        // Convert multi-value checkboxes from better-exposed-filters
        if (multiValueNameRegEx.test(filter.name)) {
          return 'views-filter[' + filter.name.split('[')[0] + '][]=' + filter.value;
        }
        // Convert multi-value selects to the proper format
        else if (filter.name.slice(-2) == '[]') {
          return 'views-filter[' + filter.name.slice(0, -2) + '][]=' + filter.value;
        }
        else {
          return 'views-filter[' + filter.name + ']=' + filter.value;
        }
      })

    const search = activeFilters.join('&');

    return '?' + search;
  }

  /**
   * Constucts a JSON:API Views resource uri with the given exposed filter values.
   *
   * @param {string} view
   *   Views view ID.
   * @param {string} display
   *   Views display ID.
   * @param {object} formValues
   *   Exposed form input values.
   *
   * @return {string}
   *   A fully formed uri.
   */
  function buildUri(view, display, formValues) {
    const base = window.location.protocol + '//' + window.location.host;
    const namespace = 'jsonapi/views';
    const href = [base, namespace, view, display].join('/');
    const search = getParams(formValues);

    return encodeURI(href + search);
  }

  /**
   * Constucts a URL query string.
   *
   * @param {object} formValues
   *   Exposed form input values.
   *
   * @return {string}
   *   A fully formed uri.
   */
  function buildUrlQuery(formValues) {
    const base = window.location.protocol + '//' + window.location.host;
    const uri = window.location.pathname;
    // const href = [base, uri].join('/');
    const href = base + uri;
    const search = decodeURIComponent($.param(formValues));

    return [href,search].join('?');
  }

  /**
   * Constucts a JSON:API Views resource uri from the exposed form.
   *
   * @param {Node} formBlock
   *   Exposed form wrapper DOM node.
   *
   * @return {string}
   *   A fully formed uri.
   */
  function buildUriFromFormBlock(formBlock) {
    var $form = $(formBlock).find('form');
    var view = formBlock.getAttribute('data-view-id');
    var display = formBlock.getAttribute('data-view-display');

    var values = $form.serializeArray();
    return buildUri(view, display, values);
  }

})(jQuery, Drupal, drupalSettings);
