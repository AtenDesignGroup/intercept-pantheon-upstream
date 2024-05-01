/**
 * @file
 * Override views_autosubmit.js to allow refocusing of materialize select options.
 */

(function (Drupal) {
  // Due to the way we have to attach this library as a dependency to views_autosubmit,
  // (See intercept_base_library_info_alter) it will be loaded before
  // Drupal.behaviors.ViewsAutoSubmitRefocus is defined. We need to poll
  // for the existence of the original Drupal.behaviors.ViewsAutoSubmitRefocus
  // method before overidding it.

  const MAX_ATTEMPTS = 30;
  const INTERVAL = 100;

  let attempts = 0;

  /**
   * Poll for the existence of the original behavior.
   */
  const checkForOriginalBehavior = () => {
    if (attempts >= MAX_ATTEMPTS) {
      console.warn('Failed to override Drupal.behaviors.ViewsAutoSubmitRefocus.');
      return;
    }
    if (Drupal?.behaviors?.ViewsAutoSubmitRefocus) {
      overrideStoreFocusedElement();
    } else {
      attempts++;
      setTimeout(checkForOriginalBehavior, INTERVAL);
    }
  };

  checkForOriginalBehavior();

  /**
   * Override the storeFocusedElement and refocusElement methods to handle materialize select elements.
   */
  function overrideStoreFocusedElement() {
    const storeFocusedElement = Drupal.behaviors.ViewsAutoSubmitRefocus.storeFocusedElement;
    const refocusElement = Drupal.behaviors.ViewsAutoSubmitRefocus.refocusElement;

    /**
     *
     * @returns void
     */
    Drupal.behaviors.ViewsAutoSubmitRefocus.storeFocusedElement = function () {
      const activeElement = document.activeElement;

      // Check if focused on materialize select option <li> element.
      if (activeElement && activeElement.tagName === 'LI' && activeElement.parentElement.classList.contains('dropdown-content')) {
        // Clear the currently focused element.
        this.clearFocusedElement();

        // The focus element doesn't have a unique id or value. Just a position in the list.
        // We need to find the matching option in the hidden select element and key off that.

        // Find the index of the focused LI element
        const index = Array.from(activeElement.parentElement.children).indexOf(activeElement);

        // Find the select element that matches the dropdown.
        const select = activeElement.closest('.select-wrapper').querySelector('select');
        const selector = select.getAttribute('data-drupal-selector');

        this.focusedElement = `${selector}#${index}`;
        this.focusedElementIsMaterializeMultiSelect = true;
        return;
      }

      // If the focused element is the body, we don't want to store it. This can happen when
      // the dropdown is closed.
      if (this.focusedElementIsMaterializeMultiSelect === true && activeElement && activeElement.tagName === 'BODY') {
        return;
      }

      // If we've gotten this far, we're not focused on a materialize select option.
      // Call the original storeFocusedElement method.
      this.focusedElementIsMaterializeMultiSelect = false;
      storeFocusedElement.call(this);
    };

    /**
     * Override the refocusElement method to handle materialize select elements.
     *
     * @param {Element} context
     * @returns
     */
    Drupal.behaviors.ViewsAutoSubmitRefocus.refocusElement = function (context) {

      // If the focused element is a materialize select option, we need to handle it differently.
      if (this.focusedElementIsMaterializeMultiSelect) {
        const [selector, index] = this.focusedElement.split('#');
        const wrapper = context.querySelector(`.select-wrapper:has(select[data-drupal-selector="${selector}"])`);
        if (!wrapper) {
          return;
        }

        try {
          // Open the dropdown.
          const trigger = wrapper.querySelector('.dropdown-trigger');
          trigger.click();

          // We need to delay the focus event to give the dropdown time to open.
          setTimeout(() => {
            // Find the option to focus.
            const element = wrapper.querySelector(`.dropdown-content li:nth-child(${parseInt(index, 10) + 1})`);
            const formSelect = M.FormSelect.getInstance(wrapper.querySelector('select'));
            formSelect.dropdown.focusedIndex = parseInt(index, 10);
            if (element) {
              element.focus();
              this.clearFocusedElement();
            }
          }, 300);
        } catch (e) {
          console.warn('Failed to check if dropdown is open', e);
        }

        return;
      }

      // If we've gotten this far, we're not focused on a materialize select option.
      // Call the original refocusElement method.
      refocusElement.call(this, context);
    };
  }
})(Drupal);