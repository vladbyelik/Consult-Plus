<?php

namespace Drupal\blazy;

/**
 * Provides Blazy specific methods normally called by hooks or procedural codes.
 */
class Blazy {

  /**
   * Defines constant placeholder Data URI image.
   */
  const PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

  /**
   * The blazy HTML ID.
   *
   * @var int
   */
  private static $blazyId;

  /**
   * Provides re-usable breakpoint data-attributes for IMG or DIV element.
   *
   * $settings['breakpoints'] must contain: xs, sm, md, lg breakpoints with
   * the expected keys: width, image_style.
   */
  public static function buildBreakpointAttributes(array &$attributes, array &$settings, $item = NULL) {
    // Defines attributes, builtin, or supported lazyload such as Slick.
    $attributes['class'][] = $settings['lazy_class'];
    $attributes['data-' . $settings['lazy_attribute']] = $settings['image_url'];

    // Only provide multi-serving image URLs if breakpoints are provided.
    if (empty($settings['breakpoints'])) {
      return;
    }

    $srcset = $json = [];
    // https://css-tricks.com/sometimes-sizes-is-quite-important/
    // For older iOS devices that don't support w descriptors in srcset, the
    // first source item in the list will be used.
    $settings['breakpoints'] = array_reverse($settings['breakpoints']);
    foreach ($settings['breakpoints'] as $key => $breakpoint) {
      $url = image_style_url($breakpoint['image_style'], $settings['uri']);

      // Supports multi-breakpoint aspect ratio with irregular sizes.
      // Yet, only provide individual dimensions if not already set.
      // @see Drupal\blazy\BlazyManager::setDimensionsOnce().
      if (!empty($settings['_breakpoint_ratio']) && empty($settings['blazy_data']['dimensions'])) {
        $dimensions = self::transformDimensions($breakpoint['image_style'], $item);

        if ($width = self::widthFromDescriptors($breakpoint['width'])) {
          $json[$width] = round((($dimensions['height'] / $dimensions['width']) * 100), 2);
        }
      }

      $settings['breakpoints'][$key]['url'] = $url;

      // Recheck library if multi-styled BG is still supported anyway.
      // Confirmed: still working with GridStack multi-image-style per item.
      if ($settings['background']) {
        $attributes['data-src-' . $key] = $url;
      }
      else {
        $width = trim($breakpoint['width']);
        $width = is_numeric($width) ? $width . 'w' : $width;
        $srcset[] = $url . ' ' . $width;
      }
    }

    if ($srcset) {
      $settings['srcset'] = implode(', ', $srcset);

      $attributes['srcset'] = '';
      $attributes['data-srcset'] = $settings['srcset'];
      $attributes['sizes'] = '100w';

      if (!empty($settings['sizes'])) {
        $attributes['sizes'] = trim($settings['sizes']);
        unset($attributes['height'], $attributes['width']);
      }
    }

    if ($json) {
      $settings['blazy_data']['dimensions'] = $json;
    }
  }

  /**
   * Returns common content with prefix and suffix containers.
   */
  public static function container($content, $attributes, $tag = 'div') {
    $build = array_filter($content);
    // Supports DIV only without $content such as for CSS background.
    if ($build || $attributes) {
      $build['#prefix'] = '<' . $tag . drupal_attributes($attributes) . '>';
      $build['#suffix'] = '</' . $tag . '>';
    }
    return $build;
  }

  /**
   * Transforms dimensions using an image style.
   *
   * @param string $image_style
   *   The image style.
   * @param object $item
   *   The optional image item.
   *
   * @return array
   *   An array containing width and height transformed by the image style.
   */
  public static function transformDimensions($image_style, $item = NULL) {
    $dimensions['width'] = $item && isset($item->width) ? $item->width : NULL;
    $dimensions['height'] = $item && isset($item->height) ? $item->height : NULL;

    image_style_transform_dimensions($image_style, $dimensions);
    return $dimensions;
  }

  /**
   * Gets the numeric "width" part from a descriptor.
   */
  public static function widthFromDescriptors($descriptor = '') {
    if (empty($descriptor)) {
      return FALSE;
    }

    // Dynamic multi-serving aspect ratio with backward compatibility.
    $descriptor = trim($descriptor);
    if (is_numeric($descriptor)) {
      return (int) $descriptor;
    }

    // Cleanup w descriptor to fetch numerical width for JS aspect ratio.
    $width = strpos($descriptor, "w") !== FALSE ? str_replace('w', '', $descriptor) : $descriptor;

    // If both w and x descriptors are provided.
    if (strpos($descriptor, " ") !== FALSE) {
      // If the position is expected: 640w 2x.
      list($width, $px) = array_pad(array_map('trim', explode(" ", $width, 2)), 2, NULL);

      // If the position is reversed: 2x 640w.
      if (is_numeric($px) && strpos($width, "x") !== FALSE) {
        $width = $px;
      }
    }
    return is_numeric($width) ? (int) $width : FALSE;
  }

  /**
   * Returns the URI from the given image URL, relevant for unmanaged files.
   *
   * @todo recheck if any core function for this aside from file_build_uri().
   */
  public static function buildUri($image_url) {
    if (!url_is_external($image_url) && $path = drupal_parse_url($image_url)['path']) {
      $normal_path = drupal_get_normal_path($path);
      $public_path = variable_get('file_public_path', '');

      // Only concerns for the correct URI, not image URL which is already being
      // displayed via SRC attribute. Don't bother language prefixes for IMG.
      if ($public_path && strpos($normal_path, $public_path) !== FALSE) {
        $rel_path = str_replace($public_path, '', $normal_path);
        return file_build_uri($rel_path);
      }
    }
    return FALSE;
  }

