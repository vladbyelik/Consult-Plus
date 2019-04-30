<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyEntityReferenceBase;
use Drupal\slick\SlickDefault;
use Drupal\slick\SlickFormatterInterface;
use Drupal\slick\SlickManagerInterface;

/**
 * Base class for slick entity reference formatters with field details.
 */
abstract class SlickEntityReferenceFormatterBase extends BlazyEntityReferenceBase {

  use SlickFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SlickDefault::extendedSettings() + parent::defaultSettings();
  }

  /**
   * Constructs a SlickEntityReferenceFormatter instance.
   */
  public function __construct($plugin_id, $field, $instance, SlickFormatterInterface $formatter, SlickManagerInterface $manager) {
    parent::__construct($plugin_id, $field, $instance);
    $this->formatter = $formatter;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    $scopes = parent::getScopedFormElements();
    return [
      'nav'             => TRUE,
      'layouts'         => $this->stringOptions,
      'thumb_captions'  => $this->textOptions,
      'thumb_positions' => TRUE,
      'use_view_mode'   => TRUE,
      'vanilla'         => TRUE,
    ] + $scopes;
  }

}
