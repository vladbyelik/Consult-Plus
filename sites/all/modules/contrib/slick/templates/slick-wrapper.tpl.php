<?php

/**
 * @file
 * Default theme implementation for the Slick carousel wrapper template.
 *
 * This file is not used by Slick, which uses theme_slick_wrapper() instead for
 * performance reasons. The markup is the same, though, so if you want to use
 * template files rather than functions to extend Slick theming, copy this to
 * your custom theme. If you are comfortable with PHP, consider overriding
 * theme_slick_wrapper() instead, such as MY_THEME_slick_wrapper(), or
 * regular preprocess.
 *
 * This file is also provided to support Views template suggestions at Views UI
 * when using Slick Views style plugins.
 *
 * Available variables:
 * - $items: An array of slick instances: main and thumbnail slicks.
 * - $settings: A cherry-picked settings that mostly defines the slide HTML or
 *     layout, and none of JS settings/options which are defined at each slick.
 * - $attributes: The array of attributes to hold the container classes, and id.
 *
 * @see theme_slick_wrapper()
 * @see template_preprocess_slick_wrapper()
 */
?>
<?php if (!empty($settings['nav'])): ?>
  <div<?php print $attributes; ?>>
<?php endif; ?>

  <?php foreach ($items as $item): ?>
    <?php print render($item); ?>
  <?php endforeach; ?>

<?php if (!empty($settings['nav'])): ?>
  </div>
<?php endif; ?>
