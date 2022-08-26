require=(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({2:[function(require,module,exports){
'use strict';

/*
  header-mobile.js
 */

(function ($, Drupal, tabbed) {
  Drupal.behaviors.headerMobile = {
    attach: function attach(context) {

      $('.js-header-mobile', context).once('headerMobile').each(function () {
        var headerMobileTabs = new tabbed('headerMobileTabs');

        // Prevent scrolling when the mobile menu is open.
        var $body = $('body');
        $body.on({
          'show.headerMobileTabs': function showHeaderMobileTabs() {
            $body.addClass('js-prevent-scroll');
          },
          'hide.headerMobileTabs': function hideHeaderMobileTabs() {
            $body.removeClass('js-prevent-scroll');
          }
        }, '.js-mobile-panel');

        headerMobileTabs.init($('.js-mobile-tab'), $('.js-mobile-panel'), {
          deselectAll: true,
          hover: false,
          container: '.js-header-mobile'
        });
      });
    }
  };
})(jQuery, Drupal, tabbed);

},{}]},{},[2]);
