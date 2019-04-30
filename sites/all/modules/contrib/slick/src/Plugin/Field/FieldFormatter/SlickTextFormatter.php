<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\blazy\Plugin\Field\FieldFormatter\FormatterBase;
use Drupal\slick\SlickFormatterInterface;
use Drupal\slick\SlickManagerInterface;
use Drupal\slick\SlickDefault;

/**
 * Plugin implementation of the 'Slick Text' formatter.
 */
class SlickTextFormatter extends FormatterBase {

  use SlickFormatterTrait;

  /**
   * Constructs a SlickTextFormatter instance.
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
    return SlickDefault::baseSettings() + SlickDefault::gridSettings();
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
  public function viewElements($items, $entity) {
    $settings = $this->buildSettings();
    // Hand over to default formatter if not multiple.
    // @todo figure out to disable formatter like isApplicable() at D8 in the
    // first place, and remove this.
    if (!$this->isMultiple) {
      $types = field_info_field_types($this->fieldDefinition['type']);
      $display['type'] = isset($types['default_formatter']) ? $types['default_formatter'] : '';

      if ($fallback = text_field_formatter_view($settings['entity_type_id'], $entity, $this->fieldDefinition, $this->fieldInstance, $this->langcode, $items, $display)) {
        return $fallback;
      }
    }

    $settings['vanilla'] = TRUE;

    // Build the settings.
    $build = ['settings' => $settings];

    // Modifies settings before building elements.
    $this->formatter()->preBuildElements($build, $items, $entity);

    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    foreach ($items as $item) {
      $text = _text_sanitize($this->fieldInstance, $settings['langcode'], $item, 'value');
      $build['items'][] = ['#markup' => $text];
      unset($text);
    }

    // If using 0, or directly passed like D8, taken over by theme_field().
    $element = $this->manager()->build($build);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, &$form_state, $definition) {
    $element = [];

    // @todo remove once D8-like isApplicable() for cardinality - 1 is working.
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
      'no_image_style'   => TRUE,
      'no_layouts'       => TRUE,
      'responsive_image' => FALSE,
      'style'            => TRUE,
    ] + parent::getScopedFormElements();
  }

}
