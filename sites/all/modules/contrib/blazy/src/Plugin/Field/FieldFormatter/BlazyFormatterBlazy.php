<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\blazy\BlazyManagerInterface;
use Drupal\blazy\Form\BlazyAdminFormatter;

/**
 * Plugin implementation of the `Blazy File` or `Blazy Image` for Blazy only.
 *
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFileFormatter
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyImageFormatter
 */
class BlazyFormatterBlazy extends BlazyFormatterBase {

  /**
   * Constructs a BlazyFormatter instance.
   */
  public function __construct($plugin_id, $field, $instance, BlazyManagerInterface $formatter) {
    parent::__construct($plugin_id, $field, $instance);
    $this->formatter = $formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function admin() {
    if (!isset($this->admin)) {
      $this->admin = new BlazyAdminFormatter($this->formatter);
    }
    return $this->admin;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements($items, $entity) {
    $build = [];
    $files = $this->getEntitiesToView($items);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $build;
    }

    // Collects specific settings to this formatter.
    $settings              = $this->buildSettings();
    $settings['blazy']     = TRUE;
    $settings['namespace'] = $settings['item_id'] = $settings['lazy'] = 'blazy';

    // Build the settings.
    $build = ['settings' => $settings];

    // Modifies settings before building elements.
    $this->formatter->preBuildElements($build, $files, $entity);

    // Build the elements.
    $this->buildElements($build, $files);

    // Modifies settings post building elements.
    $this->formatter->postBuildElements($build, $files, $entity);

    // Pass to manager for easy updates to all Blazy formatters.
    return $this->formatter->build($build);
  }

  /**
   * Build the Blazy elements for image and file entity/ media.
   */
  public function buildElements(array &$build, $files) {
    $settings = $build['settings'];

    foreach ($files as $delta => $item) {
      // Some settings need to be available before arriving at theme_blazy() so
      // that lightboxes and media switcher can get context to work with.
      // The trouble is they are not always available depending on file entity
      // and other supported modules availability which is not always there.
      $settings['delta'] = $delta;
      $settings['type']  = isset($item->type) ? $item->type : 'image';
      $settings['uri']   = isset($item->uri) ? $item->uri : '';
      $box['item']       = $item;
      $box['settings']   = $settings;

      $this->buildElement($box, $item, $delta);

      // Build caption if so configured.
      if (!empty($settings['caption'])) {
        foreach ($settings['caption'] as $caption) {
          if (isset($item->{$caption}) && $caption_content = array_filter($this->getCaption($item, $caption, $settings))) {
            $box['captions'][$caption] = $caption_content;
          }
        }
      }

      // Image with grid, responsive image, lazyLoad, and lightbox supports.
      $build[$delta] = $this->formatter()->getBlazy($box);
      unset($box);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildElement(array &$element, $entity, $delta = 0) {
    // Do nothing.
  }

  /**
   * Returns the captions.
   */
  protected function getCaption($entity, $field_name, $settings) {
    return [];
  }

}
