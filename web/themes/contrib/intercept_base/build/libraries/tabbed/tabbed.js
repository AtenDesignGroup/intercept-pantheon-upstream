require=(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({40:[function(require,module,exports){
(function (global){
'use strict';

var _jquery = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null);

var _jquery2 = _interopRequireDefault(_jquery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var tabbed = function tabbed(name) {
  if (name) {
    this.name = name;
  }
}; /*
    * Tabbed.js
    */

tabbed.prototype.options = {
  deselectAll: false,
  hover: false,
  container: null
};

/**
 * Initialize Tabs.
 */
tabbed.prototype.init = function ($tabs, $panels, options) {
  var self = this;
  self.options = _jquery2.default.extend({}, this.options, options || {});
  this.$body = (0, _jquery2.default)('body');
  this.$container = this.options.container || null;

  if ($tabs.length > 0 && $panels.length > 0) {
    self.bindUi($tabs, $panels, self.options);
  }
};

/**
 * Bind Tab Links.
 */
tabbed.prototype.bindUi = function ($tabLinks, $tabPanels, options) {
  var self = this;

  // Click handler that closes tabs when clicked outside of a tab.
  function onGeneralClick(event) {
    var $target = (0, _jquery2.default)(event.target);

    if (options.deselectAll && !$target.is('[role="tab"][aria-selected="true"], [role="tab"][aria-selected="true"] *, [role="tabpanel"][aria-hidden="false"], [role="tabpanel"][aria-hidden="false"] *')) {
      deselectAllTabs();
    }
  }

  function clickTab(event) {
    var $target = (0, _jquery2.default)(event.target);
    var namespace = self.name;

    if (!$target.is($tabLinks)) {
      return false;
    }

    if (options.deselectAll === true && $target.attr('aria-selected') === 'true') {
      $target.trigger('deselect.' + namespace);
    } else {
      $target.trigger('select.' + namespace);
    }

    return false;
  }

  function selectTab(event) {
    var $target = (0, _jquery2.default)(event.target);
    var $panel = (0, _jquery2.default)('#' + $target.attr('aria-controls'));
    var namespace = self.name;

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
    var $target = (0, _jquery2.default)(event.target);
    var $panel = (0, _jquery2.default)('#' + $target.attr('aria-controls'));
    var namespace = self.name;

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
    (0, _jquery2.default)(event.target).attr('aria-hidden', true);
  }

  function showPanel(event) {
    (0, _jquery2.default)(event.target).attr('aria-hidden', false);
    (0, _jquery2.default)('#account-trigger').prop('checked', false);
  }

  function keyDownTab(event) {
    if (event.type === 'keydown') {
      var code = event.charCode || event.keyCode;
      // 27 === 'Escape'
      if (code !== 27) {
        return;
      }
    }
    var $tab = (0, _jquery2.default)(event.currentTarget);
    $tab.trigger('deselect.' + self.name);
  }

  function keyDownPanel(event) {
    if (event.type === 'keydown') {
      var code = event.charCode || event.keyCode;
      // 27 === 'Escape'
      if (code !== 27) {
        return;
      }
    }

    var $panel = (0, _jquery2.default)(event.currentTarget);
    var $tab = (0, _jquery2.default)('[aria-controls="' + $panel.attr('id') + '"]');

    if (this.options.deselectAll) {
      $tab.trigger('deselect.' + self.name);
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
      self.$body.hoverIntent(function () {}, function (e) {
        deselectAllTabs();
      }, this.$container);
    }
  }
};

window.tabbed = tabbed;

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})
},{}]},{},[40]);
