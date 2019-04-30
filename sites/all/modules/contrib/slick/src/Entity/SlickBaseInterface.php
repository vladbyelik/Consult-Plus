<?php

namespace Drupal\slick\Entity;

/**
 * Provides an interface defining a Slick entity.
 */
interface SlickBaseInterface {

  /**
   * Returns the given optionset object identified by $id.
   *
   * @param string $id
   *   The optionset ID with property name, or default.
   *
   * @return object
   *   Returns the optionset, or else default, if no optionset found.
   */
  public static function load($id = 'default');

  /**
   * Fetches all optionsets from the storage.
   *
   * @param bool $reset
   *   If TRUE, the static cache of all objects will be flushed prior to
   *   loading all. This can be important on listing pages where items
   *   might have changed on the page load.
   *
   * @return array
   *   The associative array of all optionsets.
   */
  public static function loadMultiple($reset = FALSE);

  /**
   * Checks whether an optionset with the given name already exists.
   *
   * @param string $name
   *   The Optionset machine name.
   *
   * @return bool
   *   Returns TRUE if exists, FALSE otherwise.
   */
  public static function exists($name);

  /**
   * Returns a new optionset object without saving it to the database.
   *
   * @param array $values
   *   The values to build the optionset if provided.
   *
   * @return object
   *   Returns the programmatically created optionset object.
   */
  public static function create(array $values = []);

  /**
   * Returns the Slick options by group, or property.
   *
   * @param string $group
   *   The name of setting group: settings, responsives.
   * @param string $property
   *   The name of specific property: prevArrow, nexArrow.
   *
   * @return mixed|array|null
   *   Available options by $group, $property, all, or NULL.
   */
  public function getOptions($group = NULL, $property = NULL);

  /**
   * Returns the array of slick settings.
   *
   * @param string $ansich
   *   Whether to return the settings as is.
   *
   * @return array
   *   The array of settings.
   */
  public function getSettings($ansich = FALSE);

  /**
   * Sets the array of slick settings.
   *
   * @param array $settings
   *   The new array of settings.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setSettings(array $settings = []);

  /**
   * Returns the value of a slick setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting($setting_name);

  /**
   * Sets the value of a slick setting.
   *
   * @param string $setting_name
   *   The setting name.
   * @param string $value
   *   The setting value.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setSetting($setting_name, $value);

  /**
   * Returns available slick default options under group 'settings'.
   *
   * @return array
   *   The default settings under options.
   */
  public static function defaultSettings();

}
