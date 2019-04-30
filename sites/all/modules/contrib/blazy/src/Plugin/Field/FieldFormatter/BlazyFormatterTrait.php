<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

/**
 * A Trait common for blazy formatters.
 */
trait BlazyFormatterTrait {

  /**
   * The blazy-related formatter service.
   *
   * @var \Drupal\blazy\BlazyFormatter
   */
  protected $formatter;

  /**
   * The blazy field formatter manager.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $manager;

  /**
   * Returns the blazy-related formatter.
   */
  public function formatter() {
    return $this->formatter;
  }

  /**
   * Returns the blazy service.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(array $definition) {
    return $this->admin()->getSettingsSummary($definition);
  }

}
