/**
 * @file
 * Fullcalendar Block JavaScript file.
 */

/* eslint no-unused-vars: ["warn", { "argsIgnorePattern": "^_" }] */
/* eslint no-console: ["warn", { allow: ["warn"] }] */

// Codes run both on normal page loads and when data is loaded by AJAX (or BigPipe!)
// @See https://www.drupal.org/docs/drupal-apis/javascript-api/javascript-api-overview
(($, Drupal, once, drupalSettings, DOMPurify, FullCalendar) => {
  /**
   * @namespace
   */
  Drupal.fullcalendar_block = {};

  Drupal.fullcalendar_block.instances = {};

  /**
   * Retrieves a block instance setting.
   *
   * If the settings path is not specified, then the entire block instance settings is returned instead.
   *
   * @param {string} blockIndex - The index of the block.
   * @param {string|null} settingPath - The path to the desired setting.
   * @param {*} defaultValue - The default value to return if the setting is not found.
   * @return {*} The value of the requested setting or the default value.
   */
  Drupal.fullcalendar_block.getSettings = function getSettings(
    blockIndex,
    settingPath = null,
    defaultValue = null,
  ) {
    const blockSettings =
      drupalSettings &&
      drupalSettings.fullCalendarBlock &&
      drupalSettings.fullCalendarBlock[blockIndex];
    if (!settingPath) {
      return blockSettings;
    }
    // https://stackoverflow.com/a/43849204
    return settingPath
      .split(/[.\\[\]'"]/)
      .filter(Boolean)
      .reduce((o, p) => {
        if (o && typeof o[p] !== 'undefined') {
          return o[p];
        }
        return defaultValue;
      }, blockSettings);
  };

  /**
   * Sanitize a piece of text. Addressing any potential XSS attacks.
   *
   * @param {string} html - The HTML text to be sanitized.
   * @param {string} blockIndex - The block index associated with the description.
   * @param {object} _info - Additional information related to the description.
   * @return {string} The sanitized HTML text.
   */
  Drupal.fullcalendar_block.sanitizeDescription = function sanitizeDescription(
    html,
    blockIndex,
    _info,
  ) {
    return DOMPurify.sanitize(
      html,
      Drupal.fullcalendar_block.getSettings(
        blockIndex,
        'advanced.dompurify_options',
        {},
      ),
    );
  };

  /**
   * Open a URL in the current tab or a new one.
   *
   * @param {string} url - The URL to navigate to.
   * @param {boolean} newTab - A boolean indicating whether to open the URL in a new tab.
   */
  Drupal.fullcalendar_block.gotoURL = function gotoURL(url, newTab) {
    const eventURL = new URL(url, window.location.origin);
    if (!eventURL.origin || eventURL.origin === 'null') {
      // https://bugs.chromium.org/p/chromium/issues/detail?id=608606
      return;
    }
    if (newTab) {
      // Open a new window to show the details of the event.
      window.open(url, '_blank').focus();
    } else {
      window.location.href = url;
    }
  };

  /**
   * Sanitize and render the title to account for ampersands.
   *
   * @param {string} title - The title to be sanitized and rendered.
   * @return {string} The sanitized and rendered title.
   */
  Drupal.fullcalendar_block.sanitizeTitle = function sanitizeTitle(title) {
    const doc = new DOMParser().parseFromString(title, 'text/html');
    return doc.documentElement.innerText;
  };

  /**
   * Transform event object to respond to the Drupal settings.
   *
   * @param {string} blockIndex - The index of the block.
   * @param {Object} info - The event object information.
   */
  function eventDataTransform(blockIndex, info) {
    // Replace the title with the sanitized and rendered version.
    const sanitizeHtmlTitle = Drupal.fullcalendar_block.getSettings(
      blockIndex,
      'advanced.html_title',
      false,
    );
    const rawTitleField = Drupal.fullcalendar_block.getSettings(
      blockIndex,
      'advanced.raw_title_field',
      'rawTitle',
    );
    if (
      (sanitizeHtmlTitle && info[rawTitleField] !== false) ||
      info[rawTitleField] === true
    ) {
      info.title = Drupal.fullcalendar_block.sanitizeTitle(info.title);
    }
    // Clean up rrule string generated by a Drupal view.
    if (
      info.rrule &&
      typeof (info.rrule === 'string' || info.rrule instanceof String)
    ) {
      // Format the rrule string.
      // Remove all breaks(new line).
      info.rrule = info.rrule.replaceAll(/[\s\n\r\\n]+(?=RRULE:)/g, '');
      info.rrule = info.rrule.replaceAll(' ', '');
      // Remove br tag.
      info.rrule = info.rrule.replaceAll('<br/>', '');
      // Put one new line back.
      info.rrule = info.rrule.replace('RRULE:', '\nRRULE:');
    }
    // Event background color.
    const backgroundFieldName = Drupal.fullcalendar_block.getSettings(
      blockIndex,
      'advanced.event_background.field_name',
      false,
    );
    if (backgroundFieldName && info[backgroundFieldName]) {
      const colorMap = Drupal.fullcalendar_block.getSettings(
        blockIndex,
        'advanced.event_background.color_map',
        false,
      );
      if (colorMap && Array.isArray(colorMap)) {
        // Find the first match to use for the background colour.
        colorMap.some((map) => {
          if (typeof map === 'string') {
            const colorSet = map.split(' ');
            if (
              colorSet[0] &&
              colorSet[1] &&
              colorSet[0] === info[backgroundFieldName]
            ) {
              [, info.backgroundColor] = colorSet;
              return true;
            }
          }
          return false;
        });
      }
    }
  }

  /**
   * The event click callback.
   *
   * @param {Object} info - Information about the clicked event.
   * @param {Object} info.event - The associated event object.
   * @param {Object} info.view - The current View Object.
   * @param {HTMLElement} info.el - The HTML element for this event.
   * @param {Event} info.jsEvent - The native JavaScript event with low-level information such as click coordinates.
   */
  function eventClick(info) {
    info.jsEvent.preventDefault();
    const blockIndex = this.el.getAttribute('data-calendar-block-index');
    const openMode = parseInt(
      Drupal.fullcalendar_block.getSettings(blockIndex, 'dialog_open', 1),
      10,
    );
    if (openMode === 1) {
      // Open in a dialog.
      let dialogWidth = parseInt(
        Drupal.fullcalendar_block.getSettings(blockIndex, 'dialog_width', 400),
        10,
      );
      const dialogType = Drupal.fullcalendar_block.getSettings(
        blockIndex,
        'advanced.dialog_type',
        'modal',
      );
      const dialogOptions = Drupal.fullcalendar_block.getSettings(
        blockIndex,
        'advanced.dialog_options',
        {
          // autoResize option will turn off resizable by default.
          // autoResize: false,
        },
      );

      if (dialogWidth <= 0) {
        // The dialog width is unneeded.
        dialogWidth = undefined;
      }

      const draggableDialog = Drupal.fullcalendar_block.getSettings(
        blockIndex,
        'advanced.draggable',
        false,
      );
      const resizableDialog = Drupal.fullcalendar_block.getSettings(
        blockIndex,
        'advanced.resizable',
        false,
      );

      // Attempt to render descriptions.
      const descriptionPopup = Drupal.fullcalendar_block.getSettings(
        blockIndex,
        'advanced.description_popup',
        false,
      );
      const description =
        info.event.extendedProps[
          Drupal.fullcalendar_block.getSettings(
            blockIndex,
            'advanced.description_field',
            'des',
          )
        ];

      if (descriptionPopup && description) {
        // Create a Drupal dialog to render the description.
        const descriptionEl = document.createElement('div');
        descriptionEl.setAttribute('id', 'fullcalendar-block-dialog');
        descriptionEl.innerHTML = Drupal.fullcalendar_block.sanitizeDescription(
          description,
          blockIndex,
          info,
        );
        Drupal.dialog(
          descriptionEl,
          $.extend(
            {
              title: info.event.title,
              dialogClass:
                'fullcalendar-block-dialog fullcalendar-block-dialog--description',
              resizable: resizableDialog,
              draggable: draggableDialog,
              width: dialogWidth,
              fullcalendarBlockIndex: blockIndex,
            },
            dialogOptions,
          ),
        ).showModal();
        // Trigger Drupal's attachment behaviour.
        Drupal.attachBehaviors(descriptionEl);
        return;
      }

      if (!info.event.url) {
        return;
      }
      const openDialog = Drupal.ajax({
        url: info.event.url,
        dialogType,
        dialog: $.extend(
          {
            dialogClass:
              'fullcalendar-block-dialog fullcalendar-block-dialog--url',
            resizable: resizableDialog,
            draggable: draggableDialog,
            width: dialogWidth,
            fullcalendarBlockIndex: blockIndex,
          },
          dialogOptions,
        ),
      }).execute();

      openDialog
        .done(() => {
          // The modal dialog open successfully.
        })
        .fail(() => {
          // The Ajax modal couldn't open.
          // Open in a new tab instead.
          Drupal.fullcalendar_block.gotoURL(info.event.url, true);
        });
    } else if (openMode === 2 && info.event.url) {
      // Open in the current window.
      Drupal.fullcalendar_block.gotoURL(info.event.url, false);
    } else if (info.event.url) {
      // Open in a new tab.
      Drupal.fullcalendar_block.gotoURL(info.event.url, true);
    }
  }

  $(window).on('dialog:aftercreate', (e, dialog, $element, settings) => {
    const blockIndex = settings.fullcalendarBlockIndex;
    if (blockIndex) {
      // Attempt to pass the draggable/resizable options.
      // @see dialog.position.es6.js
      const autoResize =
        settings.autoResize === true || settings.autoResize === 'true';
      const $dialog = $element.dialog('widget');
      if (
        (settings.draggable === true || settings.draggable === 'true') &&
        typeof $.fn.draggable === 'function'
      ) {
        if (autoResize) {
          // Make the dialog instance draggable.
          $element.dialog('option', { draggable: true });
        }
        // Add the custom draggable options.
        $dialog.draggable(
          'option',
          Drupal.fullcalendar_block.getSettings(
            blockIndex,
            'advanced.draggable_options',
            {},
          ),
        );
      }
      if (
        // Resizable is fundamentally unsupported by the resize widget due to the already created event listeners.
        !autoResize &&
        (settings.resizable === true || settings.resizable === 'true') &&
        typeof $.fn.resizable === 'function'
      ) {
        if ($dialog.resizable('option', 'handles') === 'true') {
          // Drupal AJAX can mangle up the default resizable handle options when it returns. Reset it ourselves.
          // @see jquery.ui.dialog _makeResizable()
          $dialog.resizable('option', 'handles', 'n,e,s,w,se,sw,ne,nw');
        }
        // Add the custom resizable options.
        $dialog.resizable(
          'option',
          Drupal.fullcalendar_block.getSettings(
            blockIndex,
            'advanced.resizable_options',
            {},
          ),
        );
      }
    }
  });

  Drupal.behaviors.buildCalendarBlock = {
    attach(context) {
      once('buildFullcalendarBlock', '.fullcalendar-block', context).forEach(
        (element) => {
          const blockIndex = element.getAttribute('data-calendar-block-index');
          element.setAttribute('data-calendar-block-initialized', 'true');
          const blockSettings =
            Drupal.fullcalendar_block.getSettings(blockIndex);
          if (!blockSettings) {
            console.warn(
              'Could not fetch fullcalendar_block settings',
              blockIndex,
              element,
            );
            return;
          }
          const calendarOptions =
            typeof blockSettings.calendar_options === 'string'
              ? JSON.parse(blockSettings.calendar_options)
              : $.extend(true, {}, blockSettings.calendar_options);

          if (
            typeof calendarOptions.events === 'string' &&
            Drupal.url.isLocal(calendarOptions.events)
          ) {
            // Check if there are any GET parameters to send to the internal
            // Drupal path. This should provide better integration with Views exposed filters.
            let queryString = window.location.search || '';

            if (queryString !== '') {
              // Remove the question mark and any Drupal path component,
              // existing fullcalendar search filters if any.
              const startParam = calendarOptions.startParam || 'start';
              const specialParams = [
                // Internal Drupal paths.
                'q',
                'render',
                // Remove parameters that will be sent by fullcalendar.
                startParam,
                calendarOptions.endParam || 'end',
                calendarOptions.timeZoneParam || 'timeZone',
              ];

              const query = new URLSearchParams(queryString);

              // Specify the default view mode.
              const initialViewParam =
                calendarOptions.initialViewParam || 'viewMode';
              if (query.get(initialViewParam)) {
                calendarOptions.initialView = query.get(initialViewParam);
              }

              specialParams.forEach((specialParam) => {
                if (specialParam === startParam) {
                  // Provide a default initial date if it's specified in the query string.
                  const date = new Date(query.get(startParam));
                  if (date instanceof Date && !Number.isNaN(date.getTime())) {
                    // Valid date string.
                    calendarOptions.initialDate = date.toISOString();
                  }
                }
                query.delete(specialParam);
              });
              queryString = query.toString();

              if (queryString !== '') {
                // If there is a '?' in events URL, & should be used to add
                // parameters.
                queryString =
                  (calendarOptions.events.indexOf('?') === -1 ? '?' : '&') +
                  queryString;
                // Add the current query string to the query.
                // Useful for taking in exposed filters.
                calendarOptions.events += queryString;
              }
            }
          }

          // Bind the event click handler.
          calendarOptions.eventClick = eventClick;

          // Bind the default event data transform handler.
          calendarOptions.eventDataTransform = eventDataTransform.bind(
            element,
            blockIndex,
          );

          // Trigger an event to let other modules know that a calendar
          // will be built.
          $(document).trigger(
            'fullcalendar_block.beforebuild',
            calendarOptions,
          );
          const calendar = new FullCalendar.Calendar(element, calendarOptions);
          calendar.render();
          // Store a reference to the calendar block instance.
          Drupal.fullcalendar_block.instances[blockIndex] = {
            element,
            index: blockIndex,
            calendar,
            calendarOptions,
            blockSettings,
          };
          // Trigger an event to let other modules know when a calendar
          // has been built.
          $(document).trigger('fullcalendar_block.build', [
            Drupal.fullcalendar_block.instances[blockIndex],
          ]);
        },
      );
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        once
          .remove('buildFullcalendarBlock', '.fullcalendar-block', context)
          .forEach((element) => {
            // Unload the calendar block instance.
            const blockIndex = element.getAttribute(
              'data-calendar-block-index',
            );
            element.removeAttribute('data-calendar-block-initialized');
            if (Drupal.fullcalendar_block.instances[blockIndex]) {
              if (Drupal.fullcalendar_block.instances[blockIndex].calendar) {
                Drupal.fullcalendar_block.instances[
                  blockIndex
                ].calendar.destroy();
              }
              delete Drupal.fullcalendar_block.instances[blockIndex];
            }
          });
      }
    },
  };
})(jQuery, Drupal, once, drupalSettings, window.DOMPurify, window.FullCalendar);
