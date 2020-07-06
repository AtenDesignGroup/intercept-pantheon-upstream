require=(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({4:[function(require,module,exports){
'use strict';

// Used to make each "What's New" item on the home page clickable (instead of having duplicate links on image and title)
(function ($) {
  $('body').on('click.blockLink', '.js--block-link', function () {
    var href = $(this).attr('data-href');
    if (href) {
      window.location.href = href;
    }
  });
})(jQuery);

},{}]},{},[4]);
