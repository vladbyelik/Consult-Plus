<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFormatterBase;
use Drupal\slick\SlickDefault;
use Drupal\slick\SlickFormatterInterface;
use Drupal\slick\SlickManagerInterface;

/**
 * Base class for slick image and file entity (Media) formatters.
 *
 * @see Drupal\slick\Plugin\Field\SlickFileFormatter
 * @see Drupal\slick\Plugin\Field\SlickImageFormatter
 */
abstract class SlickFormatterBase extends BlazyFormatterBase {

  /**
   * Constructs a SlickImageFormatter instance.
   */
  public function __construct($plugin_id, $field, $instance, SlickFormatterInterface $formatter, SlickManagerInterface $manager) {
    parent::__construct($plugin_id, $field, $instance);
    $this->formatter = $formatter;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SlickDefault::imageSettings();
  }

  /**
   * Build the slick carousel elements.
   */
  public function buildElements(array &$build, $items) {
    $settings = $build['settings'];
    $item_id = $settings['item_id'];

    foreach ($items as $delta => $item) {
      $settings['delta'] = $delta;
      $settings['type']  = isset($item->type) ? $item->type : 'image';
      $settings['uri']   = $item->uri;

      $element = ['item' => $item, 'settings' => $settings, 'content' => []];

      // Provide advanced functionality to build fieldable elements.
      $this->buildElement($element, $item, $delta);

      // Build individual slick item.
      $build['items'][$delta] = $element;

      // Build individual slick thumbnail.
      if (!empty($settings['nav'])) {
        $thumb = ['settings' => $element['settings']];

        // Thumbnail usages: asNavFor pagers, dot, arrows, photobox thumbnails.
        $thumb[$item_id] = empty($settings['thumbnail_style']) ? [] : $this->formatter()->getThumbnail($element['settings'], $element['item']);
        $thumb['caption'] = empty($settings['thumbnail_caption']) ? [] : array_filter($this->getCaption($item, $settings['thumbnail_caption'], $settings));
        $build['thumb']['items'][$delta] = $thumb;
      }
    }
  }

  /**
   * Build caption element if so configured can be used for thumbnail caption.
   */
  abstract protected function getCaption($entity, $field_name, $settings);

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    return [
      'namespace'       => 'slick',
      'nav'             => TRUE,
      'thumb_captions'  => ['title' => t('Title'), 'alt' => t('Alt')],
      'thumb_positions' => TRUE,
    ] + parent::getScopedFormElements();
  }

}
