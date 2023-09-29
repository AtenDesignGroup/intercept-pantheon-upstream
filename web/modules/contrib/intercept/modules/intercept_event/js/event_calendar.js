/* eslint-disable no-param-reassign */
/* eslint-disable no-restricted-syntax */
/* eslint-disable no-await-in-loop */

/**
 * @file
 * Provides functionality for event calendar.
 */

(function interceptEventCalendar($, Drupal, once, drupalSettings) {
  if (!Drupal.fullcalendar_block) {
    return;
  }

  const blockInstances = {};
  let filterUrl;
  let dates = {
    start: null,
    end: null,
  };
  let currentFetches = 0;

  // Create a custom event for fetch start
  const fetchEventsStartEvent = new Event('fetchEventsStart');

  // Create a custom event for fetch end
  const fetchEventsEndEvent = new Event('fetchEventsEnd');

  function showLoader() {
    document.getElementById('event-progress-throbber')?.classList.remove('hidden');
  }

  function hideLoader() {
    document.getElementById('event-progress-throbber')?.classList.add('hidden');
  }

  /**
   * Fetches events from a JSON API endpoint.
   *
   * @param {string} url
   *  The URL to fetch events from.
   *
   * @returns {AsyncGenerator}
   *  An async generator that yields events.
   */
  async function* fetchEvents(url) {
    while (url) {
      const response = await fetch(url);
      const data = await response.json();

      yield data.data;
      // eslint-disable-next-line no-param-reassign
      url = data?.links?.next?.href;
    }
  }

  function getJSONAPIUrl() {
    const url = new URL(filterUrl);
    url.searchParams.set('views-filter[start]', dates.start);
    url.searchParams.set('views-filter[end]', dates.end);
    url.searchParams.set('fields[node--event]', 'title,field_date_time,drupal_internal__nid,field_audience_primary');
    return url.toString();
  }

  /**
   * Transforms JSON API event data to FullCalendar event data.
   * See: https://fullcalendar.io/docs/event-object
   */
  function eventDataTransform(eventData) {
    return {
      id: eventData.id,
      title: eventData.attributes.title,
      start: eventData.attributes.field_date_time.value,
      end: eventData.attributes.field_date_time.end_value,
      url: `/event/${eventData.attributes.drupal_internal__nid}/calendar`,
      allDay: eventData.attributes.field_date_time.value.split('T')[0] !== eventData.attributes.field_date_time.end_value.split('T')[0],
      classNames: ['field-audience-primary-' + eventData.relationships.field_audience_primary.data.meta.drupal_internal__target_id],
    };
  }

  async function fetchInstanceEvents(blockInstanceId) {
    if (!dates.start || !dates.end || !filterUrl) {
      return;
    }
    const url = getJSONAPIUrl();
    const calendar = blockInstances[blockInstanceId].instance.calendar;
    calendar.getEventSources().forEach((source) => {
      source.remove();
    });
    const eventsGenerator = fetchEvents(url);

    // Dispatch the fetch start event
    document.dispatchEvent(fetchEventsStartEvent);

    for await (const events of eventsGenerator) {
      // Process each batch of events as they come in.
      calendar.addEventSource(events.map(eventDataTransform));
    }

    // Dispatch the fetch start event
    document.dispatchEvent(fetchEventsEndEvent);
  }

  function addInstance(instance) {
    blockInstances[instance.index] = {
      instance,
      start: null,
      end: null,
      filterUrl: null,
    };
    fetchInstanceEvents(instance.index);
  }

  function removeInstance(id) {
    delete blockInstances[id];
  }

  function setDates(start, end) {
    dates = { start, end };
    Object.keys(blockInstances).forEach((blockInstanceId) => {
      fetchInstanceEvents(blockInstanceId);
    });
  }

  function setFilterUrl(url) {
    // Avoid fetching events if the filter URL hasn't changed.
    if (filterUrl === url) {
      return;
    }
    filterUrl = url;
    Object.keys(blockInstances).forEach((blockInstanceId) => {
      fetchInstanceEvents(blockInstanceId);
    });
  }

  function eventCalendarBuild(event, blockInstance) {
    addInstance(blockInstance);
  }

  function eventCalendarBeforeBuild(event, calendarOptions) {
    // Prevent initial default fetch.
    calendarOptions.events = [];
    calendarOptions.startParam = 'views-filter[start]';
    calendarOptions.endParam = 'views-filter[end]';
    // Transform event data from JSON API to FullCalendar.
    calendarOptions.datesSet = (dateInfo) => {
      setDates(
        dateInfo.start.toISOString().split('T')[0],
        dateInfo.end.toISOString().split('T')[0]
      );
    };
    calendarOptions.dayMaxEventRows = true;
    calendarOptions.views = {
      timeGrid: {
        dayMaxEventRows: 6, // adjust to 6 only for timeGridWeek/timeGridDay
      },
    };
    calendarOptions.customButtons = {
      print: {
        text: 'Print',
        click() {
          window.print();
        },
      },
    },
    calendarOptions.headerToolbar = {
      left: 'prev,next today print',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay',
    };
    calendarOptions.nowIndicator = true;
  }

  window.addEventListener('jsonApiViewsUriChange', (e) => {
    setFilterUrl(e.detail.uri);
  });

  $(document).on('fullcalendar_block.build', eventCalendarBuild);
  $(document).on('fullcalendar_block.beforebuild', eventCalendarBeforeBuild);

  // Listen for the fetch start event
  document.addEventListener('fetchEventsStart', () => {
    currentFetches += 1;
    showLoader();
  });

  // Listen for the fetch end event
  document.addEventListener('fetchEventsEnd', () => {
    currentFetches -= 1;
    if (currentFetches === 0) {
      hideLoader();
    }
  });

  /**
   * Attaches a behavior to the events calendar filter form
   * which will fetch an updated filter and summary from the server.
   * This is necessary to keep the filter summary updated.
   */
  Drupal.behaviors.interceptEventsCalendarFilter = {
    attach: function attach(context) {
      const $form = $('#views-exposed-form-intercept-events-events', context);
      $form.on('submit', function(e) {
        const updateFilters = Drupal.ajax({
          url: window.location.origin + window.location.pathname + '?' + $form.serialize(),
          base: 'intercept-events-filter',
          element: $('#intercept-events-filter')[0],
        });
        updateFilters.execute();
      });
    },
    detach: function detach(context) {
      const $form = $('#views-exposed-form-intercept-events-events', context);
      $form.off('submit');
    },
  };

  // eslint-disable-next-line no-undef
}(jQuery, Drupal, once, drupalSettings));
