<?php

/**
 * @file
 * Default theme implementation for the individual slick item/ slide template.
 *
 * This file is not used by Slick, which uses theme_slick_slide() instead for
 * performance reasons. The markup is the same, though, so if you want to use
 * template files rather than functions to extend Slick theming, copy this to
 * your custom theme. If you are comfortable with PHP, consider overriding
 * theme_slick_slide(), such as MY_THEME_slick_slide(), or regular preprocess.
 *
 * Available variables:
 * - $attributes: An array of attributes to apply to the element.
 * - $content_attributes: An array of attributes to apply to the inner element.
 * - $item containing:
 *   - slide: A renderable array of the main image/background.
 *   - caption: A renderable array containing caption fields if provided:
 *     - title: The individual slide title.
 *     - alt: The core Image field Alt as caption.
 *     - link: The slide links or buttons.
 *     - overlay: The image/audio/video overlay, or a nested slick.
 *     - data: Any possible field for more complex data if crazy enough.
 * - $settings: An array containing the given settings.
 *
 * @see template_preprocess_slick_slide()
 * @see theme_slick_slide()
 */
?>
<?php
  $slide = empty($item['slide']) ? '' : render($item['slide']);
  if ($slide && $settings['split'] && empty($settings['unslick'])) {
    $slide = '<div class="slide__media">' . $slide . '</div>';
  }
?>
<?php if ($settings['use_wrapper']): ?>
  <div<?php print $attributes; ?>>
  <?php if (empty($settings['grid'])): ?>
    <div<?php print $content_attributes; ?>>
  <?php endif; ?>
<?php endif; ?>

  <?php print $slide; ?>

  <?php if ($settings['use_caption']): ?>
    <?php if ($settings['fullwidth']): ?>
      <div class="slide__constrained">
    <?php endif; ?>

      <div<?php print $caption_attributes; ?>>
        <?php if (!empty($item['caption']['overlay'])): ?>
          <div class="slide__overlay"><?php print render($item['caption']['overlay']); ?></div>
          <?php if ($settings['has_data']): ?>
            <div class="slide__data">
          <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($item['caption']['title'])): ?>
          <h2 class="slide__title"><?php print render($item['caption']['title']); ?></h2>
        <?php endif; ?>

        <?php if (!empty($item['caption']['alt'])): ?>
          <p class="slide__description"><?php print render($item['caption']['alt']); ?></p>
        <?php endif; ?>

        <?php if (!empty($item['caption']['data'])): ?>
          <div class="slide__description"><?php print render($item['caption']['data']); ?></div>
        <?php endif; ?>

        <?php if (!empty($item['caption']['link'])): ?>
          <div class="slide__link"><?php print render($item['caption']['link']); ?></div>
        <?php endif; ?>

        <?php if (!empty($item['caption']['overlay']) && $settings['has_data']): ?>
          </div>
        <?php endif; ?>
      </div>

    <?php if ($settings['fullwidth']): ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

<?php if ($settings['use_wrapper']): ?>
  <?php if (empty($settings['grid'])): ?>
    </div>
  <?php endif; ?>
  </div>
<?php endif; ?>
