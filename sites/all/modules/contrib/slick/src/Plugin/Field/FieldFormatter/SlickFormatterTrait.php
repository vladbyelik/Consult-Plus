<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFormatterTrait;
use Drupal\slick\Form\SlickAdmin;

/**
 * A Trait common for slick formatters.
 */
trait SlickFormatterTrait {

  use BlazyFormatterTrait;

  /**
   * Returns the slick admin service.
   */
  public function admin() {
    if (!isset($this->admin)) {
      $this->admin = new SlickAdmin($this->manager);
    }
    return $this->admin;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements($items, $entity) {
    $entities = $this->getEntitiesToView($items);

    // Early opt-out if the field is empty.
    if (empty($entities)) {
      return [];
    }

    // Collects specific settings to this formatter.
    $this->entity = $entity;
    $settings = $this->buildSettings();
    $build = ['settings' => $settings];

    // Modifies settings before building elements.
    $this->formatter()->preBuildElements($build, $entities, $entity);

    // Build the elements.
    $this->buildElements($build, $entities);

    // Modifies settings post building elements.
    $this->formatter()->postBuildElements($build, $entities, $entity);

    // If using 0, or directly passed like D8, taken over by theme_field().
    $element = $this->manager()->build($build);
    return $element;
  }

}
