<?php

/**
 * @file
 * Hooks and preprocess functions for the Slick module.
 *
 * Slick 3.x strives to be compatible with Slick 2.x, but not always.
 * Unlike Slick 2.x which uses .tpl, Slick 3.x uses theme functions with the
 * expected render array for some. An extra care (string vs. render array) is
 * needed as Slick 3.x still supports the unused optional .tpl files, as well.
 */

use Drupal\blazy\Blazy;
use Drupal\slick\SlickDefault;
use Drupal\slick\Entity\Slick;

/**
 * Preprocess common attributes for slick.
 */
function _slick_preprocess_attributes(&$variables) {
  foreach (['attributes', 'content_attributes'] as $key) {
    $variables[$key . '_array'] = isset($variables['element']["#$key"]) ? $variables['element']["#$key"] : [];
  }
}

/**
 * Process common attributes for slick.
 */
function _slick_process_attributes(&$variables) {
  // The template_process_BLAH is ditched at D8, including BLAH_array.
  // However we must stick to D7 convention FWIW, save for Slick 4.x maybe.
  foreach (['attributes', 'content_attributes'] as $key) {
    $variables[$key] = empty($variables[$key . '_array']) ? '' : drupal_attributes($variables[$key . '_array']);
  }
}

/**
 * Prepares common variables for slick container.
 */
function _slick_preprocess_container(&$variables) {
  _slick_preprocess_attributes($variables);
  foreach (['items', 'settings'] as $key) {
    $variables[$key] = isset($variables['element']["#$key"]) ? $variables['element']["#$key"] : [];
  }
}

/**
 * Prepares common variables for slick item.
 */
function _slick_preprocess_item(&$variables) {
  _slick_preprocess_attributes($variables);
  foreach (['delta', 'item', 'settings'] as $key) {
    $default = $key == 'delta' ? 0 : [];
    $variables[$key] = isset($variables['element']["#$key"]) ? $variables['element']["#$key"] : $default;
  }

  // Chances are entity_view, or Views flattens out the expected render array.
  // Or a drupal_render() is called too early, or just string passed.
  // Applies to thumbnail caption as well in case bypassing the API.
  foreach (['slide', 'caption'] as $key) {
    if (!empty($variables['item'][$key]) && is_string($variables['item'][$key])) {
      $variables['item'][$key] = ['#markup' => $variables['item'][$key]];
    }
  }

  // With clones, regular CSS :nth-child will fail to style uniquely, add delta.
  $variables['attributes_array']['class'][] = 'slick__slide slide slide--' . $variables['delta'];
}

/**
 * Returns HTML for a slick wrapper with/ without thumbnails.
 *
 * Variables are processed at Drupal\slick\SlickManager::preRenderWrapper().
 *
 * @param array $variables
 *   An associative array containing:
 *   - items: An array of slick instances: main and thumbnail slicks.
 *   - settings: HTML related settings.
 *
 * @see Drupal\slick\SlickManager::build()
 *
 * @ingroup themeable
 */
function theme_slick_wrapper(array $variables) {
  extract($variables);

  $build = ['content' => $items];
  if (!empty($settings['nav'])) {
    $build['content']['#prefix'] = '<div' . $attributes . '>';
    $build['content']['#suffix'] = '</div>';
  }

  return drupal_render_children($build);
}

/**
 * Preprocess variables for theme_slick_wrapper(), or slick-wrapper.tpl.php.
 */
function template_preprocess_slick_wrapper(&$variables) {
  _slick_preprocess_container($variables);
}

/**
 * Process variables for theme_slick_wrapper(), or slick-wrapper.tpl.php.
 */
function template_process_slick_wrapper(&$variables) {
  _slick_process_attributes($variables);
}

/**
 * Returns HTML for a slick.
 *
 * @param array $variables
 *   An associative array containing:
 *   - items: The array of items containing main image/video/audio, optional
 *       image/video/audio overlay and captions, and optional thumbnail
 *       texts/images.
 *   - js: The JS optionset which may contain configurable HTML such as arrows.
 *   - settings: A cherry-picked settings that mostly defines the slide HTML or
 *       layout, and few JS settings/options which affect HTML output.
 *   - attributes: The attributes to hold the main container classes, RTL, id.
 *   - content_attributes: The array of attributes to hold optional id and
 *       data-slick containing JSON object aka JS settings the Slick expects to
 *       override default options. We don't store these JS settings in the
 *       normal <head>, but inline within data-slick attribute instead.
 *
 * @ingroup themeable
 */
