<?php

namespace Drupal\blazy\Plugin\Filter;

/**
 * Defines re-usable services and functions for blazy plugins.
 */
interface BlazyFilterInterface {

  /**
   * Cleanups invalid nodes or those of which their contents are moved.
   *
   * @param \DOMDocument $dom
   *   The HTML DOM object being modified.
   */
  public function cleanupNodes(\DOMDocument &$dom);

  /**
   * Build the grid.
   *
   * @param \DOMDocument $dom
   *   The HTML DOM object being modified.
   * @param array $settings
   *   The settings array.
   * @param array $elements
   *   The renderable array of blazy item.
   * @param array $grid_nodes
   *   The grid nodes.
   */
  public function buildGrid(\DOMDocument &$dom, array &$settings, array $elements = [], array $grid_nodes = []);

  /**
   * Returns the faked image item for the image, uploaded or hard-coded.
   *
   * @param array $build
   *   The content array being modified.
   * @param object $node
   *   The HTML DOM object.
   */
  public function buildImageItem(array &$build, &$node);

  /**
   * Returns the settings for the current $node.
   *
   * @param array $settings
   *   The settings being modified.
   * @param object $node
   *   The HTML DOM object.
   */
  public function buildSettings(array &$settings, $node);

  /**
   * Submit handler for hook_filter_admin_format_form_alter().
   *
   * The hustle is due to non-bubbleable cache metadata at D7, needs to catch
   * at least the enabled settings without querying database.
   */
  public function submitForm($form, &$form_state);

}
