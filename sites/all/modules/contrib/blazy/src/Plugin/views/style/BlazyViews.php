<?php

namespace Drupal\blazy\Plugin\views\style;

use Drupal\blazy\BlazyDefault;
use Drupal\blazy\BlazyManagerTrait;
use Drupal\blazy\Form\BlazyAdminTrait;
use Drupal\blazy\Dejavu\BlazyStyleBaseTrait;
use views_plugin_style;

/**
 * Blazy style plugin.
 */
class BlazyViews extends views_plugin_style {

  use BlazyManagerTrait;
  use BlazyAdminTrait;
  use BlazyStyleBaseTrait;

  /**
   * {@inheritdoc}
   */
  public function option_definition() {
    $options = parent::option_definition();
    foreach (BlazyDefault::gridSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options;
  }

  /**
   * Overrides StylePluginBase::buildOptionsForm().
   */
  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $definition = [
      'namespace'     => 'blazy',
      'forms'         => ['grid' => TRUE],
      'settings'      => $this->options,
      'style'         => TRUE,
      'grid_required' => TRUE,
      'opening_class' => 'form--views',
    ];

    // Build the form.
    $this->admin()->openingForm($form, $definition);
    $this->admin()->gridForm($form, $definition);

    if (isset($form['grid'])) {
      $form['grid']['#description'] = t('The amount of block grid columns for large monitors 64.063em.');
    }

    $this->admin()->finalizeForm($form, $definition);

    // Blazy doesn't need complex grid with multiple groups.
    unset($form['layout'], $form['preserve_keys'], $form['visible_items']);
  }

  /**
   * Overrides StylePluginBase::render().
   */
  public function render() {
    $settings              = $this->buildSettings();
    $settings['item_id']   = 'content';
    $settings['namespace'] = 'blazy';

    $elements = [];
    foreach ($this->render_grouping($this->view->result, $settings['grouping']) as $rows) {
      $items = [];
      foreach ($rows as $index => $row) {
        $this->view->row_index = $index;

        $items[$index] = $this->view->style_plugin->row_plugin->render($row);
      }

      // Supports Blazy multi-breakpoint images if using Blazy formatter.
      $settings['first_image'] = isset($rows[0]) ? $this->getFirstImage($rows[0]) : [];
      $build = ['items' => $items, 'settings' => $settings];
      $elements = $this->manager()->build($build);

      unset($this->view->row_index);
    }

    return $elements;
  }

}
