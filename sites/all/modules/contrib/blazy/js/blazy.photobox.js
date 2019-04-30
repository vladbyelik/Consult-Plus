/**
 * @file
 * Provides Photobox integration for Image and Media fields.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.blazy = Drupal.blazy || {};

  /**
   * Blazy Colorbox utility functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} box
   *   The photobox HTML element.
   */
  function blazyPhotobox(i, box) {
    $(box).photobox('a[data-photobox-trigger]', {thumb: '> [data-thumb]', thumbAttr: 'data-thumb'}, Drupal.blazy.photobox);
  }

  /**
   * Attaches blazy photobox behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyPhotobox = {
    attach: function (context) {
      $('[data-photobox-gallery]', context).once('blazy-photobox', blazyPhotobox);
    }
  };

  /**
   * Callback for custom captions.
   */
  Drupal.blazy.photobox = function () {
    var $caption = $(this).next(".litebox-caption");

    if ($caption.length) {
      $('#pbCaption .title').html($caption.html());
    }
  };

}(jQuery, Drupal));
