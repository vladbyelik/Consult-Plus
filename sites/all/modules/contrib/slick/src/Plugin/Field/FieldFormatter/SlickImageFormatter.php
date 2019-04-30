<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'Slick Image' formatter.
 */
class SlickImageFormatter extends SlickFormatterBase {

  use SlickFormatterTrait;

  /**
   * {@inheritdoc}
   */
  protected function getCaption($item, $caption, $settings) {
    return empty($item->{$caption}) ? [] : ['#markup' => filter_xss_admin($item->{$caption})];
  }

  /**
   * {@inheritdoc}
   */
  public function buildElement(array &$element, $entity, $delta = 0) {
    $settings = $element['settings'];
    $item_id = $settings['item_id'];

    // Image with responsive image, lazyLoad, and lightbox supports.
    $element[$item_id] = $this->formatter()->getBlazy($element);

    // Build caption if so configured, supports file entity/ media via $file.
    if (!empty($settings['caption'])) {
      foreach ($settings['caption'] as $caption) {
        $element['caption'][$caption] = $this->getCaption($entity, $caption, $element['settings']);
      }
    }
  }

}
