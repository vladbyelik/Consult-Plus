<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

/**
 * Defines re-usable formatter methods for blazy plugins.
 */
interface FormatterInterface {

  /**
   * Returns required form elements for the current formatter.
   */
  public function getRequiredForms();

  /**
   * Returns default settings.
   */
  public static function defaultSettings();

  /**
   * Implements hook_field_formatter_view().
   */
  public function viewElements($items, $entity);

  /**
   * Implements hook_field_formatter_settings_form().
   */
  public function settingsForm($form, &$form_state, $definition);

  /**
   * Implements hook_field_formatter_settings_summary().
   */
  public function settingsSummary(array $definition);

}
