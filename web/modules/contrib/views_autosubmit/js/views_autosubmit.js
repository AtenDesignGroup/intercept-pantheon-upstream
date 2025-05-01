/**
 * Views exposed filter autosubmit js for Drupal.
 */

(function ($, drupalSettings) {
  Drupal.behaviors.ViewsAutoSubmitRefocus = {

    /**
     * The most recently focused element.
     */
    focusedElement: null,

    /**
     * The cursor position of the most recently focused element.
     **/
    cursorPosition: [0,0],

    /**
     * Saves the currently focused element so we can refocus it after
     * the content refreshes. If the element is an input, also saves
     * the cursor position. So we can restore it.
     */
    storeFocusedElement: function () {
      const activeElement = document.activeElement;
      if (activeElement) {
        this.clearFocusedElement();
        this.focusedElement = activeElement.getAttribute('data-drupal-selector');
        if (typeof activeElement.setSelectionRange === 'function') {
          this.cursorPosition = [activeElement.selectionStart, activeElement.selectionEnd];
        }
      }
    },

    /**
     * Sets the focus on the last focused element and restores the cursor position
     * if applicable.
     *
     * @param {Document|HTMLElement} context
     */
    refocusElement: function(context) {
      const element = context.querySelector(`[data-drupal-selector="${this.focusedElement}"]`);
      if (element) {
        element.focus();
        try {
          if (typeof element.setSelectionRange === 'function') {
            element.setSelectionRange(...this.cursorPosition);
          }
        } catch (error) {
          console.warn('Failed to set cursor position', error);
        }
        this.clearFocusedElement();
      }
    },

    /**
     * Resets the focused element and cursor position to defaults.
     */
    clearFocusedElement: function () {
      this.focusedElement = null;
      this.cursorPosition = [0,0];
    },

    /**
     * Maintain the currently the document's active element between AJAX requests.
     */
    attach: function(context) {
      // Refocus on the last focused element after the form has been submitted.
      this.refocusElement(context);
    }
  }

Drupal.behaviors.ViewsAutoSubmit = {
  attach: function(context) {
    // 'this' references the form element.
    function triggerSubmit (e) {
      var $this = $(this);
      if (!$this.hasClass('views-ajaxing')) {
        if (document.activeElement) {
          Drupal.behaviors.ViewsAutoSubmitRefocus.storeFocusedElement();
        }
        $this.find('.views-auto-submit-click').click();
      }
    }

    // the change event bubbles so we only need to bind it to the outer form
    $(once('views-auto-submit', 'form.views-auto-submit-full-form, .views-auto-submit-full-form form', context))
      .add('.views-auto-submit', context)
      .filter('form, input:not(:text, :submit, [type="date"])')
      .change(function (e) {
        // don't trigger on text change for full-form.
        if ($(e.target).is(':not(:text, :submit, [type="date"], select)')) {
          triggerSubmit.call(e.target.form);
        }
      });

    // e.keyCode: key
    var discardKeyCode = [
      16, // shift
      17, // ctrl
      18, // alt
      20, // caps lock
      33, // page up
      34, // page down
      35, // end
      36, // home
      37, // left arrow
      38, // up arrow
      39, // right arrow
      40, // down arrow
      9, // tab
      13, // enter
      27  // esc
    ];
    // Don't wait for change event on textfields.
    $(once('views-auto-submit', $(context)
      .find('.views-auto-submit-full-form input:text, input:text.views-auto-submit, .views-auto-submit-full-form input[type="date"]')))
      .each(function() {
        // Each text input element has his own timeout.
        var timeoutID = 0;
        $(this)
          .bind('keydown keyup', function (e) {
            if ($.inArray(e.keyCode, discardKeyCode) === -1) {
              timeoutID && clearTimeout(timeoutID);
            }
          })
          .keyup(function(e) {
            if ($.inArray(e.keyCode, discardKeyCode) === -1) {
              timeoutID = setTimeout($.proxy(triggerSubmit, this.form), 2500);
            }
          })
          .change(function(e) {
            timeoutID = setTimeout($.proxy(triggerSubmit, this.form), 2500);
          });
      });
    // Set select fields to only submit after LAST interaction.
    $(once('views-auto-submit', $(context)
      .find('.views-auto-submit-full-form select')))
      .each(function() {
        // Each text input element has his own timeout.
        var timeoutID = 0;
        $(this)
          .change(function(e) {
            clearTimeout(timeoutID);
            timeoutID = setTimeout($.proxy(triggerSubmit, this.form), 2500);
          });
      });
  }
}
}(jQuery, drupalSettings));
