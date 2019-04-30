<?php

namespace Drupal\slick;

use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;

/**
 * Defines shared plugin default settings for field formatter and Views style.
 *
 * @see FormatterBase::defaultSettings()
 * @see StylePluginBase::defineOptions()
 */
class SlickDefault extends BlazyDefault {

  /**
   * Defines constant for the supported fields.
   */
  const FIELDS = ['file', 'image', 'field_collection', 'paragraphs', 'text'];

  /**
   * {@inheritdoc}
   */
  public static function baseSettings() {
    return [
      'optionset'    => 'default',
      'override'     => FALSE,
      'overridables' => [],
      'skin'         => '',
      'skin_arrows'  => '',
      'skin_dots'    => '',
    ] + parent::baseSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function gridSettings() {
    return [
      'preserve_keys' => FALSE,
      'visible_items' => 0,
    ] + parent::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function imageSettings() {
    return [
      'layout'              => '',
      'optionset_thumbnail' => '',
      'skin_thumbnail'      => '',
      'thumbnail_caption'   => '',
      'thumbnail_effect'    => '',
      'thumbnail_position'  => '',
    ] + self::baseSettings() + parent::imageSettings() + self::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function extendedSettings() {
    return [
      'thumbnail' => '',
    ] + self::imageSettings() + parent::extendedSettings();
  }

  /**
   * Returns HTML or layout related settings to shut up notices.
   */
  public static function htmlSettings() {
    return [
      'display'       => 'main',
      'grid'          => 0,
      'id'            => '',
      'lazy'          => '',
      'namespace'     => 'slick',
      'nav'           => FALSE,
      'navpos'        => FALSE,
      'thumbnail_uri' => '',
      'unslick'       => FALSE,
      'vanilla'       => FALSE,
      'vertical'      => FALSE,
      'vertical_tn'   => FALSE,
      'view_name'     => '',
    ] + self::imageSettings();
  }

  /**
   * Defines JS options required by theme_slick(), used with optimized option.
   */
  public static function jsSettings() {
    return [
      'asNavFor'        => '',
      'prevArrow'       => '<button type="button" data-role="none" class="slick-prev" aria-label="Previous" tabindex="0">Previous</button>',
      'nextArrow'       => '<button type="button" data-role="none" class="slick-next" aria-label="Next" tabindex="0">Next</button>',
      'downArrowTarget' => '',
      'downArrowOffset' => '',
      'lazyLoad'        => 'ondemand',
      'rows'            => 1,
      'slidesPerRow'    => 1,
      'slide'           => '',
      'slidesToShow'    => 1,
      'vertical'        => FALSE,
    ];
  }

  /**
   * Returns slick theme properties.
   */
  public static function themeProperties() {
    return [
      'attached',
      'attributes',
      'items',
      'options',
      'optionset',
      'settings',
    ];
  }

  /**
   * Returns default settings for typecasting.
   */
  public static function formSettings() {
    return [
      'module_css'           => TRUE,
      'slick_css'            => TRUE,
      'deprecated'           => TRUE,
      'deprecated_formatter' => TRUE,
    ];
  }

  /**
   * Update deprecated settings prefixed with `slide_` to be without one.
   */
  public static function deprecatedSettings() {
    return [
      'caption',
      'class',
      'image',
      'layout',
      'overlay',
      'link',
      'thumbnail',
      'title',
    ];
  }

  /**
   * Dummy method to remove.
   *
   * @todo temp fix for Slick failing test missing Blazy class.
   *
   * @see https://dispatcher.drupalci.org/job/drupal_d7/121280/artifact/jenkins-drupal_d7-121280/artifacts/run_tests_d7/
   */
  public static function dummy() {
    return Blazy::PAGES;
  }

}
