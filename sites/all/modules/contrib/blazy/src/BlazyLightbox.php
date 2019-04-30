<?php

namespace Drupal\blazy;

/**
 * Provides lightbox utilities.
 */
class BlazyLightbox {

  /**
   * Gets media switch elements: all lightboxes, not content, nor iframe.
   *
   * @param array $element
   *   The element being modified.
   */
  public static function build(array &$element = []) {
    $item       = $element['#item'];
    $settings   = &$element['#settings'];
    $type       = $settings['type'];
    $uri        = $settings['uri'];
    $switch     = $settings['media_switch'];
    $switch_css = str_replace('_', '-', $switch);

    // Provide relevant URL if it is a lightbox.
    $url_attributes = &$element['#url_attributes'];
    $url_attributes['class'][] = 'blazy__' . $switch_css . ' litebox';
    $url_attributes['data-' . $switch_css . '-trigger'] = TRUE;

    // If it is a video/audio, otherwise image to image.
    $settings['id']         = isset($settings['id']) ? $settings['id'] : 'blazy-' . $switch_css;
    $settings['gallery_id'] = empty($settings['gallery_id']) ? $settings['id'] : $settings['gallery_id'];
    $settings['box_url']    = file_create_url($uri);
    $settings['icon']       = empty($settings['icon']) ? ['#markup' => '<span class="media__icon media__icon--litebox"></span>'] : $settings['icon'];
    $settings['lightbox']   = $switch;
    $settings['box_width']  = isset($item->width) ? $item->width : (empty($settings['width']) ? NULL : $settings['width']);
    $settings['box_height'] = isset($item->height) ? $item->height : (empty($settings['height']) ? NULL : $settings['height']);

    $dimensions = ['width' => $settings['box_width'], 'height' => $settings['box_height']];
    if (!empty($settings['box_style'])) {
      image_style_transform_dimensions($settings['box_style'], $dimensions);

      $settings['box_url'] = image_style_url($settings['box_style'], $uri);
    }

    // Allows custom work to override this without image style, such as
    // a combo of image, video, Instagram, Facebook, etc.
    if (empty($settings['_box_width'])) {
      $settings['box_width'] = $dimensions['width'];
      $settings['box_height'] = $dimensions['height'];
    }

    $json = [
      'type'   => $type,
      'width'  => $settings['box_width'],
      'height' => $settings['box_height'],
    ];

    // This allows PhotoSwipe with videos still swipable.
    if (!empty($settings['box_media_style'])) {
      image_style_transform_dimensions($settings['box_media_style'], $dimensions);
      $settings['box_media_url'] = image_style_url($settings['box_media_style'], $uri);
    }

    if (!empty($settings['embed_url'])) {
      $json['scheme'] = $settings['scheme'];
      $json['width'] = 640;
      $json['height'] = 360;

      // Force autoplay for media URL on lightboxes, saving another click.
      $url = empty($settings['autoplay_url']) ? $settings['embed_url'] : $settings['autoplay_url'];

      // This allows PhotoSwipe with videos still swipable.
      if (!empty($settings['box_media_style'])) {
        $settings['box_url'] = $settings['box_media_url'];

        // Allows custom work to override this video size without image style.
        if (empty($settings['_box_width'])) {
          $settings['box_width'] = $dimensions['width'];
          $settings['box_height'] = $dimensions['height'];
        }

        $json['width'] = $settings['box_width'];
        $json['height'] = $settings['box_height'];
      }

      if ($switch == 'photobox') {
        $url_attributes['rel'] = 'video';
      }
    }
    else {
      $url = $settings['box_url'];
    }

    if ($switch == 'colorbox') {
      // @todo make Blazy Grid without Blazy Views fields support multiple
      // fields and entities as a gallery group, likely via a class at Views UI.
      // Must use consistent key for multiple entities, hence cannot use id.
      $json['rel'] = 'blazy-' . $settings['gallery_id'];
    }

    $url_attributes['data-media'] = drupal_json_encode($json);

    if (!empty($settings['box_caption'])) {
      $element['#captions']['lightbox'] = self::buildCaptions($item, $settings);
    }

    $element['#url'] = $url;
  }

  /**
   * Builds lightbox captions.
   *
   * @param object|mixed $item
   *   The \Drupal\image\Plugin\Field\FieldType\ImageItem item, or array when
   *   dealing with Video Embed Field.
   * @param array $settings
   *   The settings to work with.
   *
   * @return array
   *   The renderable array of caption, or empty array.
   */
  public static function buildCaptions($item, array $settings = []) {
    $title   = empty($item->title) ? '' : $item->title;
    $alt     = empty($item->alt) ? '' : $item->alt;
    $delta   = empty($settings['delta']) ? 0 : $settings['delta'];
    $caption = '';
    $entity  = $entity_type = NULL;

    if (!empty($settings['entity'])) {
      $entity_type = $settings['entity_type_id'];
      $entity = $settings['entity'];
      unset($settings['entity']);
    }

    switch ($settings['box_caption']) {
      case 'auto':
        $caption = $alt ?: $title;
        break;

      case 'alt':
        $caption = $alt;
        break;

      case 'title':
        $caption = $title;
        break;

      case 'alt_title':
      case 'title_alt':
        $alt     = $alt ? '<p>' . $alt . '</p>' : '';
        $title   = $title ? '<h2>' . $title . '</h2>' : '';
        $caption = $settings['box_caption'] == 'alt_title' ? $alt . $title : $title . $alt;
        break;

      case 'entity_title':
        $caption = entity_label($entity_type, $entity);
        break;

      case 'custom':
        if (!empty($settings['box_caption_custom'])) {
          $caption = token_replace($settings['box_caption_custom'], [
            $entity_type => $entity,
            'file' => (object) $item,
          ],
          ['clear' => TRUE]);

          // Checks for multi-value text fields, and maps its delta to image.
          if (!empty($caption) && strpos($caption, ", <p>") !== FALSE) {
            $caption = str_replace(", <p>", '| <p>', $caption);
            $captions = explode("|", $caption);
            $caption = isset($captions[$delta]) ? $captions[$delta] : '';
          }
        }
        break;
    }

    return empty($caption) ? [] : ['#markup' => filter_xss($caption, BlazyDefault::TAGS)];
  }

}
