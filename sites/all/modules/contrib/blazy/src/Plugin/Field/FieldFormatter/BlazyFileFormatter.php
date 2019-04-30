<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\blazy\BlazyEntity;

/**
 * Plugin implementation of the 'Blazy File' to get videos within images/files.
 */
class BlazyFileFormatter extends BlazyFormatterBlazy {

  use BlazyFormatterTrait;
  use BlazyVideoTrait;

  /**
   * The blazy entity instance.
   *
   * @var object
   */
  protected $blazyEntity;

  /**
   * Returns the blazy entity object.
   */
  public function blazyEntity() {
    if (!isset($this->blazyEntity)) {
      $this->blazyEntity = new BlazyEntity($this->formatter);
    }
    return $this->blazyEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredForms() {
    return ['fieldable' => TRUE] + parent::getRequiredForms();
  }

  /**
   * {@inheritdoc}
   */
  public function buildElement(array &$element, $entity, $delta = 0) {
    $settings = $element['settings'];

    // Extract image item from file, and assign it to $element['item'] so that
    // Blazy can display an image along with video, or just mixed.
    if ($settings['type'] == 'video') {
      if ($image = $this->getImageItem($entity)) {
        $element['item'] = $image['item'];
        $element['settings'] = array_merge($settings, $image['settings']);
      }

      $this->getMediaItem($element, $entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getCaption($entity, $field_name, $settings) {
    return $this->blazyEntity()->getFieldRenderable($entity, $field_name, $settings, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    $bundles = !empty($this->fieldDefinition['bundles']) ? $this->fieldDefinition['bundles'] : [];

    return [
      'captions'      => $this->admin()->getFieldOptions($this->fieldInstance, [], $this->targetType, $bundles),
      'multimedia'    => TRUE,
      'target_type'   => $this->targetType,
      'use_view_mode' => TRUE,
    ] + parent::getScopedFormElements();
  }

}
