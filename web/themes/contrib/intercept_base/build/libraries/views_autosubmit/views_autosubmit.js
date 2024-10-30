require=(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({43:[function(require,module,exports){
'use strict';

var _slicedToArray = function () { function sliceIterator(arr, i) { var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"]) _i["return"](); } finally { if (_d) throw _e; } } return _arr; } return function (arr, i) { if (Array.isArray(arr)) { return arr; } else if (Symbol.iterator in Object(arr)) { return sliceIterator(arr, i); } else { throw new TypeError("Invalid attempt to destructure non-iterable instance"); } }; }();

/**
 * @file
 * Override views_autosubmit.js to allow refocusing of materialize select options.
 */

(function (Drupal) {
  // Due to the way we have to attach this library as a dependency to views_autosubmit,
  // (See intercept_base_library_info_alter) it will be loaded before
  // Drupal.behaviors.ViewsAutoSubmitRefocus is defined. We need to poll
  // for the existence of the original Drupal.behaviors.ViewsAutoSubmitRefocus
  // method before overidding it.

  var MAX_ATTEMPTS = 30;
  var INTERVAL = 100;

  var attempts = 0;

  /**
   * Poll for the existence of the original behavior.
   */
  var checkForOriginalBehavior = function checkForOriginalBehavior() {
    if (attempts >= MAX_ATTEMPTS) {
      console.warn('Failed to override Drupal.behaviors.ViewsAutoSubmitRefocus.');
      return;
    }
    if (typeof Drupal.behaviors !== 'undefined' && Drupal.behaviors.ViewsAutoSubmitRefocus) {
      overrideStoreFocusedElement();
    } else {
      attempts++;
      setTimeout(checkForOriginalBehavior, INTERVAL);
    }
  };

  checkForOriginalBehavior();

  /**
   * Override the storeFocusedElement and refocusElement methods to handle materialize select elements.
   */
  function overrideStoreFocusedElement() {
    var storeFocusedElement = Drupal.behaviors.ViewsAutoSubmitRefocus.storeFocusedElement;
    var refocusElement = Drupal.behaviors.ViewsAutoSubmitRefocus.refocusElement;

    /**
     *
     * @returns void
     */
    Drupal.behaviors.ViewsAutoSubmitRefocus.storeFocusedElement = function () {
      var activeElement = document.activeElement;

      // Check if focused on materialize select option <li> element.
      if (activeElement && activeElement.tagName === 'LI' && activeElement.parentElement.classList.contains('dropdown-content')) {
        // Clear the currently focused element.
        this.clearFocusedElement();

        // The focus element doesn't have a unique id or value. Just a position in the list.
        // We need to find the matching option in the hidden select element and key off that.

        // Find the index of the focused LI element
        var index = Array.from(activeElement.parentElement.children).indexOf(activeElement);

        // Find the select element that matches the dropdown.
        var select = activeElement.closest('.select-wrapper').querySelector('select');
        var selector = select.getAttribute('data-drupal-selector');

        this.focusedElement = selector + '#' + index;
        this.focusedElementIsMaterializeMultiSelect = true;
        return;
      }

      // If the focused element is the body, we don't want to store it. This can happen when
      // the dropdown is closed.
      if (this.focusedElementIsMaterializeMultiSelect === true && activeElement && activeElement.tagName === 'BODY') {
        return;
      }

      // If we've gotten this far, we're not focused on a materialize select option.
      // Call the original storeFocusedElement method.
      this.focusedElementIsMaterializeMultiSelect = false;
      storeFocusedElement.call(this);
    };

    /**
     * Override the refocusElement method to handle materialize select elements.
     *
     * @param {Element} context
     * @returns
     */
    Drupal.behaviors.ViewsAutoSubmitRefocus.refocusElement = function (context) {
      var _this = this;

      // If the focused element is a materialize select option, we need to handle it differently.
      if (this.focusedElementIsMaterializeMultiSelect) {
        var _focusedElement$split = this.focusedElement.split('#'),
            _focusedElement$split2 = _slicedToArray(_focusedElement$split, 2),
            selector = _focusedElement$split2[0],
            index = _focusedElement$split2[1];

        var wrapper = context.querySelector('.select-wrapper:has(select[data-drupal-selector="' + selector + '"])');
        if (!wrapper) {
          return;
        }

        try {
          // Open the dropdown.
          var trigger = wrapper.querySelector('.dropdown-trigger');
          trigger.click();

          // We need to delay the focus event to give the dropdown time to open.
          setTimeout(function () {
            // Find the option to focus.
            var element = wrapper.querySelector('.dropdown-content li:nth-child(' + (parseInt(index, 10) + 1) + ')');
            var formSelect = M.FormSelect.getInstance(wrapper.querySelector('select'));
            formSelect.dropdown.focusedIndex = parseInt(index, 10);
            if (element) {
              element.focus();
              _this.clearFocusedElement();
            }
          }, 300);
        } catch (e) {
          console.warn('Failed to check if dropdown is open', e);
        }

        return;
      }

      // If we've gotten this far, we're not focused on a materialize select option.
      // Call the original refocusElement method.
      refocusElement.call(this, context);
    };
  }
})(Drupal);

},{}]},{},[43]);
