require=(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({3:[function(require,module,exports){
'use strict';

(function ($, Drupal) {
  $(document).ready(function () {

    // logic for the logged in greeting in block--useraccountmenu.html.twig
    var hour = new Date().getHours();
    var salutation = void 0;
    if (hour < 12) {
      salutation = 'Good morning';
    } else if (hour < 17) {
      salutation = 'Good afternoon';
    } else {
      salutation = 'Good evening';
    }
    $('#salutation').text(Drupal.t(salutation));

    // Remove anchors from menu items with "no-link" class.
    $(".region--primary-menu ul.menu li a.no-link").each(function () {
      var textContent = document.createElement("span");
      var classes = $(this).attr('class').split(/\s+/);
      $.each(classes, function (index, item) {
        textContent.classList.add(item);
      });

      textContent.innerHTML = $(this).html();
      var parent = $(this).parent();
      parent.find("a").remove();
      parent.append(textContent);
    });
  });
})(jQuery, Drupal);

},{}]},{},[3]);
