<?php

/**
 * @file
 * Provides common update utility for Slick Fields and Slick Views 3.x.
 *
 * This file is not used at front-end. Instead being used for hook_update().
 *
 * @see slick_fields.install
 * @see slick_views.install
 */

use Drupal\slick\SlickDefault;

/**
 * Helper function to update formatter and views style settings.
 *
 * This conversion is an attempt to make generic $settings, not bound to slide.
 */
function _slick_update_formatter_settings(array &$settings = []) {
  $deps = [
    'colorbox_style',
    'picture',
    'picture_style',
    'picture_fallback',
    'slide_field_wrapper',
    'thumbnail_hover',
    'visible_slides',
    'aspect_ratio',
    'mousewheel',
  ];

  foreach (SlickDefault::deprecatedSettings() as $key) {
    $old_key = $key == 'class' ? 'classes' : $key;
    if (isset($settings['slide_' . $old_key])) {
      $settings[$key] = $settings['slide_' . $old_key];
      unset($settings['slide_' . $old_key]);
    }
  }

  if (!empty($settings['skin']) && $settings['skin'] == '3d-back') {
    $settings['skin'] = 'd3-back';
  }
  if (!empty($settings['picture']) && !empty($settings['picture_style'])) {
    $settings['responsive_image_style'] = $settings['picture_style'];
  }
  if (!empty($settings['visible_slides'])) {
    $settings['visible_items'] = $settings['visible_slides'];
  }
  if (!empty($settings['aspect_ratio'])) {
    $settings['ratio'] = $settings['aspect_ratio'];
  }
  if (!empty($settings['colorbox_style'])) {
    $settings['box_style'] = $settings['colorbox_style'];
  }
  if (!empty($settings['cache']) && $settings['cache'] == 'persistent') {
    $settings['cache'] = CACHE_TEMPORARY;
  }
  if (!empty($settings['media_switch'])) {
    $switch = str_replace('-switch', '', $settings['media_switch']);
    $settings['media_switch'] = $switch == 'iframe' ? 'media' : $switch;
  }

  // Only Slick Views has Vanilla for Slick 2.x.
  if (isset($settings['slide_field_wrapper'])) {
    $settings['vanilla'] = empty($settings['slide_field_wrapper']) ? 1 : 0;
  }

  // Empty or not, safe to cleanup old settings.
  foreach ($deps as $key) {
    unset($settings[$key]);
  }

  return $settings;
}
