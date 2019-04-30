<?php

namespace Drupal\blazy\Plugin\views\field;

/**
 * Defines a custom field that renders a preview of a file.
 *
 * @ViewsField("blazy_file")
 */
class BlazyViewsFieldFile extends BlazyViewsFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render($values) {
    /** @var \Drupal\file\Entity\File $entity */
    $fid = $values->fid;
    $entity = file_load($fid);
    $settings = $this->mergedViewsSettings();
    $settings['delta'] = $this->view->row_index;
    $settings['entity_type_id'] = 'file';
    $settings['uri'] = $entity->uri;

    $data = $this->getImageItem($entity);
    $data['settings'] = isset($data['settings']) ? array_merge($settings, $data['settings']) : $settings;

    // Pass results to \Drupal\blazy\BlazyEntity.
    return $this->blazyEntity()->build($data, $entity);
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    return ['multimedia' => TRUE, 'view_mode' => 'default'] + parent::getScopedFormElements();
  }

}
