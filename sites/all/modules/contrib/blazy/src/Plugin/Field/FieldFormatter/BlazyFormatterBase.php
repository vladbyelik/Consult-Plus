<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\blazy\BlazyDefault;

/**
 * Base class for blazy-related modules (slick, etc.).
 *
 * Defines one base class to extend for both image and file entity formatters.
 *
 * @see Drupal\blazy\Plugin\Field\FieldFormatter\BlazyEntityBase
 * @see Drupal\blazy\Plugin\Field\FieldFormatter\BlazyImageFormatter
 * @see Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFileFormatter
 * @see Drupal\slick\Plugin\Field\FieldFormatter\SlickImageFormatter
 * @see Drupal\slick\Plugin\Field\FieldFormatter\SlickFileFormatter
 */
abstract class BlazyFormatterBase extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::imageSettings();
  }

  /**
   * Returns the blazy admin service for blazy-related module.
   */
  abstract public function admin();

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
   * {@inheritdoc}
   */
  public function getRequiredForms() {
    return [
      'grid'         => $this->isMultiple,
      'image_style'  => TRUE,
      'media_switch' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    return [
      'background'      => TRUE,
      'breakpoints'     => BlazyDefault::getConstantBreakpoints(),
      'box_captions'    => TRUE,
      'namespace'       => 'blazy',
      'style'           => $this->isMultiple,
      'thumbnail_style' => TRUE,
      'captions'        => ['title' => t('Title'), 'alt' => t('Alt')],
    ] + parent::getScopedFormElements();
  }

  /**
   * Converts $items array to object for easy D8 -> D7 backports.
   *
   * When extending this class, be sure to override this as needed as $items
   * can be just anything. This one assumes image, or file entity.
   * The actual D8 method is now taken care of at image_field_prepare_view().
   * At D7, image is not an entity, of course, bear the method name.
   *
   * @see \Drupal\blazy\BlazyEntityBase
   */
  protected function getEntitiesToView($items) {
    if (empty($items)) {
      return [];
    }

    $files = [];
    foreach ($items as $item) {
      $file = is_object($item) ? $item : (object) $item;
      $file->targetType = 'file';
      $files[] = $file;
    }
    return $files;
  }

  /**
   * Build individual item if so configured such as for file entity goodness.
   */
  abstract public function buildElement(array &$element, $entity, $delta = 0);

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(array $definition) {
    return $this->admin()->getSettingsSummary($definition);
  }

}
