/**
 * @file
 * Provides Intersection Observer API or bLazy loader.
 *
 * @nottodo remove dblazy for jQuery at D7. Less than 1K gzip is ok.
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API
 * @see https://developers.google.com/web/updates/2016/04/intersectionobserver
 */

(function ($, Drupal, drupalSettings, _db, window, document) {

  'use strict';

  /**
   * Blazy public methods.
   *
   * @namespace
   */
  Drupal.blazy = Drupal.blazy || {
    init: null,
    windowWidth: 0,
    blazySettings: {},
    ioSettings: {},
    options: {},
    globals: function () {
      var me = this;
      var commons = {
        success: me.clearing.bind(me),
        error: me.clearing.bind(me),
        selector: '.b-lazy',
        errorClass: 'b-error',
        successClass: 'b-loaded'
      };

      return _db.extend(me.blazySettings, me.ioSettings, commons);
    },

    clearing: function (el) {
      var me = this;
      var ie = _db.hasClass(el, 'b-responsive') && el.hasAttribute('data-pfsrc');

      // The .b-lazy element can be attached to IMG, or DIV as CSS background.
      el.className = el.className.replace(/(\S+)loading/, '');

      // The .is-loading can be .grid, .slide__content, .box__content, etc.
      var loaders = [
        _db.closest(el, '.is-loading'),
        _db.closest(el, '[class*="loading"]')
      ];

      // Also cleans up closest containers containing loading class.
      _db.forEach(loaders, function (wrapEl) {
        if (wrapEl !== null) {
          wrapEl.className = wrapEl.className.replace(/(\S+)loading/, '');
        }
      });

      // @see http://scottjehl.github.io/picturefill/
      if (window.picturefill && ie) {
        window.picturefill({
          reevaluate: true,
          elements: [el]
        });
      }

      // Provides event listeners for easy overrides without full overrides.
      _db.trigger(el, 'blazy.done', {options: me.options});
    },

    isIo: function () {
      return this.ioSettings && this.ioSettings.enabled && 'IntersectionObserver' in window;
    },

    isBlazy: function () {
      return !this.isIo() && 'Blazy' in window;
    },

    run: function (opts) {
      return this.isIo() ? new BioMedia(opts) : new Blazy(opts);
    }
  };

  /**
   * Blazy utility functions.
   *
   * @param {HTMLElement} elm
   *   The Blazy HTML element.
   */
  function doBlazy(elm) {
    var me = Drupal.blazy;
    var dataAttr = elm.getAttribute('data-blazy');
    var data = !dataAttr || dataAttr === '1' ? {} : _db.parse(dataAttr);
    var opts = _db.extend({}, me.globals(), data);
    var ratios = elm.querySelectorAll('[data-dimensions]');
    var loopRatio = ratios.length > 0;
    var fallbackRatios = elm.querySelectorAll('[data-ratio]');
    var loopFallbackRatio = fallbackRatios.length > 0;

    /**
     * Updates the dynamic multi-breakpoint aspect ratio.
     *
     * This only applies to multi-serving images with aspect ratio fluid if
     * each element contains [data-dimensions] attribute.
     * Static single aspect ratio, e.g. `media--ratio--169`, will be ignored,
     * and will use CSS instead.
     *
     * @param {HTMLElement} el
     *   The .media--ratio HTML element.
     */
    function updateRatio(el) {
      var dimensions = !el.getAttribute('data-dimensions') ? false : _db.parse(el.getAttribute('data-dimensions'));

      if (!dimensions) {
        return;
      }

      var keys = Object.keys(dimensions);
      var xs = keys[0];
      var xl = keys[keys.length - 1];
      var mw = function (w) {
        return w >= me.windowWidth;
      };
      var pad = keys.filter(mw).map(function (v) {
        return dimensions[v];
      }).shift();

      if (pad === 'undefined') {
        pad = dimensions[me.windowWidth >= xl ? xl : xs];
      }

      if (pad !== 'undefined') {
        el.style.paddingBottom = pad + '%';
      }

      el.removeAttribute('data-ratio');
    }

    /**
     * Fix for Twig inline_template and Views rewrite striping out style.
     *
     * @param {HTMLElement} el
     *   The .media--ratio--fluid|enforced HTML element.
     */
    function updateFallbackRatio(el) {
      // Only rewrites if the style is indeed stripped out by Twig, and not set.
      if (!el.hasAttribute('style')) {
        el.style.paddingBottom = el.getAttribute('data-ratio') + '%';
      }
      el.removeAttribute('data-ratio');
    }

    // Initializes IntersectionObserver or Blazy instance.
    me.options = opts;
    me.init = me.run(opts);

    // Reacts on resizing per 200ms, and the magic () also does it on page load.
    _db.resize(function () {
      me.windowWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth || window.screen.width;

      if (loopRatio) {
        _db.forEach(ratios, updateRatio, elm);
      }
      else if (loopFallbackRatio) {
        _db.forEach(fallbackRatios, updateFallbackRatio, elm);
      }

      // BC with bLazy, IO doesn't need to revalidate, Slick multiple-view does.
      if (me.isBlazy() || _db.hasClass(elm, 'blazy--revalidate')) {
        me.init.revalidate(true);
      }
    })();

    _db.addClass(elm, 'blazy--on');
  }

  /**
   * Attaches blazy behavior to HTML element identified by [data-blazy].
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazy = {
    attach: function (context) {
      var me = Drupal.blazy;
      var el = document.querySelector('[data-blazy]');

      // Unlike D8, we must assign values outside Drupal.blazy. Alternatively
      // make these functions which are not good for the need.
      me.blazySettings = drupalSettings.blazy || {};
      me.ioSettings = drupalSettings.blazyIo || {};

      // Runs basic Blazy if no [data-blazy] found, probably a single image.
      // Cannot use .contains(), as IE11 doesn't support method 'contains'.
      if (el === null) {
        me.options = me.globals();
        me.init = me.run(me.globals());
        return;
      }

      // Runs Blazy with multi-serving images, and aspect ratio supports.
      var blazies = document.querySelectorAll('.blazy:not(.blazy--on)');
      _db.once(_db.forEach(blazies, doBlazy));
    }
  };

}(jQuery, Drupal, Drupal.settings, dBlazy, this, this.document));