function theme_slick(array $variables) {
  extract($variables);

  // Prevents direct call to this theme without proper checks from screwing up.
  // @todo remove babysitters at Slick 4.x.
  if (empty($items)) {
    return '';
  }

  // Unslick is when slides amount is less than slidesToShow that is when the
  // slideshow cannot slide. The markups are only provided when it can slide.
  if (empty($settings['unslick'])) {
    $arrow_down = empty($arrow_down_attributes) ? '' : '<button' . $arrow_down_attributes . '></button>';
    $arrows = $js['prevArrow'] . $arrow_down . $js['nextArrow'];

    $items = ['slides' => $items];
    $items['slides']['#prefix'] = '<div' . $content_attributes . '>';
    $items['slides']['#suffix'] = '</div><nav' . $arrow_attributes . '>' . $arrows . '</nav>';
  }

  $build = ['content' => $items];
  $build['content']['#prefix'] = '<div' . $attributes . '>';
  $build['content']['#suffix'] = '</div>';

  return drupal_render_children($build);
}

/**
 * Preprocess variables for theme_slick().
 */
function template_preprocess_slick(&$variables) {
  _slick_preprocess_container($variables);
  foreach (['options', 'optionset'] as $key) {
    $variables[$key] = isset($variables['element']["#$key"]) ? $variables['element']["#$key"] : [];
  }

  // Prepare attributes.
  $settings  = &$variables['settings'];
  $settings += SlickDefault::htmlSettings();
  $optionset = $variables['optionset'] ?: Slick::loadWithFallback($settings['optionset']);
  $js        = array_merge($optionset->getSettings(), $variables['options']) + SlickDefault::jsSettings();
  $display   = $settings['display'];
  $id        = $settings['id'] ?: Blazy::getHtmlId('slick');

  // @see SlickManager::buildGrid(), and this should make sense.
  $settings['count']   = isset($settings['count']) ? $settings['count'] : count($variables['items']);
  $settings['unslick'] = $settings['unslick'] || $settings['count'] == 1;
  $attributes          = &$variables['attributes_array'];
  $custom_classes      = empty($attributes['class']) ? [] : $attributes['class'];
  $attributes['class'] = array_merge(['slick'], $custom_classes);
  $attributes['id']    = $display == 'thumbnail' ? $id . '-thumbnail' : $id;
  $content_attributes  = &$variables['content_attributes_array'];

  // Blazy can still lazyload an unslick.
  // The lazy supercedes JS lazyLoad for background, breakpoints, media, etc.
  $settings['lazy'] = $settings['lazy'] ?: $js['lazyLoad'];
  if ($display != 'thumbnail' && ($settings['lazy'] == 'blazy' || !empty($settings['blazy']))) {
    $js['lazyLoad'] = 'blazy';
  }

  // Make slick language-direction-aware.
  global $language;
  if ($language->direction == 'rtl') {
    $attributes['dir'] = isset($language->dir) ? $language->dir : '';
    $js['rtl'] = $language->direction ? TRUE : FALSE;
  }

  // Prevents broken slick when only one item given, or an enforced unslick.
  if ($settings['unslick']) {
    $attributes['class'][] = 'unslick';
  }
  else {
    if ($settings['count'] <= $js['slidesToShow']) {
      $attributes['class'][] = 'slick--less';
    }

    // Arrows.
    $arrows = $downs = [];
    if ($js['vertical']) {
      $arrows[] = 'slick__arrow--v';
    }

    // Only makes sense for the main display, not thumbnail, overlay.
    if ($display == 'main') {
      if ($settings['skin_arrows']) {
        $arrows[] = 'slick__arrow--' . str_replace('_', '-', $settings['skin_arrows']);
      }

      // Down arrows.
      if (!empty($js['downArrow']) && !empty($js['downArrowTarget'])) {
        $downs['class'] = ['slick-down'];
        $downs['data-offset'] = $js['downArrowOffset'];
        $downs['data-target'] = $js['downArrowTarget'];
        $downs['data-role'] = 'none';
        $downs['type'] = 'button';
        $attributes['class'][] = 'slick--has-arrow-down';
      }
    }

    $variables['arrow_down_attributes_array'] = $downs;
    $variables['arrow_attributes_array']['class'] = array_merge(['slick__arrow'], $arrows);
    $variables['arrow_attributes_array']['role'] = 'navigation';

    // Main arrows.
    // @todo remove the $settings parts (BC) for $js to reduce dups.
    $js['prevArrow'] = $settings['prev_arrow'] = strip_tags($js['prevArrow'], '<a><em><span><strong><button><div>');
    $js['nextArrow'] = $settings['next_arrow'] = strip_tags($js['nextArrow'], '<a><em><span><strong><button><div>');

    // focusOnSelect won't work with empty slide value, so add proper selector.
    // Respects core Grid markups which may wrap .slick__slide within anon DIV.
    if (empty($js['slide']) && !empty($js['focusOnSelect'])) {
      $js['slide'] = ($js['rows'] == 1 && $js['slidesPerRow'] == 1) ? '.slick__slide' : $js['slide'];
    }

    // Add the configuration as JSON object into the slick container.
    $js = isset($variables['js']) ? array_merge($js, $variables['js']) : $js;
    $content_attributes['id'] = $attributes['id'] . '-slider';
    $content_attributes['class'][] = 'slick__slider';
    if ($json = $optionset->removeDefaultValues($js)) {
      $content_attributes['data-slick'] = drupal_json_encode($json);
    }
  }

  // Pass js, settings and attributes to theme_slick().
  $variables['js'] = $js;

  // Process individual item, basically converting array to render array.
  foreach ($variables['items'] as $delta => $item) {
    $item_settings = isset($item['settings']) ? array_merge($settings, $item['settings']) : $settings;
    $item_attributes = isset($item['attributes']) ? $item['attributes'] : [];
    $item_settings['current_item'] = $display;

    // Remove extracted item elements to prevent them from being rendered.
    unset($item['settings'], $item['attributes'], $item['item']);

    // Using non-hard-coded theme_slick_BLAH() is simply for flexibility
    // to extend functionality -- lightbox, grid, main media or thumbnail, etc.
    // before arriving here. This is kind of tiny control room to decide what
    // to do with each item. Note the new vanilla and thumbnail themes.
    $theme = $settings['vanilla'] ? 'vanilla' : ($display == 'thumbnail' ? 'thumbnail' : 'slide');
    $slide = [
      '#theme'      => 'slick_' . $theme,
      '#item'       => $item,
      '#delta'      => $delta,
      '#settings'   => $item_settings,
      '#attributes' => $item_attributes,
    ];
    $variables['items'][$delta] = $slide;
    unset($slide);
  }

  // Had weird issues with some theme expecting classes array at 2014, satisfy.
  $variables['classes_array'] = $attributes['class'];
}

