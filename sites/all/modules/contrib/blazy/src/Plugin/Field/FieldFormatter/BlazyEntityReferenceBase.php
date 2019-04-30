<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\blazy\BlazyDefault;

/**
 * Base class for entity reference formatters with field details.
 *
 * Applicable for Field Collection, Paragraphs, etc, save for File Entity.
 *
 * @see Drupal\slick\Plugin\Field\FieldFormatter\SlickFieldCollectionFormatter
 * @see Drupal\slick\Plugin\Field\FieldFormatter\SlickParagraphsFormatter
 */
abstract class BlazyEntityReferenceBase extends BlazyEntityBase {

  /**
   * The link options.
   *
   * @var object
   */
  protected $linkOptions = [];

  /**
   * The string options.
   *
   * @var object
   */
  protected $stringOptions = [];

  /**
   * The text options.
   *
   * @var object
   */
  protected $textOptions = [];

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::extendedSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettings() {
    return ['blazy' => TRUE, 'ratio' => 'fluid'] + parent::buildSettings();
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
  public function buildElement(array &$build, $entity, $delta = 0) {
    $settings = &$build['settings'];
    $item_id = $settings['item_id'] = empty($settings['item_id']) ? 'box' : $settings['item_id'];

    if (!empty($settings['vanilla'])) {
      return parent::buildElement($build, $entity);
    }

    $element = ['settings' => $settings];

    // Built early before stage to allow custom highres video thumbnail later.
    // Implementor must import Drupal\blazy\Dejavu\BlazyVideoTrait.
    // Build the main stage.
    if (!empty($settings['image'])) {
      if (method_exists($this, 'getMediaItem')) {
        $file = (object) $entity->wrapper()->{$settings['image']}->value();
        if ($file && $image = $this->getImageItem($file)) {
          $element['item'] = $image['item'];
          $element['settings'] = array_merge($settings, $image['settings']);
        }

        $this->getMediaItem($element, $file);
      }

      // If Image rendered is picked, render image as is, a rare case.
      if (!empty($settings['media_switch']) && $settings['media_switch'] == 'rendered') {
        $content = $this->blazyEntity()->getFieldRenderable($entity, $settings['image'], $settings, TRUE);
      }
    }

    // Optional image with responsive image, lazyLoad, and lightbox supports.
    $element[$item_id] = isset($content) ? $content : $this->formatter()->getBlazy($element);

    // Captions if so configured.
    $this->getCaption($element, $entity);

    // Layouts can be builtin, or field, if so configured.
    if (!empty($settings['layout']) && isset($entity->{$settings['layout']})) {
      if (strpos($settings['layout'], 'field_') !== FALSE) {
        $settings['layout'] = $this->blazyEntity()->getFieldString($entity, $settings['layout'], $settings);
      }
      $element['settings']['layout'] = $settings['layout'];
    }

    // Classes, if so configured.
    if (!empty($settings['class']) && isset($entity->{$settings['class']})) {
      $element['settings']['class'] = $this->blazyEntity()->getFieldString($entity, $settings['class'], $settings);
    }

    // Build the main item.
    $build['items'][$delta] = $element;

    // Build the thumbnail item.
    if (!empty($settings['nav'])) {
      // Thumbnail usages: asNavFor pagers, dot, arrows, photobox thumbnails.
      $element[$item_id] = empty($settings['thumbnail_style']) ? [] : $this->formatter()->getThumbnail($element['settings'], $element['item']);
      $element['caption'] = empty($settings['thumbnail_caption']) ? [] : $this->blazyEntity()->getFieldRenderable($entity, $settings['thumbnail_caption'], $settings);

      $build['thumb']['items'][$delta] = $element;
    }
  }

  /**
   * Builds slide captions with possible multi-value fields.
   */
  public function getCaption(array &$element, $entity) {
    $settings = $element['settings'];

    // Title can be plain text, or link field.
    if (!empty($settings['title']) && isset($entity->{$settings['title']})) {
      if ($title = $this->blazyEntity()->getFieldTextOrLink($entity, $settings['title'], $settings)) {
        $element['caption']['title'] = $title;
      }
    }

    // Other caption fields, if so configured.
    if (!empty($settings['caption'])) {
      $caption_items = $weights = [];

      foreach ($settings['caption'] as $i => $field_caption) {
        if (!isset($entity->{$field_caption})) {
          continue;
        }
        if ($caption = $this->blazyEntity()->getFieldRenderable($entity, $field_caption, $settings)) {
          if (isset($caption['#weight'])) {
            $weights[] = $caption['#weight'];
          }
          $caption_items[$i] = $caption;
        }
      }

      if ($caption_items) {
        if ($weights) {
          array_multisort($weights, SORT_ASC, $caption_items);
        }

        $element['caption']['data'] = $caption_items;
      }
    }

    // Link, if so configured.
    if (!empty($settings['link']) && isset($entity->{$settings['link']})) {
      $element['caption']['link'] = $this->blazyEntity()->getFieldRenderable($entity, $settings['link'], $settings);
    }

    if (!empty($settings['overlay']) && isset($entity->{$settings['overlay']})) {
      $element['caption']['overlay'] = $this->blazyEntity()->getFieldRenderable($entity, $settings['overlay'], $settings);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, &$form_state, $definition) {
    $element = parent::settingsForm($form, $form_state, $definition);

    if (isset($element['layout'])) {
      $layout_description = $element['layout']['#description'];
      $element['layout']['#description'] = t('Create a dedicated List (text - max number 1) field related to the caption placement to have unique layout per slide with the following supported keys: top, right, bottom, left, center, center-top, etc. Be sure its formatter is Key.') . ' ' . $layout_description;
    }

    if (isset($element['media_switch'])) {
      $element['media_switch']['#options']['rendered'] = t('Image rendered by its formatter');
      $element['media_switch']['#description'] .= ' ' . t('Be sure the enabled fields here are not hidden/disabled at its view mode.');
    }

    if (isset($element['caption'])) {
      $element['caption']['#description'] = t('Check fields to be treated as captions, even if not caption texts.');
    }

    if (isset($element['image']['#description'])) {
      $element['image']['#description'] .= ' ' . t('For video, this allows separate highres image, be sure the same field used for Image to have a mix of videos and images. Leave empty to fallback to the video provider thumbnails. The formatter/renderer is managed by <strong>@namespace</strong> formatter. Meaning original formatter ignored. If you want original formatters, check <strong>Vanilla</strong> option. Alternatively choose <strong>Media switcher &gt; Image rendered </strong>, other image-related settings here will be ignored. <strong>Supported fields</strong>: Image, Video Embed Field.', ['@namespace' => $this->getPluginId()]);
    }

    if (isset($element['overlay']['#description'])) {
      $element['overlay']['#description'] .= ' ' . t('The formatter/renderer is managed by the child formatter. <strong>Supported fields</strong>: Image, File/ Media Entity.');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    $bundles = $this->fieldDefinition['bundles'];
    $links = ['text', 'link_field', 'url'];
    $strings = ['text', 'list_text'];
    $texts = ['text', 'text_long', 'text_with_summary', 'link_field', 'url'];
    $this->linkOptions = $this->admin()->getFieldOptions($this->fieldInstance, $links, $this->targetType, $bundles);
    $this->stringOptions = $this->admin()->getFieldOptions($this->fieldInstance, $strings, $this->targetType, $bundles);
    $this->textOptions = $this->admin()->getFieldOptions($this->fieldInstance, $texts, $this->targetType, $bundles);

    return [
      'multimedia'    => TRUE,
      'vanilla'       => TRUE,
      'captions'      => $this->admin()->getFieldOptions($this->fieldInstance, [], $this->targetType, $bundles),
      'classes'       => $this->stringOptions,
      'images'        => $this->admin()->getFieldOptions($this->fieldInstance, ['image'], $this->targetType, $bundles),
      'layouts'       => $this->stringOptions,
      'links'         => $this->linkOptions,
      'titles'        => $this->textOptions,
      'use_view_mode' => TRUE,
    ] + parent::getScopedFormElements();
  }

}
