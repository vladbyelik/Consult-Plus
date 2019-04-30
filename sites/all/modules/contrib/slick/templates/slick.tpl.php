<?php

/**
 * @file
 * Default theme implementation for the Slick carousel template.
 *
 * This file is not used by Slick, which uses theme_slick() instead for
 * performance reasons. The markup is the same, though, so if you want to use
 * template files rather than functions to extend Slick theming, copy this to
 * your custom theme. If you are comfortable with PHP, consider overriding
 * theme_slick() instead, such as MY_THEME_slick(), or regular
 * preprocess.
 *
 * Available variables:
 * - $items: The array of items containing main image/video/audio, and optional
 *     image/video/audio overlay and captions.
 * - $settings: A cherry-picked settings that mostly defines the slide HTML or
 *     layout, and none of JS settings/options which are defined at data-slick.
 * - $attributes: The array of attributes to hold the container classes, and id.
 * - $content_attributes: The array of attributes to hold the slick-slider and
 *     data-slick containing JSON object aka JS settings the Slick expects to
 *     override default options. We don't store these JS settings in the normal
 *     <head>, but inline within data-slick attribute instead.
 *
 * @see template_preprocess_slick()
 * @see theme_slick()
 */
?>
<div<?php print $attributes; ?>>
  <?php if (empty($settings['unslick'])): ?>
    <div<?php print $content_attributes; ?>>
  <?php endif; ?>

    <?php foreach ($items as $item): ?>
      <?php print render($item); ?>
    <?php endforeach; ?>

  <?php if (empty($settings['unslick'])): ?>
    </div>
    <nav<?php print $arrow_attributes; ?>>
      <?php print $js['prevArrow']; ?>
      <?php !empty($arrow_down_attributes) && print '<button' . $arrow_down_attributes . '></button>'; ?>
      <?php print $js['nextArrow']; ?>
    </nav>
  <?php endif; ?>
</div>
