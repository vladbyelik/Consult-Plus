<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

/**
 * Base class for blazy/slick image, and file formatters.
 */
abstract class FormatterBase implements FormatterInterface {

  /**
   * The blazy-related admin formatter service.
   *
   * @var object
   */
  protected $admin;

  /**
   * The formatter settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * Whether default settings have been merged into the current $settings.
   *
   * @var bool
   */
  protected $defaultSettingsMerged = FALSE;

  /**
   * The form settings.
   *
   * @var array
   */
  protected $htmlSettings = [];

  /**
   * The field instance.
   *
   * @var array
   */
  protected $fieldInstance = [];

  /**
   * The field definition.
   *
   * @var array
   */
  protected $fieldDefinition = [];

  /**
   * The field display.
   *
   * @var array
   */
  protected $fieldDisplay = [];

  /**
   * The blazy formatter plugin id.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The langcode.
   *
   * @var string
   */
  protected $langcode;

  /**
   * The view mode.
   *
   * @var string
   */
  protected $viewMode;

  /**
   * True if the field cardinality equals -1.
   *
   * @var bool
   */
  protected $isMultiple = FALSE;

  /**
   * The known hard-coded entities.
   *
   * @var array
   */
  protected $knownEntities = ['field_collection', 'paragraphs'];

  /**
   * Constructs a base formatter object.
   */
  public function __construct($plugin_id, $field, $instance) {
    $this->pluginId = $plugin_id;
    $this->fieldDefinition = $field;
    $this->fieldInstance = $instance;
    $this->isMultiple = $field['cardinality'] == -1;
    $this->bundle = $instance['bundle'];
    $this->fieldName = $instance['field_name'];
    $this->entityType = $instance['entity_type'];
    $this->fieldType = $field['type'];

    // For more entities, it is overriden via blazy_entity_load().
    // Below is just for few known entities to save plugins from overriding.
    $this->targetType = in_array($field['type'], $this->knownEntities) ? $field['type'] . '_item' : $field['type'];
  }

  /**
   * Gets formatter settings.
   */
  public function getSettings() {
    // Merge defaults before returning the array.
    if (!$this->defaultSettingsMerged) {
      $this->mergeDefaults();
    }
    return $this->settings;
  }

  /**
   * Sets formatter settings.
   */
  public function setSettings(array $settings = []) {
    $this->settings = $settings;
    $this->defaultSettingsMerged = FALSE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    // Merge defaults if we have no value for the key.
    if (!$this->defaultSettingsMerged && !array_key_exists($key, $this->settings)) {
      $this->mergeDefaults();
    }
    return isset($this->settings[$key]) ? $this->settings[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($key, $value) {
    $this->settings[$key] = $value;
    return $this;
  }

  /**
   * Merges default settings values into $settings.
   */
  protected function mergeDefaults() {
    $this->settings += static::defaultSettings();
    $this->defaultSettingsMerged = TRUE;
  }

  /**
   * Sets html settings.
   */
  public function setHtmlSettings(array $settings = []) {
    $this->htmlSettings = $settings;
    return $this;
  }

  /**
   * Gets html settings.
   */
  public function getHtmlSettings() {
    return array_merge((array) $this->htmlSettings, (array) $this->getSettings());
  }

  /**
   * Gets formatter plugin id.
   */
  public function getPluginId() {
    return $this->pluginId;
  }

  /**
   * Builds the settings.
   */
  public function buildSettings() {
    return array_merge((array) $this->htmlSettings, (array) $this->settings);
  }

  /**
   * Implements hook_field_formatter_view().
   */
  public function view($items, $langcode, $entity_type, $entity, $display) {
    list($entity_id) = entity_extract_ids($entity_type, $entity);

    // Simplify relevant function arguments into settings array.
    $this->fieldDisplay = $display;
    $this->entity = $entity;
    $settings = $this->setupFieldVariables();
    $settings['entity_id'] = $entity_id;
    $this->langcode = $settings['langcode'] = $langcode;

    // Gets view_mode from formatter_view.
    $this->viewMode = isset($settings['current_view_mode']) ? $settings['current_view_mode'] : 'default';
    $this->setHtmlSettings($settings);

    return $this->viewElements($items, $entity);
  }

  /**
   * Implements hook_field_formatter_settings_form().
   */
  public function buildSettingsForm($form, &$form_state, $view_mode) {
    $display = $this->fieldInstance['display'][$view_mode];
    $this->fieldDisplay = $display;
    $this->viewMode = $view_mode;
    $this->fieldInstance['display'][$view_mode]['current_view_mode'] = $view_mode;
    $this->setupFieldVariables();

    return $this->settingsForm($form, $form_state, $this->getScopedFormElements());
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettingsSummary($view_mode) {
    $this->fieldDisplay = $this->fieldInstance['display'][$view_mode];
    $this->viewMode = $view_mode;
    $this->setupFieldVariables();

    return $this->settingsSummary($this->getScopedFormElements());
  }

  /**
   * Setup common variables across different hooks.
   */
  protected function setupFieldVariables() {
    $settings = $this->fieldDisplay['settings'];

    // The actual formatter settings for database, and summaries.
    $this->setSettings($settings);

    // Additional settings for the formatters and forms to work with.
    $settings['bundle'] = $this->bundle;
    $settings['field_name'] = $this->fieldName;
    $settings['entity_type_id'] = $this->entityType;
    $settings['field_type'] = $this->fieldType;
    $settings['multiple'] = $this->isMultiple;
    $settings['target_type'] = $this->targetType;
    $settings['plugin_id'] = $this->fieldDisplay['type'];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function viewElements($items, $entity);

  /**
   * {@inheritdoc}
   */
  public function getRequiredForms() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, &$form_state, $definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function settingsSummary(array $definition);

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    return [
      'forms'          => $this->getRequiredForms(),
      'entity_type_id' => $this->entityType,
      'field_name'     => $this->fieldName,
      'field_type'     => $this->fieldType,
      'instance'       => $this->fieldInstance,
      'plugin_id'      => $this->pluginId,
      'settings'       => $this->getSettings(),
      'target_type'    => $this->targetType,
    ];
  }

}
