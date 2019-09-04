/**
 * @file
 * Provides JavaScript for reservation related forms.
 */

(function ($, Drupal) {

/**
 * Provides JS helper functions for reservation functionality.
 */
Drupal.behaviors.reservationFormHelper = {

  convertDate: function (time, diff) {
    var d = new Date();
    var timeArray = time.split(':');
    d.setHours(timeArray[0]);
    d.setMinutes(diff ? diff(timeArray[1]) : timeArray[1]);
    var h = d.getHours().toString().length == 2 ? d.getHours() : '0' + d.getHours();
    var m = d.getMinutes().toString().length == 2 ? d.getMinutes() : '0' + d.getMinutes();
    return h + ':' + m;
  },
  attach: function (context, settings) {
    var $root = this;
    $('.reservation-prepopulate-dates', context).once().change(function() {
      var fields = [{
        'source': '#edit-field-date-time-0-value-date',
        'target': '#edit-reservation-dates-start-date',
        'targetName': 'reservation[dates][start][date]'
      },{
        'source': '#edit-field-date-time-0-value-time',
        'target': '#edit-reservation-dates-start-time',
        'targetName': 'reservation[dates][start][time]'
      },{
        'source': '#edit-field-date-time-0-end-value-date',
        'target': '#edit-reservation-dates-end-date',
        'targetName': 'reservation[dates][end][date]'
      },{
        'source': '#edit-field-date-time-0-end-value-time',
        'target': '#edit-reservation-dates-end-time',
        'targetName': 'reservation[dates][end][time]'
      }];
      for (var i = 0; i < fields.length; i++) {
        var sv = $(fields[i].source).val();
        $(fields[i].target).val(sv);
        $('[name="' + fields[i].targetName + '"]').val(sv);
      }
    });
  },
};

})(jQuery, Drupal);
