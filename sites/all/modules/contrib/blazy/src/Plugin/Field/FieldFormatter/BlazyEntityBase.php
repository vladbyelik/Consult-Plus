<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\blazy\BlazyEntity;

/**
 * Base class for entity reference formatters without field details.
 *
 * @see Drupal\slick\Plugin\Field\FieldFormatter\SlickEntityFormatterBase
 * @see Drupal\slick\Plugin\Field\FieldFormatter\SlickEntityReferenceFormatterBase
 * @see Drupal\slick\Plugin\Field\FieldFormatter\SlickFieldCollectionFormatter
 * @see Drupal\slick\Plugin\Field\FieldFormatter\SlickParagraphsFormatter
 */
abstract class BlazyEntityBase extends BlazyFormatterBase {

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
  protected function getEntitiesToView($items) {
    if (empty($items)) {
      return [];
    }

    // Assumes we have entityreference, or sub-moduled which prepare the view.
    $entities = [];
    foreach ($items as $item) {
      // Skip an item that is not accessible.
      if (empty($item['access'])) {
        continue;
      }

      $entity = clone $item['entity'];
      unset($entity->content);
      $entities[] = $entity;
    }
    return $entities;
  }

  /**
   * Returns media contents.
   */
  public function buildElements(array &$build, $entities) {
    $settings = &$build['settings'];
    foreach ($entities as $delta => $entity) {
      // Overrides Constructor::targetType via blazy_entity_load().
      // Safe as a formatter is designed to work for a particular entity type
      // like what entityreference does, to not be confused with bundles.
      $this->targetType = $settings['target_type'] = $entity->targetType;
      list($entity_id) = entity_extract_ids($this->targetType, $entity);

      // Protect ourselves from recursive rendering.
      static $depth = 0;
      $depth++;
      if ($depth > 20) {
        throw new \Exception(t('Recursive rendering detected when rendering entity @entity_type(@entity_id). Aborting rendering.', ['@entity_type' => $settings['entity_type_id'], '@entity_id' => $entity_id]));
      }

      $settings['delta'] = $delta;
      $settings['entity_id'] = $entity_id;
      $this->buildElement($build, $entity, $delta);

      $depth = 0;
    }

    // Supports Blazy formatter multi-breakpoint images if available.
    $settings['check_blazy'] = empty($settings['vanilla']);
  }

  /**
   * Build individual item contents.
   */
  public function buildElement(array &$build, $entity, $delta = 0) {
    $build['items'][$delta] = $this->blazyEntity()->entityView($entity->targetType, $entity, $build['settings']) ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, &$form_state, $definition) {
    parent::settingsForm($form, $form_state, $definition);

    $element = [];
    $definition['_views'] = isset($form['field_api_classes']);

    $this->admin()->buildSettingsForm($element, $definition);
    return $element;
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    return [
      'use_view_mode' => TRUE,
    ] + parent::getScopedFormElements();
  }

}
