/**
 * toggle.js
 */

import $ from 'jquery';
import debounce from 'lodash/debounce';

var toggle = function () {
  /**
   * Get Panel from toggle.
   */
  function getTogglePanel($toggle) {
    var $panel = $('#' + $toggle.attr('aria-controls'));
    return $panel.length > 0 ? $panel : false;
  }

  /**
   * Click Handler.
   */
  function collapseGroup(group) {
    var $group = $('[data-toggleGroup="' + group + '"]').trigger('collapse');
  }

  /**
   * Click Handler.
   */
  function onToggleClick(event) {
    if (event.type === 'keydown') {
      const code = event.charCode || event.keyCode;
      // 27 === 'Escape'
      if (code == 27) {
        $(event.currentTarget).trigger('collapse');
        return;
      }
    }

    if (event.type === 'keydown') {
      const code = event.charCode || event.keyCode;
      if (code !== 32 && code !== 13) {
        return;
      }
    }

    var $target = $(event.currentTarget);
    var wasExpanded = $target.attr('aria-expanded') === 'true';

    // Collapse other toggles in the group.
    var group = $target.attr('data-toggleGroup');
    if (group) {
      collapseGroup(group);
    }

    $target.trigger(wasExpanded ? 'collapse' : 'expand');

    event.preventDefault();
    return false;
  }

  function onHandleEscape(event) {
    const $panel = $(event.currentTarget);
    const $trigger = $(`[aria-controls="${$panel.attr('id')}"]`);
    if (event.type === 'keydown') {
      const code = event.charCode || event.keyCode;
      // 27 === 'Escape'
      if (code !== 27) {
        return;
      }
    }
    $trigger.trigger('collapse').focus();
  }

  /**
   * Collapse Handler.
   */
  function onToggleCollapse(event) {
    const $target = $(event.target);
    const $panel = getTogglePanel($target);

    // Update this toggle's state.
    $target.attr('aria-expanded', 'false');
    // Collapse this panel.
    if ($panel) {
      $panel.trigger('collapse');
      $panel.off('keydown', onHandleEscape);
    }
  }

  /**
   * Expand Handler.
   */
  function onToggleExpand(event) {
    var $target = $(event.target);
    var $panel = getTogglePanel($target);

    // Update this toggle's state.
    $target.attr('aria-expanded', 'true');
    // Expand this panel.
    if ($panel) {
      $panel.trigger('expand');
      $panel.on('keydown', onHandleEscape);
    }
  }

  /**
   * Expand Handler.
   */
  function onBodyClick(event) {
    var $target = $(event.target);

    // Ignore toggles.
    if ($target.is('.toggle')) {
      return false;
    }

    // Ignore children of expanded panels.
    if ($target.parents('[aria-expanded="true"]').length >= 1) {
      return;
    }

    // Close all focused toggles.
    $('[data-toggleFocused]').trigger('collapse');
  }

  // Click handler that closes tabs when clicked outside of a tab.
  function onGeneralFocus(event) {
    const $target = $(event.target);

    if (!$target.is('[aria-haspopup][aria-expanded="true"], [role="dialog"][aria-expanded="true"] *')) {
      $('[data-toggleFocused]').trigger('collapse');
    }
  }

  /**
   * Bind Toggles.
   */
  function bindUi() {
    var toggle = this,
      $body = $('body');

    $body.on(
      'touchstart.toggle mousedown.toggle keydown',
      '.toggle[aria-controls], .js-toggle[aria-controls]',
      // Keep this from calling multiple time.
      debounce(onToggleClick, 300, { leading: true, trailing: false })
    );
    $body.on('expand.toggle', onToggleExpand);
    $body.on('collapse.toggle', onToggleCollapse);
    $body.on(
      'touchstart.toggle mousedown.toggle',
      debounce(onBodyClick, 300, { leading: true, trailing: false })
    );
    $body.on('focusin.toggle', onGeneralFocus);
  }

  return {
    init: function () {
      bindUi();
    },
  };
};

(function ($) {
  $(document).ready(() => {
    toggle().init();
  });
})(jQuery);
