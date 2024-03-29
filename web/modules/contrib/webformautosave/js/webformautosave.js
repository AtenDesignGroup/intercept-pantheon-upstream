/**
 * @file
 * Webform Autosave behaviors.
 */

// eslint-disable-next-line func-names
(function ($, Drupal, drupalSettings) {
  // Set our primary store.
  const store = {
    csrfToken: null,
    activeClass: 'active',
    focusedElement: null,
    webform: null,
    submit: null,
    ...drupalSettings.webformautosave,
  };

  /**
   * The handler that triggers after ajax is complete.
   *
   * @return {Boolean}
   *   True if the element already has the active class.
   */
  function ajaxCompleteHandler() {
    // Get outta here if we didn't trigger the ajax.
    if (!$(store.submit).hasClass(store.activeClass)) {
      return true;
    }
    // Remove the active class.
    $(store.submit).removeClass(store.activeClass);
    // Ensure our focus doesn't change.
    $(store.focusedElement).focus();
  }

  /**
   * The handler bound to inputs on the form.
   *
   * @return {Boolean}
   *   True if the element already has the active class.
   */
  function inputHandler() {
    const webformId = store.webform.data('webform-id');
    const formStore = store.forms[webformId];
    store.submit = $(store.webform).find('[data-autosave-trigger="submit"]');
    // Get out of here if the submit is already happening.
    if ($(store.submit).hasClass(store.activeClass)) {
      return true;
    }
    // Fire off the draft submission.
    if (formStore) {
      // Prevent propagation by adding the active class.
      $(store.submit).addClass(store.activeClass);
      // eslint-disable-next-line func-names
      setTimeout(function () {
        // Submit our draft after the timeout.
        $(store.submit).click();
        // Ensure our focus doesn't change.
        $(store.focusedElement).focus();
      }, formStore.autosaveTime);
    }
  }

  /**
   * Bind event handlers to input fields.
   *
   * @param {object} form
   *   The form element.
   * @param {HTMLDocument | HTMLElement} context
   *   The current document.
   */
  function bindAutosaveHandlers(form, context) {
    store.webform = $('form.webform-submission-form');
    store.submit = $(form).find('[data-autosave-trigger="submit"]');

    // Add input, change and focus event listeners to each input.
    $(once('webformAutosaveBehaviorFiles', 'body', context))
      // Add an input listener to most inputs.
      .on(
        'input',
        'input:not([data-autosave-trigger="submit"]):not([type="file"]), select:not([data-autosave-trigger="submit"]), textarea:not([data-autosave-trigger="submit"])',
        inputHandler,
      )
      // Add a change listener to file inputs.
      .on(
        'change',
        'input[type="file"], select:not([data-autosave-trigger="submit"])',
        inputHandler,
      )
      .on('keydown keyup paste blur', '.ck-editor__editable', inputHandler)
      .on(
        'focus',
        'input:not([data-autosave-trigger="submit"]), select:not([data-autosave-trigger="submit"]), textarea:not([data-autosave-trigger="submit"])',
        // eslint-disable-next-line func-names
        function () {
          store.focusedElement = $(this);
        },
      );
  }

  // Remove the active class and perform other actions when ajax is complete.
  $(document).on('ajaxComplete', ajaxCompleteHandler);

  /**
   * Setup our default behaviors for the webformautosave module.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Specific description of this attach function goes here.
   */
  Drupal.behaviors.webformautosave = {
    attach(context, settings) {
      $(document, context).find('form.webform-submission-form');
      // This runs every time we attach (on backend ajax callback).
      store.forms = settings.webformautosave.forms;
      const webformForm = $('form.webform-submission-form');
      // Let's bind an input event to our inputs once.
      if ($(webformForm).length) {
        // eslint-disable-next-line func-names
        $(once('webformAutosaveBindHandler', webformForm)).each(
          function bindHandlers(form) {
            bindAutosaveHandlers(form, context);
          },
        );
      }
      // Ensure the wrapper for our draft submit is hidden.
      // eslint-disable-next-line func-names
      $(once('webformAutosaveHideWrapper', webformForm)).each(
        function hideDraftSubmit() {
          // Ensure the wrapper is hidden.
          $(webformForm).find('.webformautosave-trigger--wrapper').hide();
        },
      );
    },
  };
})(jQuery, Drupal, drupalSettings);
