<?php

namespace Drupal\blazy\Dejavu;

/**
 * A Trait common for optional views style plugins.
 */
trait BlazyStyleOptionsTrait {

  /**
   * The Views as options.
   *
   * @var array
   */
  protected $viewsOptions;

  /**
   * Returns available fields for select options.
   */
  public function getDefinedFieldOptions($defined_options = []) {
    $fields = $this->view->display_handler->get_handlers('field');

    $options = [];
    $classes = [
      'list_text',
      'entityreference',
      'taxonomy_term_reference',
      'text',
    ];
    foreach ($fields as $field => $handler) {
      if (isset($handler->field_info)) {
        $type = $handler->field_info['type'];

        switch ($type) {
          case 'file':
          case 'image':
          case 'youtube':
          case 'video_embed_field':
            $options['images'][$field] = $handler->ui_name();
            $options['overlays'][$field] = $handler->ui_name();
            $options['thumbnails'][$field] = $handler->ui_name();
            break;

          case 'list_text':
            $options['layouts'][$field] = $handler->ui_name();
            break;

          case 'entityreference':
          case 'text':
          case 'text_long':
          case 'text_with_summary':
          case 'link_field':
            $options['links'][$field] = $handler->ui_name();
            $options['titles'][$field] = $handler->ui_name();
            if ($type != 'link_field') {
              $options['thumb_captions'][$field] = $handler->ui_name();
            }
            break;
        }
        if (in_array($type, $classes)) {
          $options['classes'][$field] = $handler->ui_name();
        }
      }

      // Content: title is not really a field, unless title.module installed.
      if ($handler->field == 'title') {
        $options['classes'][$field] = $handler->ui_name();
        $options['titles'][$field] = $handler->ui_name();
        $options['thumb_captions'][$field] = $handler->ui_name();
      }

      if ($handler->field == 'nothing') {
        $options['classes'][$field] = $handler->ui_name();
      }

      if (in_array($handler->field, ['nid', 'nothing', 'view_node'])) {
        $options['links'][$field] = $handler->ui_name();
        $options['titles'][$field] = $handler->ui_name();
      }

      // Caption can be anything to get custom works going.
      $options['captions'][$field] = $handler->ui_name();
    }

    $definition['plugin_id'] = $this->plugin_name;
    $definition['settings'] = $this->options;
    $definition['current_view_mode'] = $this->view->current_display;

    // Provides the requested fields based on available $options.
    foreach ($defined_options as $key) {
      $definition[$key] = isset($options[$key]) ? $options[$key] : [];
    }

    $contexts = [
      'handler' => $this->view->display_handler,
      'view' => $this->view,
    ];

    drupal_alter('blazy_views_field_options', $definition, $contexts);
    return $definition;
  }

  /**
   * Returns the string values for the expected Title, ET label, List, Term.
   */
  public function getFieldString($row, $field_name, $index, $clean = TRUE) {
    $result = '';

    if ($value = $this->get_field($index, $field_name)) {
      $value = is_array($value) ? array_filter($value) : $value;

      if (is_string($value)) {
        // Only respects tags with default CSV, just too much to worry about.
        if (strpos($value, ',') !== FALSE) {
          $tags = array_map('trim', explode(",", $value));
          $rendered_tags = [];
          foreach ($tags as $tag) {
            $rendered_tags[] = $clean ? drupal_clean_css_identifier($tag) : $tag;
          }
          $result = implode(' ', $rendered_tags);
        }
        else {
          $result = $clean ? drupal_clean_css_identifier($value) : $value;
        }
      }
      else {
        // @todo recheck if anything else but value worthwhile.
        $value = isset($value[0]) && !empty($value[0]['value']) ? $value[0]['value'] : '';
        if ($value) {
          $result = $clean ? drupal_clean_css_identifier($value) : $value;
        }
      }
    }

    return empty($result) ? '' : trim(strip_tags($result));
  }

}
