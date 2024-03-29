/**
 * @todo Check js file for new API.
 * @see https://www.drupal.org/docs/drupal-apis/javascript-api/javascript-api-overview
 */
(function updateElement($, Drupal) {
  Drupal.behaviors.office_hours = {
    attach: function doUpdateElement(context) {
      // When the document loads, look for checked 'all day'
      // checkboxes, and disable the start and end times.
      $(document).ready(function prepareElements() {

        /**
         * Traverses visible rows and applies even/odd classes.
         */
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
         * @param formItem
         *   The time slot.
         * @param value
         *   The new value.
         */
        function setTimeSlotElement(formItem, value) {
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
          $(this).hide();

          const $nextTr = $(this).closest('tr').next();
          if ($nextTr.is(':hidden')) {
            $(this).show();
          }
        }

        /**
         * Enable/Disable the time input elements, depending on the all-day checkbox.
         *
         * @todo When 'all_day' is set, the link 'Add time slot' must be hidden #3322982.
         */
        function setAllDayTimeSlot() {
          // Get the name of the checkbox, which will be mostly the
          // same name for the start and end times.
          const name = $(this).attr('name');

          // Determine the state of the all_day checkbox.
          const isEnabled = $(this).is(':checked');

          // Variable to store all the names of the start/end times.
          const timeNames = [];

          // Replace [all_day] with the names for start and end times.
          // For HTML5 element.
          timeNames.push(name.replace('[all_day]', '[starthours][time]'));
          timeNames.push(name.replace('[all_day]', '[endhours][time]'));
          // For select list element.
          timeNames.push(name.replace('[all_day]', '[starthours][hour]'));
          timeNames.push(name.replace('[all_day]', '[starthours][minute]'));
          timeNames.push(name.replace('[all_day]', '[starthours][ampm]'));
          timeNames.push(name.replace('[all_day]', '[endhours][hour]'));
          timeNames.push(name.replace('[all_day]', '[endhours][minute]'));
          timeNames.push(name.replace('[all_day]', '[endhours][ampm]'));

          // Enable/Disable the start and end time depending on all_day checkbox.
          timeNames.forEach(function prepareAllDayTimeSlot(item) {
            $('[name="' + item + '"]').prop('disabled', isEnabled);
          });
        }

        /**
         * Shows an office-hours-slot, when user clicks "Add more".
         *
         * @param e The event.
         */
        function addTimeSlot(e) {
          e.preventDefault();

          // Hide the link, the user clicked upon.
          $(this).hide();

          // Show the next slot item, slowly.
          const $nextTr = $(this).closest('tr').next();
          $nextTr.fadeIn('slow');

          fixStriping();
        }

        /**
         * Clear a time slot when the delete link is selected.
         *
         * @param e The event.
         */
        function clearTimeSlot(e) {
          e.preventDefault();

          // Clear the value from the element.
          function clearValue() {
            $(this).val($('#target').find('option:first').val());
          }

          // Find the time slot.
          const slot = $(this).closest('tr');

          // Clear the date (in Exception days).
          slot.find('.form-date').each(clearValue);
          // Clear the all_day checkbox and set depending fields.
          slot.find('.form-checkbox').prop('checked', false);
          slot.find('.form-checkbox').each(setAllDayTimeSlot);
          // Do the following for both widgets:
          // Clear the hours, minutes in the select box.
          slot.find('.form-select').each(clearValue);
          // Clear the hours, minutes in the HTML5 time element.
          slot.find('.form-time').each(clearValue);
          // Clear the comment.
          slot.find('.form-text').each(clearValue);

          // @todo Hide subsequent slot that is cleared.
        }

        function copyPreviousDay(e) {
          e.preventDefault();

          let currentDay;
          let currentSelector;
          let previousDay;
          let previousSelector;
          let tbody;

          // Get current day using attribute, both for Week Widget and List Widget.
          // @todo Use only attribute, not both attribute and class name.
          currentDay = parseInt(
            $(this, 10).closest('tr').attr('office_hours_day'),
          );
          if (Number.isNaN(currentDay)) {
            // Basic List Widget.
            currentDay = parseInt(
              $(this, 10).closest('fieldset').attr('office_hours_day'),
            );
          }
          if (Number.isNaN(currentDay)) {
            // Error.
          } else {
            // Week widget can have value 0 (sunday). List widget starts with value 1.
            previousDay = currentDay === 0 ? currentDay + 6 : currentDay - 1;

            // Select current table.
            tbody = $(this).closest('tbody');
            // Get div's from current day using class name.
            currentSelector = tbody.find('.js-office-hours-day-' + currentDay);
            // Get div's from previous day using class name.
            previousSelector = tbody.find('.js-office-hours-day-' + previousDay);
          }

          // For better UX, first copy the comments, then hours and fadeIn.
          // Copy the comment.
          previousSelector.find('.form-text').each(function copyComment(index) {
            setTimeSlotElement(
              currentSelector.find('.form-text').eq(index),
              $(this).val(),
            );
          });
          // Do NOT copy the day/date in the select list/HTML5 date element (List widget).
          previousSelector.find('.form-date').each(function copyDateInHtml5() {
            // setTimeSlotElement(currentSelector.find('.form-date').eq(index), $(this).val());
          });
          previousSelector.find('.form-checkbox').each(function copyAllDay(index) {
            // Determine the state of the all_day checkbox.
            const previousIsEnabled = $(this).is(':checked');
            // Copy the all_day checkbox and set depending fields.
            $(currentSelector.find('.form-checkbox').eq(index)).prop('checked', previousIsEnabled);
            $(currentSelector.find('.form-checkbox').eq(index)).each(setAllDayTimeSlot);
          });
          // Copy the hours, minutes in the select box.
          previousSelector.find('.form-select').each(function copyTimeInSelect(index) {
            setTimeSlotElement(
              currentSelector.find('.form-select').eq(index),
              $(this).val(),
            );
          });
          // Copy the hours, minutes in the select list/HTML5 time element.
          previousSelector.find('.form-time').each(function copyTimeInHtml5(index) {
            setTimeSlotElement(
              currentSelector.find('.form-time').eq(index),
              $(this).val(),
            );
          });

          // If needed, show each Add-link of the day, after "Copy previous day".
          currentSelector.find('[id$=add]').each(showAddLink);
        }

        // When the document loads, hide every slot above the max slots per day.
        $('.js-office-hours-hide').hide();

        // Attach a function to each all_day checkbox, to enable/disable the times
        // when the checkbox is clicked, and initialize each one.
        $('[id$="all-day"]').bind('click', setAllDayTimeSlot)
        .each(setAllDayTimeSlot);

        // Attach a function to each add-link to show the next slot if clicked upon.
        // Show the Add-link, except if the next time slot is hidden.
        $('.js-office-hours-operations-wrapper [id$=add]').bind('click', addTimeSlot)
        .each(showAddLink);

        // Attach a function to each clear-link, to clear the slot if clicked upon.
        $('.js-office-hours-operations-wrapper [id$=clear]').bind('click', clearTimeSlot);

        // Attach a function to each copy-link, to copy the slot values
        // from previous day, when user clicks "Copy previous day".
        // @todo This works for Table widget, not yet for List Widget.
        $('.js-office-hours-operations-wrapper [id$=copy]').bind('click', copyPreviousDay);

        fixStriping();
      });
    },
  };
})(jQuery, Drupal);
