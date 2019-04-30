<?php

namespace Drupal\slick\Entity;

use Drupal\blazy\Utility\NestedArray;

/**
 * Defines the Slick configuration entity.
 */
abstract class SlickBase implements SlickBaseInterface {

  /**
   * Defines slick table name.
   */
  const TABLE = 'slick_optionset';

  /**
   * The legacy CTools ID for the configurable optionset.
   *
   * @var string
   */
  public $name;

  /**
   * The human-readable name for the optionset.
   *
   * @var string
   */
  public $label;

  /**
   * The plugin instance options.
   *
   * @var array
   */
  public $options = [];

  /**
   * The plugin default settings.
   *
   * @var array
   */
  protected static $defaultSettings;

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->name;
  }

  /**
   * The slick label.
   *
   * @var string
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public static function load($id = 'default') {
    ctools_include('export');
    $optionset = ctools_export_crud_load(static::TABLE, $id);

    // Ensures deleted optionset while being used doesn't screw up.
    if (!isset($optionset->name)) {
      $optionset = ctools_export_crud_load(static::TABLE, 'default');
    }

    return $optionset;
  }

  /**
   * Load the optionset with a fallback.
   */
  public static function loadWithFallback($id) {
    $optionset = self::load($id);

    // Ensures deleted optionset while being used doesn't screw up.
    if (empty($optionset)) {
      $optionset = self::load('default');
    }
    return $optionset;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultiple($reset = FALSE) {
    ctools_include('export');
    return ctools_export_crud_load_all(static::TABLE, $reset);
  }

  /**
   * {@inheritdoc}
   */
  public static function exists($name) {
    ctools_include('export');
    $optionset = ctools_export_crud_load(static::TABLE, $name);
    return isset($optionset->name);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    ctools_include('export');

    $optionset = ctools_export_crud_new(static::TABLE);

    $optionset->options = $optionset->options['settings'] = [];
    foreach (self::defaultProperties() as $key => $ignore) {
      if (isset($values[$key])) {
        $optionset->{$key} = $values[$key];
      }
    }

    if (empty($values['label']) && isset($values['name'])) {
      $optionset->label = $values['name'];
    }

    $defaults['settings'] = self::defaultSettings();
    $optionset->options = $optionset->options + $defaults;
    return $optionset;
  }

  /**
   * Saves the optionset to database.
   *
   * @return mixed
   *   Returns the newly saved or updated object, FALSE otherwise.
   */
  public function save() {
    $data = $this->toArray();
    $update = self::exists($data['name']) ? ['name'] : [];

    $defaults['settings'] = self::defaultSettings();
    $data['options'] = $data['options'] + $defaults;

    return drupal_write_record(static::TABLE, $data, $update);
  }

  /**
   * Deletes the optionset from database.
   *
   * This only deletes from the database, which means that if an item is in
   * code, then this is actually a revert.
   */
  public function delete() {
    ctools_include('export');
    ctools_export_crud_delete(static::TABLE, $this->name);
  }

  /**
   * Returns the typecast values.
   *
   * @param array $settings
   *   An array of Optionset settings.
   */
  public static function typecast(array &$settings = []) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($group = NULL, $property = NULL) {
    if ($group) {
      if (is_array($group)) {
        return NestedArray::getValue($this->options, (array) $group);
      }
      elseif (isset($property) && isset($this->options[$group])) {
        return isset($this->options[$group][$property]) ? $this->options[$group][$property] : NULL;
      }
      return isset($this->options[$group]) ? $this->options[$group] : NULL;
    }

    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings($ansich = FALSE) {
    if ($ansich && isset($this->options['settings'])) {
      return $this->options['settings'];
    }

    // With the Optimized options, all defaults are cleaned out, merge em.
    return isset($this->options['settings']) ? NestedArray::mergeDeep(self::defaultSettings(), $this->options['settings']) : self::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings = []) {
    $this->options['settings'] = $settings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name) {
    return isset($this->getSettings()[$name]) ? $this->getSettings()[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($name, $value) {
    $this->options['settings'][$name] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [];
  }

  /**
   * Returns default database field property values.
   *
   * @return mixed[]
   *   An array of property values, keyed by property name.
   */
  public static function defaultProperties() {
    return [
      'name'    => 'default',
      'label'   => 'Default',
      'options' => [],
    ];
  }

  /**
   * Returns an array of all property values.
   *
   * @return mixed[]
   *   An array of property values, keyed by property name.
   */
  public function toArray() {
    $values = [];
    foreach (self::defaultProperties() as $key => $ignore) {
      $values[$key] = $this->{$key};
    }
    return $values;
  }

}
