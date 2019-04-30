<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\blazy\BlazyManagerInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Form\BlazyAdminFormatter;

/**
 * Plugin implementation of the 'Blazy Text' formatter to have a grid of texts.
 */
class BlazyTextFormatter extends FormatterBase {

  use BlazyFormatterTrait;

  /**
   * Constructs a BlazyTextFormatter instance.
   */
  public function __construct($plugin_id, $field, $instance, BlazyManagerInterface $formatter) {
    parent::__construct($plugin_id, $field, $instance);
    $this->formatter = $formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::baseSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredForms() {
    return [
      'grid' => $this->isMultiple,
    ];
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
    $settings = $this->buildSettings();
    $settings['_grid'] = !empty($settings['style']) && !empty($settings['grid']);

    // Hand over to default formatter if not multiple as it is for grid only.
    // @todo figure out to disable formatter like isApplicable() at D8 in the
    // first place, and remove this.
    if (!$this->isMultiple || !$settings['_grid']) {
      $types = field_info_field_types($this->fieldDefinition['type']);
      $display['type'] = isset($types['default_formatter']) ? $types['default_formatter'] : '';

      if ($fallback = text_field_formatter_view($this->entityType, $entity, $this->fieldDefinition, $this->fieldInstance, $this->langcode, $items, $display)) {
        return $fallback;
      }
    }

    $settings['vanilla'] = TRUE;
    $settings['namespace'] = $settings['item_id'] = 'blazy';

    // Build the settings.
    $build = ['settings' => $settings];

    // Modifies settings before building elements.
    $this->formatter->preBuildElements($build, $items, $entity);

    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    foreach ($items as $item) {
      $content = _text_sanitize($this->fieldInstance, $settings['langcode'], $item, 'value');
      $build[] = ['#markup' => $content];
      unset($content);
    }

    // Pass to manager for easy updates to all Blazy formatters.
    return $this->formatter->build($build);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, &$form_state, $definition) {
    $element = [];

    // @todo remove once D8-like isApplicable() for cardinality - 1 landed.
    if (!$this->isMultiple) {
      return $element;
    }

    $this->admin()->buildSettingsForm($element, $definition);
    return $element;
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    return [
      'grid_required'    => $this->isMultiple,
      'no_image_style'   => TRUE,
      'no_layouts'       => TRUE,
      'responsive_image' => FALSE,
      'style'            => TRUE,
    ] + parent::getScopedFormElements();
  }

}