/**
 * Process variables for theme_slick(), or slick.tpl.php.
 */
function template_process_slick(&$variables) {
  _slick_process_attributes($variables);
  foreach (['arrow', 'arrow_down'] as $key) {
    $variables[$key . '_attributes'] = empty($variables[$key . '_attributes_array']) ? '' : drupal_attributes($variables[$key . '_attributes_array']);
  }
}

/**
 * Returns HTML for a slick_vanilla to render individual slide as is.
 *
 * @param array $variables
 *   An associative array containing:
 *   - attributes: An array of attributes to apply to the element.
 *   - delta: An index of the current item.
 *   - item: A renderable array of the slide content can be just anything.
 *   - settings: An array containing the given settings.
 *
 * @ingroup themeable
 */
function theme_slick_vanilla(array $variables) {
  _slick_preprocess_item($variables);
  extract($variables);

  return $item ? '<div' . drupal_attributes($attributes_array) . '>' . drupal_render($item) . '</div>' : '';
}

/**
 * Returns HTML for a thumbnail navigation.
 *
 * @param array $variables
 *   An associative array containing:
 *   - attributes: An array of attributes to apply to the element.
 *   - delta: An index of the current item.
 *   - item contains:
 *     - slide: A renderable array of the thumbnail image/background.
 *     - caption: A renderable array containing caption text for tab-like.
 *   - settings: An array containing the given settings.
 *
 * @ingroup themeable
 */
function theme_slick_thumbnail(array $variables) {
  _slick_preprocess_item($variables);
  extract($variables);

  $build = '';
  // Cannot use FIGURE, as both slide and caption are optional. FIGURE makes
  // no sense when only caption is provided for tab navigation, and no image.
  if (!empty($item['slide'])) {
    $build .= '<div class="slide__thumbnail">' . drupal_render($item['slide']) . '</div>';
  }
  if (!empty($item['caption'])) {
    $build .= '<div class="slide__caption">' . drupal_render($item['caption']) . '</div>';
  }
  return $build ? '<div' . drupal_attributes($attributes_array) . '>' . $build . '</div>' : '';
}

