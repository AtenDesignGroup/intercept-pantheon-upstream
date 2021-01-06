/* eslint-disable prefer-rest-params */
/* eslint-disable prefer-arrow-callback */
/* eslint-disable object-shorthand */
/* eslint-disable no-param-reassign */
/* eslint-disable func-names */
/* eslint-disable wrap-iife */

/**
  * Returns a function, that, as long as it continues to be invoked, will not
  * be triggered. The function will be called after it stops being called for
  * N milliseconds. If `immediate` is passed, trigger the function on the
  */
function debounce(func, wait, immediate) {
  let timeout;
  return function () {
    const context = this;
    const args = arguments;
    const later = function () {
      timeout = null;
      if (!immediate) func.apply(context, args);
    };
    const callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
    if (callNow) func.apply(context, args);
  };
}

/**
 * @file
 * Contains interceptRoomReservationMediator.js
 * This describes event dispatching and handling of evenets
 * to mediate communication between React and Drupal.
 *
 * Event types
 *  addRoomReservation
 *  editRoomReservation
 *  viewRoomReservation
 *  saveRoomReservation
 *  saveRoomReservationSuccess
 *  saveRoomReservationError
 *  changeRoomReservation
 *  closeRoomReservation
 */

/**
 * Event Types
 */
const ADD_ROOM_RESERVATION = 'intercept:addRoomReservation';
const EDIT_ROOM_RESERVATION = 'intercept:editRoomReservation';
const VIEW_ROOM_RESERVATION = 'intercept:viewRoomReservation';
const SAVE_ROOM_RESERVATION = 'intercept:saveRoomReservation';
const SAVE_ROOM_RESERVATION_SUCCESS = 'intercept:saveRoomReservationSuccess';
const SAVE_ROOM_RESERVATION_ERROR = 'intercept:saveRoomReservationError';
const CHANGE_ROOM_RESERVATION = 'intercept:changeRoomReservation';
const REFRESH_ROOM_RESERVATION = 'intercept:updateRoomReservation';
const CLOSE_ROOM_RESERVATION = 'intercept:closeRoomReservation';

/**
 * Actions
 */
const EDIT_ACTION = 'edit';
const VIEW_ACTION = 'view';
const OPEN_ACTION = 'open';
const REPLACE_ACTION = 'replace';

const OFF_CANVAS_SELECTOR = '#drupal-off-canvas';
const OFF_CANVAS_SPEED = 1000;
const OFF_CANVAS_RESIZE_INTERVAL = 30;

(function ($, Drupal) {
  let currentEvent;
  let currentAction;
  let currentValues;
  let shouldUpdateFormValues = false;

  /**
   * Debounce the activateDialog function so we don't inadvertantly
   * try to open both the view and edit dialogs.
   */
  const activateDialog = debounce(function (url, values) {
    const action = Drupal.offCanvas.isOffCanvas($(OFF_CANVAS_SELECTOR))
      ? REPLACE_ACTION
      : OPEN_ACTION;

    // POST values
    const submit = {
      action: action,
    };

    if (values) {
      submit.values = values;
    }

    return Drupal.ajax({
      url: url,
      dialogType: 'dialog',
      dialog: {
        width: 400,
        autoResize: false,
      },
      submit: submit,
      dialogRenderer: 'off_canvas',
    }).execute();
  }, 300);

  function getRoomInputValue(room) {
    return `${room.title} (${room.drupal_internal__nid})`;
  }

  function updateFormValues(context) {
    if (!shouldUpdateFormValues) {
      return;
    }

    // Update Room.
    $('[data-drupal-selector="edit-field-room-0-target-id"]', context).val(
      getRoomInputValue(currentValues.resource),
    );

    // Update Date & Time.
    $('[data-drupal-selector="edit-field-dates-0-value-date"]', context).val(
      currentValues.start.date,
    );
    $('[data-drupal-selector="edit-field-dates-0-value-time"]', context).val(
      currentValues.start.time,
    );
    $('[data-drupal-selector="edit-field-dates-0-end-value-date"]', context).val(
      currentValues.end.date,
    );
    $('[data-drupal-selector="edit-field-dates-0-end-value-time"]', context).val(
      currentValues.end.time,
    ).trigger('change');

    shouldUpdateFormValues = false;
  }

  Drupal.behaviors.interceptUpdateRoomReservationForm = {
    attach: function (context) {
      if (context.tagName !== 'FORM') {
        return;
      }
      updateFormValues(context);
    },
  };

  Drupal.behaviors.interceptRoomReservationMediator = {
    /**
     * Attaches a submit handler to a views exposed filter block that prevents
     * the form from submitting and dispatches an update event.
     * @param {Node} context
     *   The DOM node scope.
     */
    attach: function () {
      $(window)
        .once('interceptRoomReservationMediatorEvents')
        .on({
          [ADD_ROOM_RESERVATION]: this.onAddRoomReservation,
          [VIEW_ROOM_RESERVATION]: this.onViewRoomReservation,
          [EDIT_ROOM_RESERVATION]: this.onEditRoomReservation,
          [CHANGE_ROOM_RESERVATION]: this.onChangeRoomReservation,
          'dialog:afterclose': this.onCloseDialog,
        });
    },

    onCloseDialog: function () {
      currentEvent = null;
      currentAction = null;
      currentValues = null;
      // Allow an easy way to hook into resize events.
      window.dispatchEvent(new CustomEvent(CLOSE_ROOM_RESERVATION));
    },

    onAddRoomReservation: function (event) {
      console.log({event});
      currentValues = event.detail;
      shouldUpdateFormValues = true;

      currentAction = EDIT_ACTION;

      // Manually open the reservation edit dialog.
      activateDialog('/manage/room-reservations/add', {
        room: currentValues.resource.drupal_internal__nid,
        date: {
          start: currentValues.start,
          end: currentValues.end,
        },
      });
    },

    onChangeRoomReservation: function (event) {
      const id = event.detail.drupal_internal__id;
      currentValues = event.detail;
      shouldUpdateFormValues = true;

      // Update form if we are already editing this event.
      if (currentEvent === id && currentAction === EDIT_ACTION) {
        const form = document.querySelector('form.room-reservation-form');
        if (form) {
          Drupal.behaviors.interceptUpdateRoomReservationForm.attach(form);
        }
        return;
      }
      currentEvent = id;
      currentAction = EDIT_ACTION;

      // Manually open the reservation edit dialog.
      activateDialog(`/manage/room-reservations/${id}/edit`, {
        room: currentValues.resource.drupal_internal__nid,
        date: {
          start: currentValues.start,
          end: currentValues.end,
        },
      });
    },

    onEditRoomReservation: function (event) {
      const id = event.detail.id;

      // Abort if we are already editing this event.
      if (!currentEvent === id && currentAction === EDIT_ACTION) {
        return;
      }
      currentEvent = id;
      currentAction = EDIT_ACTION;

      // Manually open the reservation edit dialog.
      activateDialog(`/manage/room-reservations/${id}/edit`);
    },

    onViewRoomReservation: function (event) {
      const id = event.detail.id;

      // Abort if we are already viewing or editing this event.
      if (currentEvent === id && !!currentAction) {
        return;
      }
      currentEvent = id;
      currentAction = VIEW_ACTION;

      // Manually open the reservation view dialog.
      activateDialog(`/room-reservation/${event.detail.id}`);
    },
  };
})(jQuery, Drupal);
