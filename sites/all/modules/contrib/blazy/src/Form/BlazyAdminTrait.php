<?php

namespace Drupal\blazy\Form;

/**
 * A Trait common for blazy-related plugins.
 *
 * Provides objects which cannot be instatiated with a DI for when the classes
 * are instantiated/ locked within procedural functions such as Views hooks.
 */
trait BlazyAdminTrait {

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdmin
   */
  protected $admin;

  /**
   * Returns the blazy admin service.
   */
  public function admin() {
    if (!isset($this->admin)) {
      $this->admin = new BlazyAdmin($this->manager());
    }
    return $this->admin;
  }

}
