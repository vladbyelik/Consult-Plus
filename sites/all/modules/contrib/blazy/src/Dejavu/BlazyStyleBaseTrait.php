<?php

namespace Drupal\blazy\Dejavu;

use Drupal\blazy\Utility\NestedArray;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;

/**
 * A Trait common for optional views style plugins.
 */
trait BlazyStyleBaseTrait {

  /**
   * The first Blazy formatter found to get data from for lightbox gallery, etc.
   *
   * @var array
   */
  protected $firstImage;

  /**
   * The dynamic html settings.
   *
   * @var array
   */
  protected $htmlSettings = [];

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * Provides commons settings for the style plugins.
   */
  protected function buildSettings() {
    global $language;

    $langcode  = isset($language->language) ? $language->language : LANGUAGE_NONE;
    $view      = $this->view;
    $count     = count($view->result);
    $settings  = $this->options;
    $view_name = $view->name;
    $view_mode = $view->current_display;
    $instance  = str_replace('_', '-', "{$view_name}-{$view_mode}");
    $plugin_id = $view->plugin_name;
    $id        = empty($settings['id']) ? '' : $settings['id'];
    $id        = Blazy::getHtmlId("{$plugin_id}-views-{$instance}", $id);
    $settings += [
      'cache_metadata' => [
        'keys' => [$id, $view_mode, $count, $langcode],
      ],
    ] + BlazyDefault::lazySettings();

    // Prepare needed settings to work with.
    $settings['check_blazy']       = TRUE;
    $settings['id']                = $id;
    $settings['count']             = $count;
    $settings['current_view_mode'] = $view_mode;
    $settings['instance_id']       = $instance;
    $settings['multiple']          = TRUE;
    $settings['plugin_id']         = $plugin_id;
    $settings['use_ajax']          = $view->use_ajax;
    $settings['view_name']         = $view_name;
    $settings['view_display']      = $view->style_plugin->display->display_plugin;
    $settings['_views']            = TRUE;

    if (!empty($this->htmlSettings)) {
      $settings = NestedArray::mergeDeep($settings, $this->htmlSettings);
    }

    drupal_alter('blazy_settings_views', $settings, $view);
    return $settings;
  }

  /**
   * Sets dynamic html settings.
   */
  protected function setHtmlSettings(array $settings = []) {
    $this->htmlSettings = $settings;
    return $this;
  }

  /**
   * Returns the first Blazy formatter found.
   */
  public function getFirstImage($row) {
    if (!isset($this->firstImage)) {
      $rendered = [];
      if ($row) {
        foreach ($row as $item) {
          if (is_array($item) && isset($item[0]['rendered']) && isset($item[0]['rendered']['#build'])) {
            $rendered = $item[0]['rendered']['#build'];
            break;
          }
        }
      }
      $this->firstImage = $rendered;
    }
    return $this->firstImage;
  }

  /**
   * Returns the renderable array of field containing rendered and raw data.
   */
  public function getFieldRenderable($row, $index, $field_name = '', $multiple = FALSE) {
    // Be sure to not check "Use field template" under "Style settings" to have
    // renderable array to work with, otherwise flattened string!
    $field = $this->view->field[$field_name]->handler_type . '_' . $field_name;
    return $multiple && isset($row->{$field}) ? $row->{$field} : (isset($row->{$field}[0]) ? $row->{$field}[0] : '');
  }

}
