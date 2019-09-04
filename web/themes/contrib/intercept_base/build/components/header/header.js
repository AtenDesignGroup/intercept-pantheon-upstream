require=(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

(function ($) {

  $(document).ready(function () {
    var _this = this;

    $('.js-mobile-nav-trigger').on('change', function (e) {
      $('body')[e.target.checked ? 'addClass' : 'removeClass']('js-prevent-scroll');
      $(_this)[e.target.checked ? 'addClass' : 'removeClass']('checked');
    });

    $(document).click(function (e) {
      if (!$(e.target).closest('.header__utilities, .header__menu-main, .header__site-search').length) {
        $('.js-mobile-nav-trigger').prop('checked', false);
      }
    });

    // TODO: Combine with ^ above function
    $('.js-mobile-nav-trigger').on('change', function () {
      // Uncheck (toggle) other mobile dropdowns
      $(this).parent().siblings().children('.js-mobile-nav-trigger').prop('checked', false);
    });
  });
})(jQuery);

},{}]},{},[1]);
