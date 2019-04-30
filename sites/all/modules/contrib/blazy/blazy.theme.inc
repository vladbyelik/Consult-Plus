<?php

/**
 * @file
 * Hooks and preprocess functions for the Blazy module.
 */

use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;

/**
 * Returns HTML for a blazy template.
 *
 * Most heavy liftings are performed at BlazyManager::preRender() where they
 * belong to. Hence the theme only processes all the basic for clarity. So
 * calling this theme directly is useless. Please use the provided API, see
 * blazy.api.php for details. This separation is to avoid dup lines and ifities
 * aside from the promising performance gain of #pre_render element.
 *
 * @param array $variables
 *   An associative array containing:
 *   - captions: An optional renderable array of inline or lightbox captions.
 *   - item: The image info (alt, title, etc.) converted to object.
 *   - iframe: An optional renderable array of video iframe element.
 *   - settings: HTML related settings.
 *   - url: An optional url to link image to: content, or lightboxes.
 *
 * @return string
 *   An HTML string representing the themed output.
 *
 * @see BlazyManager::getBlazy()
 * @see blazy.api.php
 *
 * @ingroup themeable
 */
function theme_blazy(array $variables) {
  extract($variables);

  // Build optional Picture, Blazy, or Slick image with optional lazyload.
  // Image is optional for iframe only, or Blazy CSS background.
  if ($settings['use_image']) {
    if ($settings['picture']) {
      $media_item['image'] = [
        '#theme' => 'picture',
        '#uri' => $settings['uri'],
        '#style_name' => $settings['image_style'],
        '#breakpoints' => $settings['picture'],
        '#lazyload' => !empty($settings['lazy']),
        '#lazyload_aspect_ratio' => !empty($settings['lazy']),
        '#alt' => isset($item_attributes['alt']) ? $item_attributes['alt'] : '',
        '#title' => isset($item_attributes['title']) ? $item_attributes['title'] : '',
      ];
    }
    else {
      // If the image is lazyloaded.
      if ($settings['lazy']) {
        // Do not pass to theme_image() as D7 doesn't support data URI, yet.
        $media_item['image']['#markup'] = '<img' . drupal_attributes($item_attributes) . ' />';
      }
      else {
        // Supports non-lazyloaded images such as for optional Slick lazyload.
        // As we pass image_url, not URI, no worries about theme_image_style().
        $media_item['image'] = [
          '#theme' => 'image',
          '#path' => $settings['image_url'],
          '#attributes' => $item_attributes,
        ];
      }
    }
  }

  // Prepares a media/ video player if so configured.
  $media_item['iframe'] = $settings['use_media'] && $settings['player'] ? $iframe : [];

  // Build image, iframe, or CSS background DIV, with aspect ratio.
  $media['media'] = Blazy::container($media_item, $attributes);

  // Build optional media wrapped by a link to content, or lightboxes.
  if ($url) {
    $media['icon'] = $settings['lightbox'] ? $settings['icon'] : [];

    // Must define attributes even if none found.
    $options['attributes'] = $url_attributes ?: ['class' => 'blazy__link'];
    $options['html'] = TRUE;

    // Flatten out the media above into a string as required by theme_link().
    $link['image'] = [
      '#theme' => 'link',
      '#text' => array_filter($media) ? drupal_render($media) : '',
      '#options' => $options,
      '#path' => $url,
    ];

    // This separation is to allow embedding social share in lightbox captions
    // as otherwise impossible when tightly coupled to the link/ A tag.
    if ($settings['lightbox'] && !empty($captions['lightbox'])) {
      $attr['class'] = ['litebox-caption', 'element-invisible'];
      $link['caption'] = Blazy::container($captions['lightbox'], $attr);
    }

    // Rebuild the renderable media.
    $media = $link;
  }

  // Optionally wrap (linked) media with an extra container to avoid media
  // taking over the entire container due to an aspect ratio, see BlazyFilter.
  $content['media'] = empty($media_attributes) ? $media : Blazy::container($media, $media_attributes);

  // Build optional inline captions.
  if ($captions && !empty($captions['inline'])) {
    $caption_content = [];
    foreach ($captions['inline'] as $key => $caption) {
      $caption_content[$key] = Blazy::container($caption['content'], $caption['attributes'], $caption['tag']);
    }
    $content['caption'] = Blazy::container($caption_content, $caption_attributes);
  }

  // Optionally wrap them all with the top level container.
  $build = Blazy::container($content, $wrapper_attributes);
  return drupal_render_children($build);
}

/**
 * Prepares variables for theme_blazy().
 */
function template_preprocess_blazy(&$variables) {
  $element = $variables['element'];
  foreach (BlazyDefault::themeProperties() as $key) {
    $variables[$key] = isset($element["#$key"]) ? $element["#$key"] : [];
  }

  // Provides optional attributes, see BlazyFilter.
  foreach (BlazyDefault::themeAttributes() as $key) {
    $key = $key . '_attributes';
    $variables[$key] = empty($element["#$key"]) ? [] : $element["#$key"];
  }

  $settings = &$variables['settings'];
  $attributes = &$variables['attributes'];
  $settings += BlazyDefault::itemSettings();

  // Prepare container attributes.
  $classes = isset($attributes['class']) ? $attributes['class'] : [];
  $attributes['class'] = array_merge(['media'], $classes);
}