/**
 * Returns HTML for a slick slide.
 *
 * @param array $variables
 *   An associative array containing:
 *   - attributes: An array of attributes to apply to the element.
 *   - content_attributes: An array of attributes for the inner element.
 *   - delta: An index of the current item.
 *   - item containing:
 *     - slide: A renderable array of the main image/background.
 *     - caption: A renderable array containing caption fields if provided:
 *       - title: The individual slide title.
 *       - alt: The core Image field Alt as caption.
 *       - link: The slide links or buttons.
 *       - overlay: The image/audio/video overlay, or a nested slick.
 *       - data: Any possible field for more complex data if crazy enough.
 *   - settings: An array containing the given settings.
 *
 * @ingroup themeable
 */
function theme_slick_slide(array $variables) {
  extract($variables);

  $slide = empty($item['slide']) ? '' : drupal_render($item['slide']);
  if ($slide && $settings['split'] && empty($settings['unslick'])) {
    $slide = '<div class="slide__media">' . $slide . '</div>';
  }

  $caption = '';
  // Ensures no caption markup is displayed until its data is provided.
  if ($settings['use_caption']) {
    $inner = empty($item['caption']['title']) ? '' : '<h2 class="slide__title">' . drupal_render($item['caption']['title']) . '</h2>';
    $inner .= empty($item['caption']['alt']) ? '' : '<p class="slide__description">' . drupal_render($item['caption']['alt']) . '</p>';
    $inner .= empty($item['caption']['data']) ? '' : '<div class="slide__description">' . drupal_render($item['caption']['data']) . '</div>';
    $inner .= empty($item['caption']['link']) ? '' : '<div class="slide__link">' . drupal_render($item['caption']['link']) . '</div>';

    // Third level overlay container can be nested slicks, or videos.
    if (empty($item['caption']['overlay'])) {
      $caption = $inner;
    }
    else {
      $caption = '<div class="slide__overlay">' . drupal_render($item['caption']['overlay']) . '</div>';
      // Prevents overlay (nested slicks) from overlapping individual caption.
      if ($settings['has_data']) {
        $caption .= '<div class="slide__data">' . $inner . '</div>';
      }
    }

    // Second level caption container.
    $caption = '<div' . $caption_attributes . '>' . $caption . '</div>';

    // First level caption fullwidth container.
    if ($settings['fullwidth']) {
      $caption = '<div class="slide__constrained">' . $caption . '</div>';
    }
  }

  // Put slide and caption together, and only wrap with extra divs as required.
  // We should have bare minimum markups until required at the cost of ifities.
  // The same applies to CSS classes. By default slick has only two CSS classes.
  $build = $slide . $caption;
  if ($settings['use_wrapper']) {
    // Second level slide container.
    if (empty($settings['grid'])) {
      $build = '<div' . $content_attributes . '>' . $build . '</div>';
    }

    // First level slide container.
    $build = '<div' . $attributes . '>' . $build . '</div>';
  }

  return $build;
}

/**
 * Preprocess variables for theme_slick_slide() and slick-slide.tpl.php.
 */
