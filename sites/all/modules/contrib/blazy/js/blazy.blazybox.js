/**
 * @file
 * Provides Blazybox utility, a fullscreen video view.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.blazyBox = Drupal.blazyBox || {};

  Drupal.blazyBox.$el = $('.blazybox');

  /**
   * Theme function for a fullscreen lightbox video container.
   *
   * @return {HTMLElement}
   *   Returns a HTMLElement object.
   */
  Drupal.theme.blazyBox = function () {
    var html;

    html = '<div id="blazybox" class="blazybox element-invisible" tabindex="-1" role="dialog" aria-hidden="true">';
    html += '<div class="blazybox__content">' + Drupal.t('Dynamic video content.') + '</div>';
    html += '<button class="blazybox__close" data-role="none">&times;</button>';
    html += '</div>';

    return html;
  };

  /**
   * Theme function for a standalone fullscreen video.
   *
   * @param {Object} settings
   *   An object containing the embed url.
   *
   * @return {HTMLElement}
   *   Returns a HTMLElement object.
   */
  Drupal.theme.blazyBoxMedia = function (settings) {
    var html;

    html = '<div class="media media--fullscreen">';
    html += '<iframe src="' + settings.embedUrl + '" width="100%" height="100%" allowfullscreen></iframe>';
    html += '</div>';

    return html;
  };

  /**
   * Open the blazyBox.
   *
   * @param {string} embedUrl
   *   The video embed url.
   */
  Drupal.blazyBox.open = function (embedUrl) {
    var me = this;
    var mediaEl = Drupal.theme('blazyBoxMedia', {embedUrl: embedUrl});

    Drupal.attachBehaviors(me.$el[0]);
    $('.blazybox__content', me.$el).html(mediaEl);

    me.$el.removeClass('element-invisible').attr('aria-hidden', false);
    $('body').addClass('is-blazybox--open');
  };

  /**
   * Attach the blazyBox.
   */
  Drupal.blazyBox.attach = function () {
    if (!$('.blazybox').length) {
      $('body').append(Drupal.theme('blazyBox'));
    }
  };

  /**
   * Close the blazyBox.
   *
   * @param {Event} e
   *   The mouse event triggering the close.
   */
  Drupal.blazyBox.close = function (e) {
    var $el = Drupal.blazyBox.$el;
    e.preventDefault();

    $el.addClass('element-invisible').attr('aria-hidden', true);
    $el.find('.blazybox__content').empty();
    $('body').removeClass('is-blazybox--open');
  };

  /**
   * BlazyBox utility functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} box
   *   The blazybox HTML element.
   */
  function doBlazyBox(i, box) {
    var $box = $(box);

    $box.on('click', '.blazybox__close', Drupal.blazyBox.close);
    $box.addClass('blazybox--on');

    Drupal.blazyBox.$el = $box;
  }

  /**
   * Attaches Blazybox behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyBox = {
    attach: function (context) {
      $('.blazybox:not(.blazybox--on)', context).once('blazybox', doBlazyBox);
    }
  };

})(jQuery, Drupal);