  /**
   * Builds URLs, cache tags, and dimensions for individual image.
   */
  public static function buildUrlAndDimensions(array &$settings, $item = NULL) {
    // Sets dimensions.
    // VEF without image style, or image style with crop, may already set these.
    if (empty($settings['width'])) {
      $settings['width'] = $item && isset($item->width) ? $item->width : NULL;
      $settings['height'] = $item && isset($item->height) ? $item->height : NULL;
    }

    // Respects a few scenarios:
    // 1. Blazy Filter or unmanaged file with/ without valid URI.
    // 2. Hand-coded image_url with/ without valid URI.
    // 3. Respects first_uri without image_url such as colorbox/zoom-like.
    // 4. File API via field formatters or Views fields/ styles with valid URI.
    // If we have a valid URI, provides the correct image URL.
    // Otherwise leave it as is, likely hotlinking to external/ sister sites.
    // Hence URI validity is not crucial in regards to anything but #4.
    // The image will fail silently at any rate given non-expected URI.
    $image_url = file_valid_uri($settings['uri']) ? file_create_url($settings['uri']) : $settings['uri'];
    $settings['image_url'] = $settings['image_url'] ?: $image_url;
    if ($settings['image_style']) {
      $settings['image_url'] = image_style_url($settings['image_style'], $settings['uri']);

      // Only re-calculate dimensions if not cropped, nor already set once.
      if (empty($settings['_dimensions'])) {
        $settings = array_merge($settings, self::transformDimensions($settings['image_style'], $item));
      }
    }

    // Just in case, an attempted kidding gets in the way.
    $use_data_uri = !empty($settings['use_data_uri']) && substr($settings['image_url'], 0, 10) === 'data:image';
    if (!$use_data_uri) {
      $settings['image_url'] = drupal_strip_dangerous_protocols($settings['image_url']);
    }
  }

  /**
   * Modifies image attributes.
   */
  public static function buildItemAttributes(array &$attributes, array $settings, $item = NULL) {
    // Unlike D8, we have no free $item->_attributes from RDF, provide one here.
    // With or without rdf enabled, no need to check for module_exists().
    $attributes['typeof'] = ['foaf:Image'];

    // Extract field item attributes for the theme function, and unset them
    // from the $item so that the field template does not re-render them.
    if ($item && isset($item->_attributes)) {
      $attributes += $item->_attributes;
      unset($item->_attributes);
    }

    // Respects hand-coded image attributes.
    if ($settings['width'] && !isset($attributes['width'])) {
      $attributes['height'] = $settings['height'];
      $attributes['width'] = $settings['width'];
    }

    // The fallback must run as fallback, also for Picture.
    if ($item) {
      foreach (['width', 'height', 'alt', 'title'] as $key) {
        if (isset($item->{$key})) {
          // Respects hand-coded image attributes, image style, and set once.
          if (array_key_exists($key, $attributes)) {
            continue;
          }

          // Do not output an empty 'title' attribute.
          if ($key == 'title' && (strlen($item->title) != 0)) {
            $attributes['title'] = $item->title;
          }
          // Ensures to not override dimensions set once, or via image_style.
          elseif (!isset($attributes[$key])) {
            $attributes[$key] = $item->{$key};
          }
        }
      }
    }
  }

  /**
   * Modifies container attributes with aspect ratio.
   */
  public static function buildAspectRatio(array &$attributes, array &$settings) {
    $attributes['class'][] = 'media--ratio media--ratio--' . $settings['ratio'];

    if ($settings['width'] && in_array($settings['ratio'], ['enforced', 'fluid'])) {
      // If "lucky", Blazy/ Slick Views galleries may already set this once.
      // Lucky when you don't flatten out the Views output earlier.
      $padding = $settings['padding_bottom'] ?: round((($settings['height'] / $settings['width']) * 100), 2);
      $attributes['style'] = 'padding-bottom: ' . $padding . '%';

      // Provides hint to breakpoints to work with multi-breakpoint ratio.
      $settings['_breakpoint_ratio'] = $settings['ratio'];

      // Views rewrite results or Twig inline_template may strip out `style`
      // attributes, provide hint to JS.
      $attributes['data-ratio'] = $padding;
    }
  }

  /**
   * Returns the sanitized attributes common for user-defined ones.
   *
   * When IMG and IFRAME are allowed for untrusted users, trojan horses are
   * welcome. Hence sanitize attributes relevant for BlazyFilter. The rest
   * should be taken care of by HTML filters after Blazy.
   */
  public static function sanitize(array $attributes = []) {
    $clean_attributes = [];
    $tags = ['href', 'poster', 'src', 'about', 'data', 'action', 'formaction'];
    foreach ($attributes as $key => $value) {
      if (is_array($value)) {
        // Respects array item containing space delimited classes: aaa bbb ccc.
        $value = implode(' ', $value);
        $clean_attributes[$key] = array_map('drupal_clean_css_identifier', explode(' ', $value));
      }
      else {
        // Since Blazy is lazyloading known URLs, sanitize attributes which make
        // no sense to stick around within IMG or IFRAME tags.
        $kid = substr($key, 0, 2) === 'on' || in_array($key, $tags);
        $key = $kid ? 'data-' . $key : $key;
        $clean_attributes[$key] = $kid ? drupal_clean_css_identifier($value) : check_plain($value);
      }
    }
    return $clean_attributes;
  }

  /**
   * Returns the trusted HTML ID of a single instance.
   */
  public static function getHtmlId($string = 'blazy', $id = '') {
    if (!isset(static::$blazyId)) {
      static::$blazyId = 0;
    }

    // Do not use dynamic Html::getUniqueId, otherwise broken AJAX.
    $id = empty($id) ? ($string . '-' . ++static::$blazyId) : $id;
    return trim(str_replace('_', '-', strip_tags($id)));
  }

}
