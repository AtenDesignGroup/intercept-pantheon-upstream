require=(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({5:[function(require,module,exports){
'use strict';

/*
  main-menu.js
 */

(function ($, Drupal, tabbed) {
  Drupal.behaviors.mainMenu = {
    attach: function attach(context) {
      $('.header__site-navigation').once('mainMenu').each(function (index, element) {
        var mainMenuTabs = new tabbed('mainMenuTabs-' + index);

        mainMenuTabs.init($(element).find('.js-main-menu__tab'), $(element).find('.js-main-menu__panel'), {
          deselectAll: true,
          hover: false,
          container: '.header__site-navigation'
        });
      });

      // Collapse all tabs when a toggle is expanded.
      $('body').on('expand.toggle', '.js-toggle', function () {
        $('.js-main-menu__tab').trigger('deselect');
      });

      // Collapse all dropdowns when a tab is expanded.
      $('body').on('click', '.js-main-menu__tab, .slide-menu-toggle', function () {
        $('[data-toggleGroup="header"]').trigger('collapse');
      });

      // Collapse all dropdowns when a manage account trigger is clicked.
      $('body').on('click', '[for="slide-menu-toggle"]', function () {
        $('[data-toggleGroup="header"]').trigger('collapse');
      });
    }
  };
})(jQuery, Drupal, tabbed);

},{}]},{},[5]);
