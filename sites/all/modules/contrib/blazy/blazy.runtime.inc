<?php

/**
 * @file
 * Provides basic Blazy integration for lazy loading and multi-serving images.
 */

use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\BlazyFormatter;
use Drupal\blazy\BlazyLibrary;
use Drupal\blazy\BlazyManager;
use Drupal\blazy\Plugin\Filter\BlazyFilter;
use Drupal\blazy\Plugin\Field\FieldFormatter\FormatterPlugin;

/**
 * Returns one of the Blazy service objects mainly used by procedural hooks.
 *
 * @param string $key
 *   Identifier of the service.
 *
 * @return class
 *   The required Blazy class instance.
 */
function blazy($key = 'manager') {
  static $filter;
  static $formatter;
  static $library;
  static $manager;
  static $plugin;

  if (!isset($manager)) {
    $formatter = new BlazyFormatter();
    $manager   = new BlazyManager();
    $plugin    = new FormatterPlugin($formatter, $manager);
  }

  switch ($key) {
    case 'formatter':
      return $formatter;

    case 'plugin':
      return $plugin;

    case 'filter':
      if (!isset($filter)) {
        $filter = new BlazyFilter($manager);
      }
      return $filter;

    case 'library':
      if (!isset($library)) {
        $library = new BlazyLibrary($manager);
      }
      return $library;

    default:
      return $manager;
  }
}

/**
 * Implements hook_library().
 */
function blazy_library() {
  return blazy('library')->library();
}

/**
 * Implements hook_libraries_info().
 */
function blazy_libraries_info() {
  return blazy('library')->librariesInfo();
}

/**
 * Implements hook_page_build().
 */
function blazy_page_build(&$page) {
  blazy('library')->pageBuild($page);
}

/**
 * Builds the image as a structured array ready for drupal_render().
 */
function blazy_pre_render($element) {
  return blazy()->preRender($element);
}

/**
 * Builds the entire images as a structured array ready for drupal_render().
 */
function blazy_pre_render_build($element) {
  return blazy()->preRenderBuild($element);
}

/**
 * Implements hook_field_formatter_info().
 */
function blazy_field_formatter_info() {
  return blazy('plugin')->formatterInfo();
}

/**
 * Implements hook_field_formatter_view().
 */
function blazy_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $type = in_array($field['type'], BlazyDefault::TEXTS) ? 'text' : $field['type'];
  return blazy('plugin')->getFormatter($type, $field, $instance)->view($items, $langcode, $entity_type, $entity, $display);
}

/**
 * Implements hook_field_formatter_settings_form().
 */
function blazy_field_formatter_settings_form($field, $instance, $view_mode, $form, &$form_state) {
  // The $field is empty at admin/structure/file-types/manage/image/file-display
  // and this form is also loaded there via file_entity_file_formatter_info().
  if (empty($field)) {
    return [];
  }

  $type = in_array($field['type'], BlazyDefault::TEXTS) ? 'text' : $field['type'];
  return blazy('plugin')->getFormatter($type, $field, $instance)->buildSettingsForm($form, $form_state, $view_mode);
}

/**
 * Implements hook_field_formatter_settings_summary().
 */
function blazy_field_formatter_settings_summary($field, $instance, $view_mode) {
  $type = in_array($field['type'], BlazyDefault::TEXTS) ? 'text' : $field['type'];
  return blazy('plugin')->getFormatter($type, $field, $instance)->buildSettingsSummary($view_mode);
}

/**
 * Implements hook_filter_info().
 */
function blazy_filter_info() {
  return blazy('filter')->filterInfo();
}

/**
 * Implements callback_filter_process().
 */
function _blazy_filter_process($text, $filter, $format, $langcode, $cache, $cache_id) {
  return blazy('filter')->process($text, $filter, $langcode);
}

/**
 * Implements callback_filter_tips().
 */
function _blazy_filter_tips($filter, $format, $long) {
  return blazy('filter')->tips($filter, $long);
}

/**
 * Implements callback_filter_settings().
 */
function _blazy_filter_settings_form($form, &$form_state, $filter, $format, $defaults, $filters) {
  $filter->settings += $defaults;
  return blazy('filter')->settingsForm($form, $form_state, $filter);
}

/**
 * Submit handler for hook_form_FORM_ID_alter().
 */
function blazy_form_filter_admin_format_form_alter(&$form, $form_state) {
  $form['#submit'][] = 'blazy_filter_settings_form_submit';
}

/**
 * Submit handler for hook_form_FORM_ID_alter().
 */
function blazy_filter_settings_form_submit($form, &$form_state) {
  blazy('filter')->submitForm($form, $form_state);
}

/**
 * Implements hook_views_api().
 */
function blazy_views_api() {
  // Views says "Don't call us, we'll call you!".
  return ['api' => 3];
}

/**
 * Implements hook_views_pre_render().
 */
function blazy_views_pre_render($view) {
  // Pass Blazy Views field settings into Blazy Grid if found.
  if (isset($view) && isset($view->field['blazy_file']) && $blazy = $view->field['blazy_file']) {
    $plugin_id = $view->style_plugin->plugin_name;
    if ($plugin_id == 'blazy') {
      $view->style_plugin->options = array_merge((array) $blazy->mergedViewsSettings(), $view->style_plugin->options);
    }
  }
}
