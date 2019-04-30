<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

/**
 * Plugin for the Blazy image formatter.
 */
class BlazyImageFormatter extends BlazyFormatterBlazy {

  use BlazyFormatterTrait;

  /**
   * {@inheritdoc}
   */
  protected function getCaption($entity, $field_name, $settings) {
    return empty($entity->{$field_name}) ? [] : ['#markup' => filter_xss_admin($entity->{$field_name})];
  }

}
