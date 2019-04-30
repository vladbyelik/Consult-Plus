<?php

namespace Drupal\blazy;

/**
 * Provides common field formatter-related methods: Blazy, Slick.
 */
class BlazyFormatter extends BlazyManager {

  /**
   * The first image item found.
   *
   * @var object
   */
  protected $firstItem = NULL;

  /**
   * Modifies the field formatter settings inherited by child elements.
   *
   * @param array $build
   *   The array containing: settings, or potential optionset for extensions.
   * @param object $items
   *   The items to prepare settings for.
   * @param object $entity
   *   The entity this field belongs to.
   */
  public function buildSettings(array &$build, $items, $entity) {
    $settings       = &$build['settings'];
    $count          = count($items);
    $entity_type_id = $settings['entity_type_id'];
    $entity_id      = $settings['entity_id'];
    $bundle         = $settings['bundle'];
    $field_name     = $settings['field_name'];
    $field_clean    = str_replace('field_', '', $field_name);
    $view_mode      = empty($settings['current_view_mode']) ? '_custom' : $settings['current_view_mode'];
    $namespace      = $settings['namespace'] = empty($settings['namespace']) ? 'blazy' : $settings['namespace'];
    $id             = isset($settings['id']) ? $settings['id'] : '';
    $gallery_id     = "{$namespace}-{$entity_type_id}-{$bundle}-{$field_clean}-{$view_mode}";
    $id             = Blazy::getHtmlId("{$gallery_id}-{$entity_id}", $id);
    $switch         = empty($settings['media_switch']) ? '' : $settings['media_switch'];
    $internal_path  = entity_uri($entity_type_id, $entity);
    $langcode       = $settings['langcode'];

    // Pass settings to child elements.
    $settings['cache_metadata'] = ['keys' => [$id, $count, $langcode]];
    $settings['content_url']    = isset($internal_path['path']) ? $internal_path['path'] : '';
    $settings['count']          = $count;
    $settings['gallery_id']     = str_replace('_', '-', $gallery_id . '-' . $switch);
    $settings['id']             = $id;
    $settings['lightbox']       = ($switch && in_array($switch, $this->getLightboxes())) ? $switch : FALSE;
    $settings['entity']         = empty($settings['lightbox']) ? NULL : $entity;

    // Don't bother with Vanilla on.
    if (!empty($settings['vanilla'])) {
      $settings = array_filter($settings);
      return;
    }

    // Don't bother if using Responsive image.
    $settings['breakpoints'] = isset($settings['breakpoints']) && empty($settings['responsive_image_style']) ? $settings['breakpoints'] : [];
    $settings['caption']     = empty($settings['caption']) ? [] : array_filter($settings['caption']);
    $settings['background']  = empty($settings['responsive_image_style']) && !empty($settings['background']);
    $settings['placeholder'] = $this->config('placeholder', '', 'blazy.settings');
    $settings['resimage']    = function_exists('picture_mapping_load') && $this->config('responsive_image', FALSE, 'blazy.settings') && !empty($settings['responsive_image_style']);
    $settings['blazy']       = $settings['resimage'] || !empty($settings['blazy']);

    // At D7, BlazyFilter can only attach globally, prevents blocking.
    // Allows lightboxes to provide its own optionsets.
    if ($switch) {
      $settings[$switch] = empty($settings[$switch]) ? $switch : $settings[$switch];
    }

    // Let Blazy handle CSS background as Slick's background is deprecated.
    if ($settings['background']) {
      $settings['blazy'] = TRUE;
    }

    if ($settings['blazy']) {
      $settings['lazy'] = 'blazy';
    }

    // Aspect ratio isn't working with Responsive image, yet.
    // However allows custom work to get going with an enforced.
    $ratio = FALSE;
    if (!empty($settings['ratio'])) {
      $ratio = empty($settings['responsive_image_style']);
      if ($settings['ratio'] == 'enforced' || $settings['background']) {
        $ratio = TRUE;
      }
    }

    $settings['ratio'] = $ratio ? $settings['ratio'] : FALSE;
  }

