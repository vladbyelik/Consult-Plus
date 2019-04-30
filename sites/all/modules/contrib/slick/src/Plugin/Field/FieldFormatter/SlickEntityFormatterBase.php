<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyEntityBase;
use Drupal\slick\SlickDefault;
use Drupal\slick\SlickFormatterInterface;
use Drupal\slick\SlickManagerInterface;

/**
 * Base class for slick entity reference formatters without field details.
 */
abstract class SlickEntityFormatterBase extends BlazyEntityBase {

  use SlickFormatterTrait;

  /**
   * Constructs a SlickEntityFormatter instance.
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
    return SlickDefault::baseSettings() + ['view_mode' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettings() {
    $settings = parent::buildSettings();

    // Asks for Blazy to deal with iFrames, and mobile-optimized lazy loading.
    $settings['blazy'] = TRUE;
    $settings['vanilla'] = TRUE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    return [
      'no_layouts' => TRUE,
    ] + parent::getScopedFormElements();
  }

}
