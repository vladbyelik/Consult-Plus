/**
 * @file
 * Provides admin utilities.
 */

(function ($, Drupal) {

  'use strict';

  // Supports for jQuery < 1.6 admin pages.
  if (typeof $.fn.prop !== 'function') {
    $.fn.prop = function (name, value) {
      if (typeof value === 'undefined') {
        return this.attr(name);
      }
      else {
        return this.attr(name, value);
      }
    };
  }

  /**
   * Blazy admin utility functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} form
   *   The Blazy form wrapper HTML element.
   */
  function blazyForm(i, form) {
    var t = $(form);

    // Works around for Drupal 7 which doesn't supports wrapper_attributes.
    $('[data-blazy-form-item]', t).each(function () {
      $(this).parent().addClass('form-item--' + $(this).data('blazyFormItem'));
    });

    $('[data-blazy-tooltip]', t).each(function () {
      $(this).parent().addClass('form-item--tooltip-' + $(this).data('blazyTooltip'));
    });

    $('[data-blazy-tooltip-direction]', t).each(function () {
      $(this).parent().addClass('form-item--tooltip-' + $(this).data('blazyTooltipDirection'));
    });

    $('.details-legend-prefix', t).removeClass('element-invisible');

    $('.form__title--grid', t).parent().addClass('form-item--grid-header');

    t[$('.form-checkbox--vanilla', t).prop('checked') ? 'addClass' : 'removeClass']('form--vanilla-on');

    t.on('click', '.form-checkbox', function () {
      var $input = $(this);
      $input[$input.prop('checked') ? 'addClass' : 'removeClass']('on');

      if ($input.hasClass('form-checkbox--vanilla')) {
        t[$input.prop('checked') ? 'addClass' : 'removeClass']('form--vanilla-on');
      }
    });

    $('select[name$="[style]"]', t).on('change', function () {
      var $select = $(this);

      t.removeClass(function (index, css) {
        return (css.match(/(^|\s)form--style-\S+/g) || []).join(' ');
      });

      if ($select.val() === '') {
        t.addClass('form--style-off');
      }
      else {
        t.addClass('form--style-on form--style-' + $select.val());
      }
    }).change();

    $('select[name$="[grid]"]', t).on('change', function () {
      var $select = $(this);

      t[$select.val() === '' ? 'removeClass' : 'addClass']('form--grid-on');
    }).change();

    $('select[name$="[responsive_image_style]"]', t).on('change', function () {
      var $select = $(this);
      t[$select.val() === '' ? 'removeClass' : 'addClass']('form--responsive-image-on');
    }).change();

    t.addClass('form--media-switchoff');
    $('select[name$="[media_switch]"]', t).on('change', function () {
      var $select = $(this);
      var val = $select.val();

      t.removeClass(function (index, css) {
        return (css.match(/(^|\s)form--media-switch-\S+/g) || []).join(' ');
      });

      t[val === '' ? 'removeClass' : 'addClass']('form--media-switch-' + val);
      if (val) {
        t.removeClass('form--media-switchoff');
        if (val.indexOf('box') > 0 || val.indexOf('photo') > 0) {
          t.addClass('form--media-switch-litebox');
        }
      }
      else {
        t.addClass('form--media-switchoff');
      }
    }).change();

    t.on('mouseenter touchstart', '.hint', function () {
      $(this).closest('.form-item').addClass('is-hovered');
    });

    t.on('mouseleave touchend', '.hint', function () {
      $(this).closest('.form-item').removeClass('is-hovered');
    });

    t.on('click', '.hint', function () {
      $('.form-item.is-selected', t).removeClass('is-selected');
      $(this).parent().toggleClass('is-selected');
    });

    t.on('click', '.description', function () {
      $(this).closest('.is-selected').removeClass('is-selected');
    });

    t.on('focus', '.js-expandable', function () {
      $(this).parent().addClass('is-focused');
    });

    t.on('blur', '.js-expandable', function () {
      $(this).parent().removeClass('is-focused');
    });
  }

  /**
   * Blazy admin tooltip function.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} elm
   *   The Blazy form item description HTML element.
   */
  function blazyTooltip(i, elm) {
    var $tip = $(elm);

    if (!$tip.siblings('.hint').length) {
      $tip.closest('.form-item').append('<span class="hint">?</span>');
    }
  }

  /**
   * Blazy admin checkbox function.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} elm
   *   The Blazy form item checkbox HTML element.
   */
  function blazyCheckbox(i, elm) {
    var $elm = $(elm);
    if (!$elm.next('.field-suffix').length) {
      $elm.after('<span class="field-suffix"></span>');
    }
  }

  /**
   * Attaches Blazy form behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyAdmin = {
    attach: function (context) {
      var $form = $('.form--slick', context);

      $('.description', $form).once('blazy-tooltip', blazyTooltip);
      $('.form-checkbox', $form).once('blazy-checkbox', blazyCheckbox);

      $form.once('blazy-admin', blazyForm);
    }
  };

})(jQuery, Drupal);