function template_preprocess_slick_slide(&$variables) {
  _slick_preprocess_item($variables);

  // All slide types: main, thumbnail, grid, overlay -- may have captions.
  // Anything but the main slide is treated as captions for clarity.
  // Variables item may contain `slide` and `caption`.
  foreach (['alt', 'data', 'link', 'overlay', 'title'] as $key) {
    // Chances are entity_view, or Views flattens out the expected render array.
    // Or a drupal_render() is called too early, or just string passed.
    $caption = isset($variables['item']['caption'][$key]) ? $variables['item']['caption'][$key] : [];
    if ($caption) {
      $caption = is_string($caption) ? ['#markup' => $caption] : $caption;
    }
    $variables['item']['caption'][$key] = $caption;
  }

  $item = &$variables['item'];
  $settings = &$variables['settings'];
  $attributes = &$variables['attributes_array'];
  $content_attributes = &$variables['content_attributes_array'];

  // detroy: Remove .slide__content if it is an enforced unslick grid.
  // fullwidth: If full skins, add wrappers to hold caption and overlay.
  // split: Split image from captions if we do have captions, and main image.
  // use_wrapper: Don't add divities for a single item to have clean markups.
  $item['slide']           = isset($item['slide']) ? $item['slide'] : [];
  $settings['detroy']      = $settings['current_item'] == 'main' && $settings['grid'] && !empty($settings['unslick']);
  $settings['fullwidth']   = !empty($settings['skin']) && strpos($settings['skin'], 'full') !== FALSE;
  $settings['has_data']    = !empty($item['caption']['alt']) || !empty($item['caption']['title']) || !empty($item['caption']['data']);
  $settings['split']       = !empty($item) && (!empty($settings['caption']) || !empty($settings['title']));
  $settings['use_caption'] = !empty(array_filter($item['caption']));
  $settings['use_wrapper'] = $settings['count'] > 1 && $settings['current_item'] != 'grid';

  if (!empty($settings['layout'])) {
    $attributes['class'][] = 'slide--caption--' . str_replace('_', '-', $settings['layout']);
  }
  if (!empty($settings['class'])) {
    // Respects string with spaces such as CSV tags to space delimiter.
    $attributes['class'][] = drupal_strtolower(str_replace('_', '-', $settings['class']));
  }

  $content_attributes['class'][] = $settings['detroy'] ? 'slide' : 'slide__content';
  $variables['caption_attributes_array']['class'][] = 'slide__caption';
  $variables['classes_array'] = $attributes['class'];
}

/**
 * Process variables for theme_slick_slide() or slick-slide.tpl.php.
 */
function template_process_slick_slide(&$variables) {
  _slick_process_attributes($variables);
  $variables['caption_attributes'] = empty($variables['caption_attributes_array']) ? '' : drupal_attributes($variables['caption_attributes_array']);
}

/**
 * Returns HTML for a slick grid.
 *
 * @param array $variables
 *   An associative array containing:
 *   - attributes: An array of attributes to apply to the element.
 *   - items: A renderable array of the main image/background.
 *   - settings: An array containing cherry-picked settings.
 *
 * @ingroup themeable
 */
function theme_slick_grid(array $variables) {
  extract($variables);

  $build = '';
  foreach ($items as $delta => $item) {
    $slide = '<div class="grid__content">' . drupal_render($item) . '</div>';
    $build .= '<li' . drupal_attributes($item_attributes_array[$delta]) . '>' . $slide . '</li>';
  }
  return '<ul' . drupal_attributes($attributes_array) . '>' . $build . '</ul>';
}

/**
 * Prepares variables for theme_slick_grid().
 */
function template_preprocess_slick_grid(&$variables) {
  _slick_preprocess_container($variables);

  $settings = $variables['settings'];
  foreach ($variables['items'] as $delta => $item) {
    $settings = isset($item['settings']) ? array_merge($settings, $item['settings']) : $settings;
    $settings['current_item'] = 'grid';

    $item_attributes = empty($item['attributes']) ? [] : $item['attributes'];
    $classes = empty($item_attributes['class']) ? [] : (array) $item_attributes['class'];
    if (empty($settings['unslick'])) {
      $classes[] = 'slide__grid';
    }

    $item_attributes['class'] = array_merge(['grid'], $classes);
    unset($item['settings'], $item['attributes']);

    $slide['slide'] = [
      '#theme'    => empty($settings['vanilla']) ? 'slick_slide' : 'slick_vanilla',
      '#item'     => $item,
      '#delta'    => $delta,
      '#settings' => $settings,
    ];

    $variables['item_attributes_array'][$delta] = $item_attributes;
    $variables['items'][$delta] = $slide;
    unset($slide);
  }
}

/**
 * Implements hook_process_slick_grid().
 *
 * This is BC for Slick 2.x which had slick-grid.tpl.php, but no longer at 3.x.
 * As a module .tpl may be copied into themes, we play safe. At Slick 3.x,
 * you can override theme_slick_grid() instead, and remove slick-grid.tpl.php.
 *
 * @deprecated to be removed post full release as this is no longer needed.
 */
function template_process_slick_grid(&$variables) {
  _slick_process_attributes($variables);
  foreach ($variables['items'] as $delta => $item) {
    $variables['item_attributes'][$delta] = empty($variables['item_attributes_array'][$delta]) ? '' : drupal_attributes($variables['item_attributes_array'][$delta]);
  }
}