  /**
   * Modifies the field formatter settings inherited by child elements.
   *
   * @param array $build
   *   The array containing: settings, or potential optionset for extensions.
   * @param object $items
   *   The Drupal\Core\Field\FieldItemListInterface items.
   * @param object $entity
   *   The entity this field belongs to.
   * @param array $entities
   *   The optional entities array, not available for non-entities: text, image.
   */
  public function preBuildElements(array &$build, $items, $entity, array $entities = []) {
    $this->buildSettings($build, $items, $entity);
    $settings = &$build['settings'];

    // Pass first item to optimize sizes this time.
    if (isset($items[0]) && $item = $items[0]) {
      $first_entity = isset($entities[0]) ? $entities[0] : NULL;
      $this->extractFirstItem($settings, $item, $first_entity);
    }

    // Sets dimensions once, if cropped, to reduce costs with ton of images.
    // This is less expensive than re-defining dimensions per image.
    $this->cleanUpBreakpoints($settings);
    if (!empty($settings['first_uri']) && !$settings['resimage']) {
      $this->setDimensionsOnce($settings, $this->firstItem);
    }

    // Add the entity to formatter cache tags.
    // @todo $settings['cache_tags'][] = $settings['entity_type_id'] . ':' . $settings['entity_id'];
    // Sniffs for Views to allow block__no_wrapper, views_no_wrapper, etc.
    if (function_exists('views_get_current_view') && $view = views_get_current_view()) {
      $settings['view_name'] = $view->name;
      $settings['current_view_mode'] = $view->current_display;
    }

    // Allows altering the settings.
    drupal_alter('blazy_settings', $build, $items);
  }

  /**
   * Modifies the field formatter settings not inherited by child elements.
   *
   * @param array $build
   *   The array containing: items, settings, or a potential optionset.
   * @param object $items
   *   The Drupal\Core\Field\FieldItemListInterface items.
   * @param object $entity
   *   The entity this field belongs to.
   * @param array $entities
   *   The optional entities array, not available for non-entities: text, image.
   */
  public function postBuildElements(array &$build, $items, $entity, array $entities = []) {
    // Rebuild the first item to build colorbox/zoom-like gallery.
    $build['settings']['first_item'] = $this->firstItem;
  }

  /**
   * Extract the first image item to build colorbox/zoom-like gallery.
   *
   * @param array $settings
   *   The $settings array being modified.
   * @param object $item
   *   The Drupal\image\Plugin\Field\FieldType\ImageItem item.
   * @param object $entity
   *   The optional media entity.
   */
  public function extractFirstItem(array &$settings, $item, $entity = NULL) {
    if ($settings['field_type'] == 'image') {
      $this->firstItem = (object) $item;
    }
    elseif ($settings['field_type'] == 'file' && $image = BlazyMedia::imageItem($item)) {
      $this->firstItem = $image;
    }
    $settings['first_uri'] = $this->firstItem && isset($this->firstItem->uri) ? $this->firstItem->uri : '';
  }

  /**
   * Sets dimensions once to reduce method calls, if image style contains crop.
   *
   * The implementor should only call this if not using Responsive image style.
   *
   * @param array $settings
   *   The settings being modified.
   * @param object $item
   *   The first image item found.
   */
  public function setDimensionsOnce(array &$settings, $item = NULL) {
    if (!isset($this->isDimensionSet[md5($settings['first_uri'])])) {

      // If image style contains crop, sets dimension once, and let all inherit.
      if (!empty($settings['image_style']) && $this->isCrop($settings['image_style'])) {
        $settings = array_merge($settings, Blazy::transformDimensions($settings['image_style'], $item));

        // Informs individual images that dimensions are already set once.
        $settings['_dimensions'] = TRUE;
      }

      // Also sets breakpoint dimensions once, if cropped.
      if (!empty($settings['breakpoints'])) {
        $this->buildDataBlazy($settings, $item);
      }

      $this->isDimensionSet[md5($settings['first_uri'])] = TRUE;
    }
  }

}
