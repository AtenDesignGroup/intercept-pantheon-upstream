require=(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({9:[function(require,module,exports){
'use strict';

(function ($) {

  $(document).ready(function () {

    // scroll to top functionality
    var topBtn = $('#scroll-to-top');

    $(window).scroll(function () {
      var scrollPos = $(window).scrollTop();

      if (scrollPos > 2000) {
        topBtn.fadeIn();
      } else {
        topBtn.fadeOut();
      }
    });

    topBtn.click(function () {
      console.log('clicked');
      $('html, body').animate({ scrollTop: '0px' }, 800);
    });
  });
})(jQuery);

(function ($, Drupal) {
  /**
   * Provides JS helper functions for user settings form.
   */
  Drupal.behaviors.materializeInitRichland = {
    attach: function attach(context, settings) {
      $('.views-exposed-form select').formSelect();
    }
  };
})(jQuery, Drupal);

},{}]},{},[9]);
