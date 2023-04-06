'use strict';

(function updateElement($) {
  Drupal.behaviors.office_hours = {
    attach: function doUpdateElement(context, settings) {
      // Traverses visible rows and applies even/odd classes.
      function fixStriping() {
        $('tbody tr:visible', context).each(function setStriping(i) {
          if (i % 2 === 0) {
            $(this).removeClass('odd').addClass('even');
          } else {
            $(this).removeClass('even').addClass('odd');
          }
        });
      }

      /**
       * Fills a slot item with the new value,
       * and shows the next item slowly if needed.
       *
       * @param formItem The time slot.
       * @param value The new value.
       */
      function setTimeslot(formItem, value) {
        formItem.val(value);
        if (value) {
          // Show the next item, slowly.
          formItem.closest('tr').fadeIn('slow');
        }
      }

      /**
       * Shows the Add-link, conditionally.
       */
      function showAddLink() {
        var nextTr;
        nextTr = $(this).closest('tr').next();
        $(this).hide();
        if (nextTr.is(':hidden')) {
          $(this).show();
        }
      }

      /**
       * Shows an office-hours-slot, when user clicks "Add more".
       * @param e
       * @return {boolean}
       */
      function showTimeslot(e) {
        var $nextTr;
        e.preventDefault();
        // Hide the link, the user clicked upon.
        $(this).hide();
        // Show the next slot item, slowly.
        $nextTr = $(this).closest('tr').next();
        $nextTr.fadeIn('slow');

        fixStriping();
        return false;
      }

      // Hide every item above the max slots per day.
      $('.office-hours-hide').hide();

      // @todo #3322982 Create named function with parameter and re-use the code.
      // @todo #3322982 When 'all_day' is set, the link 'Add time slot' must be hidden.
      // When the document loads, look for checked 'all day'
      // checkboxes, and disable the start and end times.
      $(document).ready(function () {

        // Loop through all the all day checkboxes that are checked.
        $('[id*="all-day"]:checked').each(function (index) {

          // Get the name of the checkbox, which will be mostly the
          // same name for the start and end times.
          var name = $(this).attr('name');

          // Variable to store all the names of the start/end times.
          var timeNames = [];

          // Replace the [all_day] with the name used for the start
          // and end times.
          timeNames.push(name.replace('[all_day]', '[starthours][time]'));
          timeNames.push(name.replace('[all_day]', '[starthours][hour]'));
          timeNames.push(name.replace('[all_day]', '[starthours][minute]'));
          timeNames.push(name.replace('[all_day]', '[endhours][time]'));
          timeNames.push(name.replace('[all_day]', '[endhours][hour]'));
          timeNames.push(name.replace('[all_day]', '[endhours][minute]'));

          // Step through all the start/end time names and set to disabled.
          timeNames.forEach(function (item) {
            $('[name="' + item + '"').prop("disabled", true);
          });
        });
      });

      // If an 'all day' checkbox is clicked, set the time to either
      // enabled or disabled.
      $('[id*="all-day"]').bind('click', function (e) {

        // Get the name of the checkbox, which will be mostly the
        // same name for the start and end times.
        var name = $(this).attr('name');

        // Variable to store all the names of the start/end times.
        var timeNames = [];

        // Replace the [all_day] with the name used for the start
        // and end times.
        timeNames.push(name.replace('[all_day]', '[starthours][time]'));
        timeNames.push(name.replace('[all_day]', '[starthours][hour]'));
        timeNames.push(name.replace('[all_day]', '[starthours][minute]'));
        timeNames.push(name.replace('[all_day]', '[endhours][time]'));
        timeNames.push(name.replace('[all_day]', '[endhours][hour]'));
        timeNames.push(name.replace('[all_day]', '[endhours][minute]'));

        // Set the start and end time to enabled or disabled depending
        // on if the all day checkbox is checked.
        if ($(this).is(':checked')) {

          // Step through all the start/end times and set to disabled.
          timeNames.forEach(function (item) {
            $('[name="' + item + '"').prop("disabled", true);
          });
        }
        else {

          // Step through all the start/end times and set to not disabled.
          timeNames.forEach(function (item) {
            $('[name="' + item + '"').prop("disabled", false);
          });
        }
      });

      // Attach a function to each Add-link to show the next slot if clicked upon.
      // Show the Add-link, except if the next time slot is hidden.
      $('.office-hours-add-link').bind('click', showTimeslot)
        .each(showAddLink);

      fixStriping();

      // Clear the content of this a, when user clicks "Clear/Remove".
      // Do this for both widgets.
      $('.office-hours-delete-link').bind('click', function deleteLink(e) {
        e.preventDefault();
        // @todo #3322982 Clear the 'all day' checkbox, too.
        // Clear the hours, minutes in the select box.
        $(this).parent().parent().find('.form-select').each(function deleteTimeInSelect() {
          $(this).val($('#target').find('option:first').val());
        });
        // Clear the hours, minutes in the HTML5 time element.
        $(this).parent().parent().find('.form-time').each(function deleteTimeInHtml5() {
          $(this).val($('#target').find('option:first').val());
        });
        // Clear the comment.
        $(this).parent().parent().find('.form-text').each(function deleteComment() {
          $(this).val($('#target').find('option:first').val());
        });
        // Clear the date (in Exceptions Date).
        $(this).parent().parent().find('.form-date').each(function deleteDate() {
          $(this).val($('#target').find('option:first').val());
        });
        // Hide the link.
        $(this).hide();
      });

      // Copy values from previous day, when user clicks "Copy previous day".
      // @todo This works for Table widget, not yet for List Widget.
      $('.office-hours-copy-link').bind('click', function copyPreviousDay(e) {
        var currentDay;
        var currentSelector;
        var previousDay;
        var previousSelector;
        var tbody;

        e.preventDefault();

        // Read current day; presume Week Widget, then check if List Widget is used.
        currentDay = parseInt($(this).closest('tr').find('input')[0].value);
        if(Number.isNaN(currentDay)) {
          // List widget.
          currentDay = parseInt($(this).closest('div div').find('select')[0].value);
          // Div's from current day.
          currentSelector = $(this).closest('tr');
          // Div's from previous day.
          previousSelector = currentSelector.prev().hasClass('office-hours-hide') ? currentSelector.prev().prev() : currentSelector.prev();
        } else {
          // Week widget.
          previousDay = (currentDay == 0) ? currentDay + 6 : currentDay - 1;

          // Select current table.
          tbody = $(this).closest('tbody');
          // Div's from current day.
          currentSelector = tbody.find('.office-hours-day-' + currentDay);
          // Div's from previous day.
          previousSelector = tbody.find('.office-hours-day-' + previousDay);
        }

        // For better UX, first copy the comments, then hours and fadeIn.
        // Copy the comment.
        previousSelector.find('.form-text').each(function copyComment(index) {
          setTimeslot(currentSelector.find('.form-text').eq(index), $(this).val());
        });
        // Copy the hours, minutes in the select box.
        previousSelector.find('.form-select').each(function copyTimeInSelect(index) {
          setTimeslot(currentSelector.find('.form-select').eq(index), $(this).val());
        });
        // Copy the hours, minutes in the select list/HTML5 time element.
        previousSelector.find('.form-time').each(function copyTimeInHtml5(index) {
          setTimeslot(currentSelector.find('.form-time').eq(index), $(this).val());
        });
        // Copy the day/date in the select list/HTML5 date element (List widget).
        previousSelector.find('.form-date').each(function copyDateInHtml5(index) {
          setTimeslot(currentSelector.find('.form-date').eq(index), $(this).val());
        });

        // If needed, show each Add-link of the day, after "Copy previous day".
        currentSelector.find('.office-hours-add-link').each(showAddLink);
        // @todo If needed, show each Remove/Delete-link of the day.
        currentSelector.find('.office-hours-delete-link').each(showAddLink);
      });
    }
  };
})(jQuery);
