<?php

namespace Drupal\blazy;

/**
 * Provides common entity utilities to work with entity fields.
 */
class BlazyEntity {

  /**
   * The blazy-related formatter service.
   *
   * @var object
   */
  protected $formatter;

  /**
   * Constructs a BlazyFormatter instance.
   */
  public function __construct(BlazyManagerInterface $formatter) {
    $this->formatter = $formatter;
  }

  /**
   * Build image/video preview either using theme_blazy(), or view builder.
   *
   * This is alternative to Drupal\blazy\BlazyFormatter used outside
   * field formatters, such as Views field, or Entity Browser displays, etc.
   *
   * @param array $data
   *   An array of data containing settings, and image item.
   * @param object $entity
   *   The media entity, else file entity to be associated to media if any.
   * @param string $fallback
   *   The fallback string to display such as file name or entity label.
   *
   * @return array
   *   The renderable array of theme_blazy(), or view builder, else empty array.
   */
  public function build(array $data, $entity, $fallback = '') {
    $build = [];

    // Bail out if empty.
    if (!$entity) {
      return [];
    }

    // Supports Media.
    // @todo if (method_exists($this, 'getMediaItem')) {
    // @todo $this->getMediaItem($data, $entity);
    // @todo }
    $settings = &$data['settings'];
    if (!empty($data['item'])) {
      if (!empty($settings['media_switch'])) {
        $is_lightbox = $this->formatter->getLightboxes() && in_array($settings['media_switch'], $this->formatter->getLightboxes());
        $settings['lightbox'] = $is_lightbox ? $settings['media_switch'] : FALSE;
      }
      if (empty($settings['uri'])) {
        $settings['uri'] = $data['item']->uri;
      }

      $build = $this->formatter->getBlazy($data);

      // Provides a shortcut to get URI.
      $build['#uri'] = empty($settings['uri']) ? '' : $settings['uri'];

      // Allows top level elements to load Blazy once rather than per field.
      // This is still here for non-supported Views style plugins, etc.
      if (empty($settings['_detached'])) {
        $build['#attached'] = $this->formatter->attach($settings);
      }
    }
    else {
      $build = $this->entityView($settings['target_type'], $entity, $settings, $fallback);
    }

    return $build;
  }

  /**
   * Returns the entity view, if available.
   *
   * @param string $entity_type
   *   The entity type being rendered.
   * @param object $entity
   *   The entity being rendered.
   * @param array $settings
   *   The settings containing view_mode, etc to reduce params for the known.
   * @param string $fallback
   *   The fallback content when all fails, probably just entity label.
   *
   * @return array
   *   The renderable array of the view builder, or empty if not applicable.
   */
  public function entityView($entity_type, $entity, array $settings, $fallback = '') {
    // Get the correct language.
    global $language;

    $view_hook = $entity_type . '_view';
    $view_mode = empty($settings['view_mode']) ? 'default' : $settings['view_mode'];
    $langcode = empty($settings['langcode']) ? $language->language : $settings['langcode'];

    // Untranslatable fields are rendered with no language code, fall back
    // to the content language in that case.
    $langcode = $langcode !== LANGUAGE_NONE ? $langcode : NULL;

    // If module implements own {entity_type}_view.
    if (function_exists($view_hook)) {
      if ($entity_type == 'file') {
        // Add some references to the referencing entity.
        // @see https://www.drupal.org/node/2333107
        $entity->referencing_entity_type = $settings['entity_type_id'];
        $entity->referencing_field = $settings['field_name'];
      }
      return $view_hook($entity, $view_mode, $langcode);
    }
    // If entity is installed.
    elseif (function_exists('entity_view')) {
      return entity_view($entity_type, [$entity], $view_mode, $langcode);
    }

    return $fallback ? ['#markup' => $fallback] : [];
  }

  /**
   * Returns the string value of the fields: link, or text.
   *
   * Watch out the Entity output vs. file entity via field_get_items().
   */
  public function getFieldValue($entity, $field_name, $settings) {
    if ($entity instanceof \Entity) {
      // We have 3 possible outputs for link or text fields here:
      // 1. string, 2. $array['value'], 3. $array[0]['url']
      // Note! We have no field_get_items()-like output: $array[0]['value'].
      // If the entity has translation, fetch the translated value instead.
      $translated = $entity->wrapper()->language($settings['langcode'])->{$field_name}->value();
      return $translated ?: $entity->wrapper()->{$field_name}->value();
    }

    // File entity is not based on \Entity, and here comes the complication.
    return field_get_items($entity->targetType, $entity, $field_name);
  }

  /**
   * Returns the string value of the fields: link, or text.
   */
  public function getFieldString($entity, $field_name, $settings, $clean = TRUE) {
    if ($value = $this->getFieldValue($entity, $field_name, $settings)) {
      // If Entity, use no index, or direct string value, file entity has.
      // Cannot use safe_value as it has nothing todo with the given text value.
      $string = isset($value['value']) ? $value['value'] : $value;
      $string = isset($value[0]['value']) ? $value[0]['value'] : $string;

      if ($string && is_string($string)) {
        $string = $clean ? strip_tags($string, '<a><strong><em><span><small>') : filter_xss($string, BlazyDefault::TAGS);
        return trim($string);
      }
    }
    return '';
  }

  /**
   * Returns the text or link value of the fields: link, or text.
   */
  public function getFieldTextOrLink($entity, $field_name, $settings) {
    if ($text = $this->getFieldValue($entity, $field_name, $settings)) {

      // The $text may be just a plain string when using Entity.
      if (is_array($text)) {

        // If a link fetch the themeable output since the array is useless.
        if (isset($text[0]['url']) && !empty($text[0]['title'])) {
          $text = $this->getFieldRenderable($entity, $field_name, $settings, TRUE);
        }
        // If a text, make it the string value.
        elseif ($output = $this->getFieldString($entity, $field_name, $settings, FALSE)) {
          $text = $output;
        }
      }

      // Prevents HTML-filter-enabled text from having bad markups
      // (h2 > p), save for few reasonable tags acceptable within H2 tag.
      return is_string($text) ? ['#markup' => strip_tags($text, '<a><strong><em><span><small>')] : $text;
    }
    return [];
  }

  /**
   * Returns the formatted renderable array of the field.
   */
  public function getFieldRenderable($entity, $field_name, $settings, $multiple = TRUE) {
    if ($field = field_get_items($entity->targetType, $entity, $field_name)) {
      // If $multiple, use theme_field(). To fetch only the first item, add 0
      // which in turn similar to field_view_value() aka a single output.
      $fields = field_view_field($entity->targetType, $entity, $field_name, $settings['view_mode']);
      $weight = isset($fields['#weight']) ? $fields['#weight'] : 0;

      // Intentionally clean markups as this is not meant for vanilla.
      // Use text format to add extra markups for texts instead.
      if ($multiple) {
        $items = [];
        $entity->_field_view_prepared = FALSE;
        foreach (element_children($fields) as $key) {
          if (!empty($field[$key]['value']) && isset($field[$key]['format'])) {
            $items[] = [
              '#markup' => $this->getFieldString($entity, $field_name, $settings, FALSE),
            ];
          }
          else {
            $items[] = field_view_value($entity->targetType, $entity, $field_name, $field[$key], $settings['view_mode']);
          }
        }
        $items['#weight'] = $weight;
        $entity->_field_view_prepared = TRUE;
        return $items;
      }
      return field_view_value($entity->targetType, $entity, $field_name, $field[0], $settings['view_mode']);
    }
    return [];
  }

}
