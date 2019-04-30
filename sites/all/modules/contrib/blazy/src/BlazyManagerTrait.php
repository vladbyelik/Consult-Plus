<?php

namespace Drupal\blazy;

/**
 * A Trait common for blazy-related plugins.
 *
 * Provides objects which cannot be instatiated with a DI for when the classes
 * are instantiated/ locked within procedural functions such as Views hooks.
 */
trait BlazyManagerTrait {

  /**
   * The blazy library service.
   *
   * @var \Drupal\blazy\BlazyLibrary
   */
  protected $library;

  /**
   * The blazy formatter service.
   *
   * @var \Drupal\blazy\BlazyFormatter
   */
  protected $formatter;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $manager;

  /**
   * Returns the blazy manager.
   */
  public function formatter() {
    if (!isset($this->formatter)) {
      $this->formatter = new BlazyFormatter();
    }
    return $this->formatter;
  }

  /**
   * Returns the blazy manager.
   */
  public function manager() {
    if (!isset($this->manager)) {
      $this->manager = new BlazyManager();
    }
    return $this->manager;
  }

}
