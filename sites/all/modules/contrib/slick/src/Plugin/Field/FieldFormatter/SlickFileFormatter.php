<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\blazy\BlazyEntity;
use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyVideoTrait;
use Drupal\slick\SlickDefault;

/**
 * Plugin implementation of the 'Slick File' formatter for Media integration.
 *
 * Unfortunately, file entity is not based on `entity`, so no re-use.
 */
class SlickFileFormatter extends SlickFormatterBase {

  use SlickFormatterTrait;
  use BlazyVideoTrait;

  /**
   * The blazy entity instance.
   *
   * @var object
   */
  protected $blazyEntity;

  /**
   * Returns the blazy entity object.
   */
  public function blazyEntity() {
    if (!isset($this->blazyEntity)) {
      $this->blazyEntity = new BlazyEntity($this->formatter);
    }
    return $this->blazyEntity;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SlickDefault::extendedSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettings() {
    return ['blazy' => TRUE] + parent::buildSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredForms() {
    return ['fieldable' => TRUE] + parent::getRequiredForms();
  }

  /**
   * Build the slick carousel elements.
   */
  public function buildElements(array &$build, $items) {
    $settings = &$build['settings'];
    $item_id = $settings['item_id'];

    // Render items as is when using vanilla.
    if (!empty($settings['vanilla'])) {
      foreach ($items as $delta => $item) {
        $settings['delta'] = $delta;
        $settings['type'] = isset($item->type) ? $item->type : 'image';

        $element = ['item' => $item, 'settings' => $settings];
        $element[$item_id] = $this->blazyEntity()->entityView($this->targetType, $item, $settings);

        // Build individual slick item.
        $build['items'][$delta] = $element;
      }

      return;
    }

    // Otherwise process elements based on advanced features.
    parent::buildElements($build, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function buildElement(array &$element, $entity, $delta = 0) {
    $settings = $element['settings'];
    $item_id = $settings['item_id'];

    // Layouts can be builtin, or field, if so configured.
    if (!empty($settings['layout'])) {
      if (strpos($settings['layout'], 'field_') !== FALSE && isset($entity->{$settings['layout']})) {
        $settings['layout'] = $this->blazyEntity()->getFieldString($entity, $settings['layout'], $settings);
      }
      $element['settings']['layout'] = $settings['layout'];
    }

    // Classes, if so configured.
    if (!empty($settings['class']) && isset($entity->{$settings['class']})) {
      $element['settings']['class'] = $this->blazyEntity()->getFieldString($entity, $settings['class'], $settings);
    }

    // If imported Drupal\blazy\Dejavu\BlazyVideoTrait.
    // Extract image item from file, and assign it to $box['item'] so that
    // Blazy can display an image along with video, or just mixed.
    if ($settings['type'] == 'video') {
      if ($image = $this->getImageItem($entity)) {
        $element['item'] = $image['item'];
        $element['settings'] = array_merge($element['settings'], $image['settings']);
      }

      $this->getMediaItem($element, $entity);
    }

    // Build the main stage.
    // @todo $this->buildStage($element, $entity);
    if (!empty($settings['image'])
      && isset($entity->{$settings['image']})
      && $image = field_get_items($this->targetType, $entity, $settings['image'])) {
      if (isset($image[0]) && isset($image[0]['uri'])) {
        $image = (object) $image[0];
        $replacement = new \stdClass();
        foreach (['uri', 'alt', 'title', 'type', 'height', 'width'] as $key) {
          $replacement->{$key} = isset($image->{$key}) ? $image->{$key} : NULL;
        }

        $replacement->target_id = $image->fid;
        $element['item'] = $replacement;
      }

      // If Image rendered is picked, render image as is, a rare case.
      if (!empty($settings['media_switch']) && $settings['media_switch'] == 'rendered') {
        $content[] = $this->blazyEntity()->getFieldRenderable($entity, $settings['image'], $settings, TRUE);
      }
    }

    // Optional image with responsive image, lazyLoad, and lightbox supports.
    $element[$item_id] = isset($content) ? $content : $this->formatter()->getBlazy($element);

    // Build caption if so configured, supports file entity/ media via $entity.
    if (!empty($settings['caption'])) {
      foreach ($settings['caption'] as $caption) {
        if (isset($entity->{$caption}) && $caption_content = array_filter($this->getCaption($entity, $caption, $settings))) {
          // Put into data sepecific for fieldable entity captions where caption
          // can be anything: text, link, image, etc.
          $element['caption']['data'][$caption] = $caption_content;
        }
      }
    }

    // Title can be plain text, or link field.
    // Field can be deleted anytime while $settings hold references, play safe.
    if (!empty($settings['title']) && isset($entity->{$settings['title']})) {
      if ($title = $this->blazyEntity()->getFieldTextOrLink($entity, $settings['title'], $settings)) {
        $element['caption']['title'] = $title;
      }
    }

    // Link, if so configured.
    // Field can be deleted anytime while $settings hold references, play safe.
    if (!empty($settings['link']) && isset($entity->{$settings['link']})) {
      $element['caption']['link'] = $this->blazyEntity()->getFieldRenderable($entity, $settings['link'], $settings, TRUE);
    }

    // Overlay, if so configured.
    // Field can be deleted anytime while $settings hold references, play safe.
    if (!empty($settings['overlay']) && isset($entity->{$settings['overlay']})) {
      $element['caption']['overlay'] = $this->blazyEntity()->getFieldRenderable($entity, $settings['overlay'], $settings, TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getCaption($entity, $field_name, $settings) {
    return $this->blazyEntity()->getFieldRenderable($entity, $field_name, $settings, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    $bundles = $this->fieldDefinition['bundles'];
    $strings = ['text', 'list_text'];
    $strings = $this->admin()->getFieldOptions($this->fieldInstance, $strings, $this->targetType, $bundles);
    $texts   = ['text', 'text_long', 'text_with_summary', 'link_field', 'url'];
    $texts   = $this->admin()->getFieldOptions($this->fieldInstance, $texts, $this->targetType, $bundles);
    $links   = ['text', 'link_field', 'url'];
    $links   = $this->admin()->getFieldOptions($this->fieldInstance, $links, $this->targetType, $bundles);

    return [
      'captions'        => $this->admin()->getFieldOptions($this->fieldInstance, [], $this->targetType, $bundles),
      'images'          => $this->admin()->getFieldOptions($this->fieldInstance, ['image'], $this->targetType, $bundles),
      'multimedia'      => TRUE,
      'classes'         => $strings,
      'layouts'         => $strings,
      'links'           => $links,
      'titles'          => $texts,
      'thumb_captions'  => $texts,
      'use_view_mode'   => TRUE,
      'vanilla'         => TRUE,
    ] + parent::getScopedFormElements();
  }

}
