<?php

namespace Drupal\slick_views\Plugin\views\style;

/**
 * Slick style plugin.
 *
 * @ingroup views_style_plugins
 */
class SlickViews extends SlickViewsBase {

  /**
   * Overrides parent::buildOptionsForm().
   */
  public function options_form(&$form, &$form_state) {
    $definition = $this->getDefinedFormScopes();
    $this->buildSettingsForm($form, $definition);
  }

  /**
   * Overrides StylePluginBase::render().
   */
  public function render() {
    $settings = $this->buildSettings();
    $elements = [];

    foreach ($this->render_grouping($this->view->result, $settings['grouping']) as $rows) {
      $build = $this->buildElements($settings, $rows);

      // Extracts Blazy formatter settings if available.
      if (empty($settings['vanilla']) && isset($build['items'][0])) {
        $this->blazyManager()->isBlazy($settings, $build['items'][0]);
      }

      // Supports Blazy multi-breakpoint images if using Blazy formatter.
      $settings['first_image'] = isset($rows[0]) ? $this->getFirstImage($rows[0]) : [];

      $build['settings'] = $settings;

      $elements = $this->manager()->build($build);
      unset($build);
    }

    // Attach library if there is no results and ajax is active,
    // otherwise library will not be attached on ajax callback.
    // Note the empty space, a trick to solve: Undefined variable: empty...
    // No markup is output, yet the library is still attached on the page.
    // When this is reached, the $elements is an empty array.
    if (empty($this->view->result) && $this->view->use_ajax) {
      $elements['#markup'] = ' ';
      $elements['#attached'] = $this->manager()->attach($settings);
    }

    return $elements;
  }

}
