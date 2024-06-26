require=(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({4:[function(require,module,exports){
'use strict';

/**
 * Handles context switching between mobile and desktop headers.
 */
(function ($) {
  var CONTEXT_DESKTOP = 'desktop';
  var CONTEXT_MOBILE = 'mobile';
  var deviceContext = CONTEXT_DESKTOP;
  var menuContent = void 0;
  var searchContent = void 0;
  var accountContent = void 0;

  /**
   * Moves content from mobile header to desktop header.
   */
  function setDesktopContext() {
    if (deviceContext === CONTEXT_DESKTOP) {
      return;
    }
    deviceContext = CONTEXT_DESKTOP;
    searchContent.prependTo('#header-desktop__search-panel');
    accountContent.prependTo('#account-menu__panel--desktop');
    menuContent.prependTo('.header-desktop__primary .region--primary-menu__content');
  }

  /**
   * Moves content from desktop header to mobile header.
   */
  function setMobileContext() {
    if (deviceContext === CONTEXT_MOBILE) {
      return;
    }
    deviceContext = CONTEXT_MOBILE;
    searchContent.prependTo('#mobile-panel--search');
    accountContent.prependTo('#mobile-panel--account');
    menuContent.prependTo('#mobile-panel--menu');
  }

  /**
   * Adds a media query listener to move content between
   * Mobile and Desktop headers.
   */
  function setupContentSwitching() {
    // Create a condition that targets viewports at least 768px wide
    var mediaQuery = window.matchMedia('(min-width: 992px)');
    // Since the content of the mobile menu differs from the desktop menu.
    // We need to track which items we actually want to move.
    menuContent = $('.header-desktop__primary .region--primary-menu__content > .main-menu');
    searchContent = $('#header-desktop__search-panel > *');
    accountContent = $('#account-menu__panel--desktop > *');

    function handleContextChange(e) {
      // Check if the media query is true
      if (e.matches) {
        // Then log the following message to the console
        setDesktopContext();
      } else {
        setMobileContext();
      }
    }

    // Register event listener
    mediaQuery.addListener(handleContextChange);

    // Initial check
    handleContextChange(mediaQuery);
  };

  $(document).ready(setupContentSwitching);
})(jQuery);

},{}]},{},[4]);
