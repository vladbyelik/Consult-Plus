<?php

namespace Drupal\blazy;

/**
 * Provides extra utilities to work with Media.
 */
class BlazyMedia {

  /**
   * Prepares the Blazy iframe as a structured array ready for ::renderer().
   *
   * @param array $element
   *   The renderable array being modified.
   *
   * @todo support other Media file entities like at D8: Media Facebook, etc.
   */
  public static function build(array &$element = []) {
    $attributes            = &$element['#attributes'];
    $settings              = &$element['#settings'];
    $settings['player']    = $settings['player'] ?: (empty($settings['lightbox']) && $settings['media_switch'] != 'content');
    $settings['use_image'] = !empty($settings['media_switch']);
    $iframe_attributes     = [
      'data-src' => $settings['embed_url'],
      'src' => 'about:blank',
      'class' => ['b-lazy', 'media__element'],
      'allowfullscreen' => '',
    ];

    // Prevents broken iframe when aspect ratio is empty.
    if (empty($settings['ratio']) && $settings['width']) {
      $iframe_attributes['width'] = $settings['width'];
      $iframe_attributes['height'] = $settings['height'];
    }

    $player_attributes = [
      'class'         => 'media__icon media__icon--play',
      'data-url'      => $settings['embed_url'],
      'data-autoplay' => $settings['autoplay_url'],
    ];

    if ($settings['use_media'] && $settings['player']) {
      $iframe = [
        '#type' => 'html_tag',
        '#tag' => 'iframe',
        '#value' => '',
        '#attributes' => $iframe_attributes,
      ];

      if ($settings['media_switch']) {
        $icon = '<span class="media__icon media__icon--close"></span>';
        $iframe['#suffix'] = $icon . '<span' . drupal_attributes($player_attributes) . '></span>';
      }

      $element['#iframe'] = $iframe;
      $attributes['class'][] = 'media--player';
    }

    // Iframe is removed on lazyloaded, puts data at non-removable storage.
    $attributes['data-media'] = drupal_json_encode(['type' => $settings['type'], 'scheme' => $settings['scheme']]);
  }

  /**
   * Gets the faked image item out of file entity, or ER, if applicable.
   *
   * This should only be called for type video as file image has all
   * the needed info to get the image from.
   *
   * @param object $file
   *   The expected file entity, or ER, to get image item from.
   *
   * @return object
   *   The image item or FALSE.
   */
  public static function imageItem($file) {
    // Prevents edge case EntityMalformedException: Missing bundle property.
    if (!isset($file->uri)) {
      return FALSE;
    }

    try {
      $wrapper = file_stream_wrapper_get_instance_by_uri($file->uri);
      // No need for checking MediaReadOnlyStreamWrapper.
      if (!is_object($wrapper)) {
        throw new \Exception('Unable to find matching wrapper');
      }

      // If a video, uri points to a video scheme, not local thumbnail.
      $uri = $file->type == 'image' ? $file->uri : $wrapper->getLocalThumbnailPath();
    }
    catch (\Exception $e) {
      // Ignore.
    }

    if (!isset($uri)) {
      return FALSE;
    }

    list($type) = explode('/', file_get_mimetype($uri), 2);

    if ($type == 'image' && ($image = image_get_info($uri))) {
      $item            = new \stdClass();
      $item->target_id = $file->fid;
      $item->width     = $image['width'];
      $item->height    = $image['height'];
      $item->alt       = $file->filename;
      $item->uri       = $uri;

      return $item;
    }

    return FALSE;
  }

}
