<?php

namespace Drupal\blazy\Plugin\views\field;

use Drupal\blazy\BlazyDefault;
use Drupal\blazy\BlazyEntity;
use Drupal\blazy\BlazyManagerTrait;
use Drupal\blazy\Form\BlazyAdminTrait;
use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyVideoTrait;
use views_handler_field;

/**
 * Defines a base views field plugin to render a preview of supported fields.
 *
 * Cannot use a namespace, else broken Views.
 */
abstract class BlazyViewsFieldPluginBase extends views_handler_field {

  use BlazyManagerTrait;
  use BlazyVideoTrait;
  use BlazyAdminTrait;

  /**
   * The blazy entity instance.
   *
   * @var object
   */
  protected $blazyEntity;

  /**
   * Returns the blazy entity instance.
   */
  public function blazyEntity() {
    if (!isset($this->blazyEntity)) {
      $this->blazyEntity = new BlazyEntity($this->formatter());
    }
    return $this->blazyEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function option_definition() {
    $options = parent::option_definition();

    foreach ($this->getDefaultValues() as $key => $default) {
      $options[$key] = ['default' => $default];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function options_form(&$form, &$form_state) {
    $definitions = $this->getScopedFormElements();

    $form += $this->admin()->baseForm($definitions);

    foreach ($this->getDefaultValues() as $key => $default) {
      if (isset($form[$key])) {
        $form[$key]['#default_value'] = isset($this->options[$key]) ? $this->options[$key] : $default;
        $form[$key]['#weight'] = 0;
        if (in_array($key, ['box_style', 'box_media_style'])) {
          $form[$key]['#empty_option'] = t('- None -');
        }
      }
    }

    if (isset($form['view_mode'])) {
      $form['view_mode']['#description'] = t('Will fallback to this view mode, else entity label.');
    }
    parent::options_form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render($values) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * Defines the default values.
   */
  public function getDefaultValues() {
    return [
      'box_style'       => '',
      'box_media_style' => '',
      'image_style'     => '',
      'media_switch'    => 'media',
      'ratio'           => 'fluid',
      'thumbnail_style' => '',
      'view_mode'       => 'default',
    ];
  }

  /**
   * Merges the settings.
   */
  public function mergedViewsSettings() {
    $settings = [];

    // Only fetch what we already asked for.
    foreach ($this->getDefaultValues() as $key => $default) {
      $settings[$key] = isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    $settings['count'] = count($this->view->result);
    $settings['current_view_mode'] = $this->view->current_display;
    $settings['view_name'] = $this->view->name;

    return array_merge(BlazyDefault::entitySettings(), $settings);
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    return [
      'settings' => $this->options,
      'target_type' => 'file',
      'thumbnail_style' => TRUE,
    ];
  }

}
