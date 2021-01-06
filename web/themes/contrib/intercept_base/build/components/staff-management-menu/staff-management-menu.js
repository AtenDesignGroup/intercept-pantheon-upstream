require=(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({3:[function(require,module,exports){
"use strict";

// code to uncheck radio button (collapse an expanded submenu)
window.onload = function () {
  // check to see if menu exists (won't exist for customers or non-logged-in users)
  if (document.getElementById("slide-menu-toggle")) {
    var openSlideMenu = function openSlideMenu() {
      if (slideMenuToggle.checked) {
        slideMenuToggle.checked = false;
      }
    };

    var collapseSubmenu = function collapseSubmenu() {
      // if the clicked submenu is already open or if user clicked slideMenuToggle
      if (subMenuOpen == this || this.id == 'slide-menu-toggle') {
        subMenuOpen ? subMenuOpen.checked = false : null;
        subMenuOpen = null;
      } else {
        subMenuOpen = this; // assign the clicked submenu to the subMenuOpen var
      }
    };

    // close slide-menu and collapse any expanded


    var menuItems = document.querySelectorAll(".accordion INPUT[type='radio']");
    var slideMenuToggle = document.getElementById("slide-menu-toggle");
    var subMenuOpen = null;

    menuItems.forEach(function (menuItem) {
      menuItem.addEventListener("click", openSlideMenu);
      menuItem.addEventListener("click", collapseSubmenu);
    });

    slideMenuToggle.addEventListener("click", collapseSubmenu);
  }
};

},{}]},{},[3]);
