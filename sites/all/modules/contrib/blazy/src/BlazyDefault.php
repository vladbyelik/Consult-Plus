<?php

namespace Drupal\blazy;

/**
 * Defines shared plugin default settings for field formatter and Views style.
 */
class BlazyDefault {

  /**
   * Defines constant for the supported text fields.
   */
  const TEXTS = ['text', 'text_long', 'text_with_summary'];

  /**
   * Defines constant for the supported text tags.
   */
  const TAGS = ['a', 'em', 'strong', 'h2', 'p', 'span', 'ul', 'ol', 'li'];

  /**
   * Defines constant pages related to Blazy filter at D7.
   */
  const PAGES = "admin*\nimagebrowser*\nimg_assist*\nimce*\nnode/add/*\nnode/*/edit\nprint/*\nprintpdf/*\nsystem/ajax\nsystem/ajax/*";

  /**
   * The supported $breakpoints.
   *
   * @var array
   */
  private static $breakpoints = ['xs', 'sm', 'md', 'lg', 'xl'];

  /**
   * Returns Blazy specific breakpoints.
   */
  public static function getConstantBreakpoints() {
    return self::$breakpoints;
  }

  /**
   * Returns basic plugin settings: text, image, file, entities with grids.
   */
  public static function baseSettings() {
    $settings = [
      'cache'             => 0,
      'current_view_mode' => 'default',
    ] + self::gridSettings();

    $context = ['class' => get_called_class()];
    drupal_alter('blazy_base_settings', $settings, $context);
    return $settings;
  }

  /**
   * Returns optional grid field formatter and Views settings.
   */
  public static function gridSettings() {
    return [
      'grid'        => 0,
      'grid_medium' => 0,
      'grid_small'  => 0,
      'style'       => '',
    ];
  }

  /**
   * Returns image-related field formatter and Views settings.
   */
  public static function baseImageSettings() {
    return [
      'background'             => FALSE,
      'box_caption'            => '',
      'box_caption_custom'     => '',
      'box_style'              => '',
      'box_media_style'        => '',
      'breakpoints'            => [],
      'caption'                => [],
      'image_style'            => '',
      'lazy'                   => 'blazy',
      'media_switch'           => '',
      'ratio'                  => '',
      'responsive_image_style' => '',
      'sizes'                  => '',
    ];
  }

  /**
   * Returns image-related field formatter and Views settings.
   */
  public static function imageSettings() {
    return [
      'thumbnail_style' => '',
      'view_mode'       => '',
    ] + self::baseSettings() + self::baseImageSettings();
  }

  /**
   * Returns Views specific settings.
   */
  public static function viewsSettings() {
    return [
      'class'   => '',
      'id'      => '',
      'image'   => '',
      'link'    => '',
      'overlay' => '',
      'title'   => '',
      'vanilla' => FALSE,
    ];
  }

  /**
   * Returns fieldable entity formatter and Views settings.
   */
  public static function extendedSettings() {
    return self::viewsSettings() + self::imageSettings();
  }

  /**
   * Returns sensible default options common for Views lacking of UI.
   */
  public static function lazySettings() {
    return [
      'blazy' => TRUE,
      'lazy'  => 'blazy',
      'ratio' => 'fluid',
    ];
  }

  /**
   * Returns sensible default options common for entities lacking of UI.
   */
  public static function entitySettings() {
    return [
      'media_switch' => 'media',
      'rendered'     => FALSE,
      'view_mode'    => 'default',
      '_detached'    => TRUE,
    ] + self::lazySettings();
  }

  /**
   * Returns sensible default container settings to shutup notices when lacking.
   */
  public static function htmlSettings() {
    return [
      'blazy_data' => [],
      'lightbox'   => FALSE,
      'namespace'  => 'blazy',
      'id'         => '',
    ] + self::imageSettings();
  }

  /**
   * Returns sensible default html settings to shutup notices when lacking.
   */
  public static function itemSettings() {
    return [
      'content_url'    => '',
      'delta'          => 0,
      'embed_url'      => '',
      'entity_type_id' => '',
      'icon'           => '',
      'image_url'      => '',
      'item_id'        => 'blazy',
      'lazy_attribute' => 'src',
      'lazy_class'     => 'b-lazy',
      'one_pixel'      => TRUE,
      'placeholder'    => '',
      'padding_bottom' => '',
      'picture'        => FALSE,
      'player'         => FALSE,
      'scheme'         => '',
      'type'           => 'image',
      'uri'            => '',
      'use_data_uri'   => FALSE,
      'thumbnail_uri'  => '',
      'use_image'      => TRUE,
      'use_loading'    => TRUE,
      'use_media'      => FALSE,
      'height'         => NULL,
      'width'          => NULL,
    ] + self::htmlSettings();
  }

  /**
   * Returns blazy theme properties.
   */
  public static function themeProperties() {
    return [
      'attributes',
      'captions',
      'iframe',
      'item',
      'settings',
      'url',
    ];
  }

  /**
   * Returns blazy theme attributes.
   */
  public static function themeAttributes() {
    return ['caption', 'item', 'media', 'url', 'wrapper'];
  }

  /**
   * Returns blazy UI settings for typecasting, done via config schema at D8.
   */
  public static function formSettings() {
    return [
      'admin_css'        => TRUE,
      'responsive_image' => FALSE,
      'unbreakpoints'    => FALSE,
      'one_pixel'        => TRUE,
      'placeholder'      => '',
      'visibility'       => 0,
      'pages'            => static::PAGES,
      'extras'           => [],
      'blazy'            => [
        'loadInvisible'           => FALSE,
        'offset'                  => 100,
        'saveViewportOffsetDelay' => 50,
        'validateDelay'           => 25,
      ],
      'io'               => [
        'enabled'    => FALSE,
        'unblazy'    => FALSE,
        'disconnect' => FALSE,
        'rootMargin' => '0px',
        'threshold'  => '0',
      ],
      'filters'          => [
        'column'       => TRUE,
        'grid'         => TRUE,
        'media_switch' => '',
        'use_data_uri' => FALSE,
      ],
    ];
  }

}
