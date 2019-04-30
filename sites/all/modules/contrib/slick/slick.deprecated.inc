<?php

/**
 * @file
 * To be removed once Slick views, etc. have 3.x, or before Slick full release.
 *
 * You can disable this file inclusion at Slick UI after all-slick related
 * modules (Slick Views, Slick Extras, etc.) migrated into 3.x.
 *
 * @deprecated to be removed.
 */

use Drupal\blazy\Blazy;
use Drupal\slick\Entity\Slick;

/**
 * To be removed.
 *
 * @deprecated for Slick::defaultSettings().
 */
function slick_get_options() {
  return Slick::defaultSettings();
}

/**
 * To be removed.
 *
 * @deprecated for SlickManager::getSkins().
 */
function slick_skins() {
  // @todo cannot use return slick()->getSkins(); for BC layer which
  // may use MY_MODULE.slick.inc instead of class file.
  $skins = &drupal_static(__FUNCTION__, NULL);
  if (!isset($skins)) {
    $skins = module_invoke_all('slick_skins_info');
    drupal_alter('slick_skins_info', $skins);
  }
  return $skins;
}

/**
 * To be removed.
 *
 * @deprecated for SlickManager::getSkinsByGroup().
 */
function slick_get_skins_by_group($group = '', $select = FALSE) {
  return slick()->getSkinsByGroup($group, $select);
}

/**
 * To be removed.
 *
 * @deprecated for Blazy::getHtmlId().
 */
function slick_html_id($string = 'slick', $id = '') {
  return Blazy::getHtmlId($string, $id);
}

/**
 * To be removed.
 *
 * @deprecated for Slick::create()
 */
function slick_optionset_create(array $values = []) {
  return Slick::create($values);
}

/**
 * To be removed.
 *
 * @deprecated for SlickManager::build().
 */
function slick_build(array $items, array $options, array $settings = [], array $attach = [], $id = NULL, $optionset = NULL) {
  $loaded = isset($attach['css']) || isset($attach['js']) || isset($attach['library']);
  $attachments = $loaded ? $attach : slick_attach($attach, $settings);
  $build = [
    'items'     => $items,
    'settings'  => $settings,
    'options'   => $options,
    'optionset' => $optionset,
    'attached'  => $attachments,
  ];
  return slick()->slick($build);
}

/**
 * To be removed.
 *
 * @deprecated for SlickManager::build().
 */
function slick_render_cache($settings = []) {
  return FALSE;
}

/**
 * To be removed.
 */
function slick_get_image(array $settings = [], array &$media = [], array $item = []) {
  $build = [
    'item' => (object) $item,
    'settings' => array_merge($settings, $media),
  ];

  return slick('formatter')->getBlazy($build);
}

/**
 * To be removed.
 */
function slick_get_dimensions($media = [], $image_style = 'large') {
  return [];
}
