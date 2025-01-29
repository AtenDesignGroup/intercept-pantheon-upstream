/*
 * Tabbed.js
 */

import $ from 'jQuery';

const tabbed = function(name) {
  if (name) {
    this.name = name;
  }
};

tabbed.prototype.options = {
  deselectAll: false,
  hover: false,
  container: null,
};

/**
 * Initialize Tabs.
 */
tabbed.prototype.init = function ($tabs, $panels, options) {
  const self = this;
  self.options = $.extend({}, this.options, options || {});
  this.$body = $('body');
  this.$container = this.options.container || null;

  if ($tabs.length > 0 && $panels.length > 0) {
    self.bindUi($tabs, $panels, self.options);
  }
};

/**
 * Bind Tab Links.
 */
tabbed.prototype.bindUi = function ($tabLinks, $tabPanels, options) {
  const self = this;

  // Click handler that closes tabs when clicked outside of a tab.
  function onGeneralClick(event) {
    const $target = $(event.target);

    if (options.deselectAll && !$target.is('[role="tab"][aria-selected="true"], [role="tab"][aria-selected="true"] *, [role="tabpanel"][aria-hidden="false"], [role="tabpanel"][aria-hidden="false"] *')) {
      deselectAllTabs();
    }
  }

  function clickTab(event) {
    const $target = $(event.target);
    const namespace = self.name;

    if (!$target.is($tabLinks)) {
      return false;
    }

    if (
      options.deselectAll === true &&
      $target.attr('aria-selected') === 'true'
    ) {
      $target.trigger('deselect.' + namespace);
    } else {
      $target.trigger('select.' + namespace);
    }

    return false;
  }

  function selectTab(event) {
    const $target = $(event.target);
    const $panel = $('#' + $target.attr('aria-controls'));
    const namespace = self.name;

    if (!$target.is($tabLinks)) {
      return false;
    }

    // Deselect all panels.
    $tabLinks.trigger('deselect.' + namespace);
    // Update this tab's attributes.
    $target.attr('aria-selected', true);
    // Show this panel.
    $panel.trigger('show.' + namespace);
  }

  function deselectTab(event) {
    const $target = $(event.target);
    const $panel = $('#' + $target.attr('aria-controls'));
    const namespace = self.name;

    if (!$target.is($tabLinks)) {
      return false;
    }

    $target.attr('aria-selected', false);
    $panel.not('[aria-hidden="true"]').trigger('hide.' + namespace);
  }

  // Deselect all panels.
  function deselectAllTabs() {
    $tabLinks.trigger('deselect.' + self.name);
  }

  function hidePanel(event) {
    $(event.target).attr('aria-hidden', true);
  }

  function showPanel(event) {
    $(event.target).attr('aria-hidden', false);
    $('#account-trigger').prop('checked', false );
  }

  function keyDownTab(event) {
    if (event.type === 'keydown') {
      const code = event.charCode || event.keyCode;
      // 27 === 'Escape'
      if (code !== 27) {
        return;
      }
    }
    const $tab = $(event.currentTarget);
    $tab.trigger('deselect.' + self.name)
  }

  function keyDownPanel(event) {
    if (event.type === 'keydown') {
      const code = event.charCode || event.keyCode;
      // 27 === 'Escape'
      if (code !== 27) {
        return;
      }
    }

    const $panel = $(event.currentTarget);
    const $tab = $(`[aria-controls="${$panel.attr('id')}"]`);

    if (this.options.deselectAll) {
      $tab.trigger('deselect.' + self.name)
    }
    $tab.focus();
  }

  self.$body.on('click.' + self.name, '[role="tab"]', clickTab);
  self.$body.on('click.' + self.name, onGeneralClick.bind(this));
  self.$body.on('focusin.' + self.name, onGeneralClick.bind(this));
  self.$body.on('select.' + self.name, '[role="tab"]', selectTab);
  self.$body.on('deselect.' + self.name, '[role="tab"]', deselectTab);
  self.$body.on('keydown.' + self.name, '[role="tab"]', keyDownTab.bind(this));
  self.$body.on('show.' + self.name, '[role="tabpanel"]', showPanel);
  self.$body.on('hide.' + self.name, '[role="tabpanel"]', hidePanel);
  self.$body.on('keydown.' + self.name, '[role="tabpanel"]', keyDownPanel.bind(this));

  if (options.hover) {
    self.$body.hoverIntent(selectTab, '[role="tab"]');

    if (this.$container) {
      self.$body.hoverIntent(
        function () {},
        function (e) {
          deselectAllTabs();
        },
        this.$container
      );
    }
  }
};

window.tabbed = tabbed;
