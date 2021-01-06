/* eslint-disable */

/**
 * Event Types
 */
const SAVE_ROOM_RESERVATION_SUCCESS = 'intercept:saveRoomReservationSuccess';

(function ($, Drupal) {
  // Refresh reservation view when reservations is edited.
  $(window)
    .once('interceptRoomReservationListEvents')
    .on({
      [SAVE_ROOM_RESERVATION_SUCCESS]: function () {
        $('.view-id-intercept_room_reservations.view-display-id-page').trigger('RefreshView')
      },
    });
})(jQuery, Drupal);
