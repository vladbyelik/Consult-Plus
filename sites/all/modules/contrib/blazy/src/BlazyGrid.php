<?php

namespace Drupal\blazy;

/**
 * Provides grid utilities.
 */
class BlazyGrid {

  /**
   * Returns items wrapped by theme_item_list(), can be a grid, or plain list.
   *
   * @param array $items
   *   The grid items being modified.
   * @param array $settings
   *   The given settings.
   *
   * @return array
   *   The modified array of grid items.
   *
   * @todo implement cache.
   */
  public static function build(array $items = [], array $settings = []) {
    $settings  += BlazyDefault::gridSettings();
    $style      = empty($settings['style']) ? '' : $settings['style'];
    $is_gallery = !empty($settings['lightbox']) && !empty($settings['gallery_id']);
    $is_grid    = isset($settings['_grid']) ? $settings['_grid'] : (!empty($settings['style']) && !empty($settings['grid']));
    $class_item = $is_grid ? 'grid' : 'blazy__item';

    $contents = [];
    foreach ($items as $delta => $item) {
      $attributes = [];
      // Support non-Blazy which normally uses item_id.
      if (is_array($item)) {
        $attributes    = isset($item['attributes']) ? $item['attributes'] : [];
        $item_settings = isset($item['settings']) ? $item['settings'] : $settings;
        $item_settings = isset($item['#build']) && isset($item['#build']['settings']) ? $item['#build']['settings'] : $item_settings;

        if (isset($item['#build']) && isset($item['#build']['settings'])) {
          $item['#build']['settings'] += $settings;
          $item['#build']['settings']['delta'] = $delta;
        }

        unset($item['settings'], $item['attributes'], $item['item']);
      }

      if (!empty($item_settings['grid_item_class'])) {
        $attributes['class'][] = $item_settings['grid_item_class'];
      }

      // Supports single formatter field or complex fields such as Views.
      // Views or entity_view may flattened $item into a string.
      $content = is_string($item) ? ['#markup' => $item] : ['content' => $item];
      $content = $is_grid ? Blazy::container($content, ['class' => 'grid__content']) : $content;
      $classes = isset($attributes['class']) ? $attributes['class'] : [];
      $classes = array_merge([$class_item], $classes);
      $contents[$delta] = [
        'data'  => drupal_render($content),
        'class' => $classes,
      ];
    }

    // Provides hint about AJAX.
    if (!empty($settings['use_ajax'])) {
      $settings['blazy_data']['useAjax'] = TRUE;
    }

    $blazy   = empty($settings['blazy_data']) ? '' : drupal_json_encode($settings['blazy_data']);
    $count   = empty($settings['count']) ? count($contents) : $settings['count'];
    $element = [
      '#theme'      => 'item_list',
      '#items'      => $contents,
      '#context'    => ['settings' => $settings],
      '#attributes' => ['class' => ['blazy'], 'data-blazy' => $blazy],
    ];

    $attributes = &$element['#attributes'];
    if (!empty($settings['media_switch'])) {
      $switch = str_replace('_', '-', $settings['media_switch']);
      $attributes['data-' . $switch . '-gallery'] = TRUE;
      $attributes['class'][] = 'blazy--' . $switch;
    }

    if (!empty($settings['field_name'])) {
      $attributes['class'][] = 'blazy--field blazy--' . str_replace('_', '-', $settings['field_name']);
    }

    // Provides gallery ID, although Colorbox works without it, others may not.
    // Uniqueness is not crucial as a gallery needs to work across entities.
    if (!empty($settings['id'])) {
      $attributes['id'] = $is_gallery ? $settings['gallery_id'] : $settings['id'];
    }

    // Limit to grid only, so to be usable for plain list.
    if ($is_grid) {
      $attributes['class'][] = 'blazy--grid block-' . $style . ' block-count-' . $count;

      // Adds common grid attributes for CSS3 column, Foundation, etc.
      if ($settings['grid_large'] = $settings['grid']) {
        foreach (['small', 'medium', 'large'] as $grid) {
          if (!empty($settings['grid_' . $grid])) {
            $attributes['class'][] = $grid . '-block-' . $style . '-' . $settings['grid_' . $grid];
          }
        }
      }
    }

    return $element;
  }

}
