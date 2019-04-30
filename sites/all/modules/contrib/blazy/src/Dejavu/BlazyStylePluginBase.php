<?php

namespace Drupal\blazy\Dejavu;

use Drupal\blazy\BlazyManager;
use views_plugin_style;

/**
 * A base for blazy views integration to have re-usable methods in one place.
 *
 * This file is not used by Blazy, but for its related-modules to DRY.
 *
 * @see \Drupal\mason\Plugin\views\style\MasonViews
 * @see \Drupal\gridstack\Plugin\views\style\GridStackViews
 * @see \Drupal\slick_views\Plugin\views\style\SlickViews
 */
abstract class BlazyStylePluginBase extends views_plugin_style {

  use BlazyStyleBaseTrait;
  use BlazyStyleOptionsTrait;
  use BlazyStylePluginTrait;

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
    if (!isset($this->blazyManager)) {
      $this->blazyManager = new BlazyManager();
    }
    return $this->blazyManager;
  }

  /**
   * Returns an individual row/element content.
   */
  public function buildElement(array &$element, $row, $index) {
    $settings = &$element['settings'];
    $item_id = empty($settings['item_id']) ? 'box' : $settings['item_id'];

    // Add main image fields if so configured.
    if (!empty($settings['image'])) {
      // Supports individual grid/box image style either inline IMG, or CSS.
      $image             = $this->getImageRenderable($settings, $row, $index);
      $element['item']   = $this->getImageItem($image);
      $element[$item_id] = empty($image['rendered']) ? [] : $image['rendered'];
    }

    // Add caption fields if so configured.
    $element['caption'] = $this->getCaption($index, $settings);

    // Add layout field, may be a list field, or builtin layout options.
    if (!empty($settings['layout'])) {
      $this->getLayout($settings, $index);
    }
  }

}
