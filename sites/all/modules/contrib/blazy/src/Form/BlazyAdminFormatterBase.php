<?php

namespace Drupal\blazy\Form;

/**
 * A base for field formatter admin to have re-usable methods in one place.
 */
abstract class BlazyAdminFormatterBase extends BlazyAdminBase {

  /**
   * Returns re-usable image formatter form elements.
   */
  public function imageStyleForm(array &$form, $definition = []) {
    $is_picture = function_exists('picture_get_mapping_options');

    if (empty($definition['no_image_style'])) {
      $form['image_style'] = $this->baseForm($definition)['image_style'];
    }

    if (!empty($definition['thumbnail_style'])) {
      $form['thumbnail_style'] = $this->baseForm($definition)['thumbnail_style'];
    }

    if (!empty($definition['responsive_image']) && $this->manager()->config('responsive_image', FALSE, 'blazy.settings')) {
      $responsive_options = $this->getResponsiveImageOptions();
      $description = t('Not compatible with below breakpoints, aspect ratio, yet. However the image can be replaced by Picture if <strong>Picture</strong> option enabled at Blazy UI. Lazyload is taken care of by Picture, not Blazy. Leave empty to disable.');
      if ($is_picture && empty($responsive_options)) {
        $description = t('<a href="@url" target="_blank">No picture mappings</a> defined.', ['@url' => url('admin/config/media/picture')]);
      }

      $form['responsive_image_style'] = [
        '#type'        => 'select',
        '#title'       => t('Picture'),
        '#options'     => $responsive_options,
        '#description' => $description,
        '#access'      => $is_picture,
        '#weight'      => -100,
      ];

      if (!empty($definition['background'])) {
        $form['background']['#states'] = $this->getState(static::STATE_RESPONSIVE_IMAGE_STYLE_DISABLED, $definition);
      }
    }

    if (!empty($definition['thumbnail_effect'])) {
      $form['thumbnail_effect'] = [
        '#type'    => 'select',
        '#title'   => t('Thumbnail effect'),
        '#options' => isset($definition['thumbnail_effect']) ? $definition['thumbnail_effect'] : [],
        '#weight'  => -100,
      ];
    }
  }

  /**
   * Return the field formatter settings summary.
   */
  public function getSettingsSummary($definition = []) {
    $summary = [];

    if (empty($definition['settings'])) {
      return $summary;
    }

    $this->getExcludedSettingsSummary($definition);

    $enforced = [
      'optionset',
      'cache',
      'skin',
      'view_mode',
      'override',
      'overridables',
      'style',
      'vanilla',
    ];

    $enforced    = isset($definition['enforced']) ? $definition['enforced'] : $enforced;
    $settings    = array_filter($definition['settings']);
    $breakpoints = isset($settings['breakpoints']) && is_array($settings['breakpoints']) ? array_filter($settings['breakpoints']) : [];

    foreach ($definition['settings'] as $key => $setting) {
      $title   = ucfirst(str_replace('_', ' ', $key));
      $vanilla = !empty($settings['vanilla']);

      if ($key == 'breakpoints') {
        $widths = [];
        if ($breakpoints) {
          foreach ($breakpoints as $breakpoint) {
            if (!empty($breakpoint['width'])) {
              $widths[] = $breakpoint['width'];
            }
          }
        }

        $title   = 'Breakpoints';
        $setting = $widths ? implode(', ', $widths) : 'none';
      }
      else {
        if ($vanilla && !in_array($key, $enforced)) {
          continue;
        }

        if ($key == 'override' && empty($setting)) {
          unset($settings['overridables']);
        }

        if (is_bool($setting) && $setting) {
          $setting = 'yes';
        }
        elseif (is_array($setting)) {
          $setting = array_filter($setting);
          if (!empty($setting)) {
            $setting = implode(', ', $setting);
          }
        }

        if ($key == 'cache') {
          $setting = $this->getCacheOptions()[$setting];
        }
      }

      if (empty($setting)) {
        continue;
      }

      if (isset($settings[$key]) && is_string($setting)) {
        $summary[] = t('@title: <strong>@setting</strong>', [
          '@title'   => $title,
          '@setting' => $setting,
        ]);
      }
    }
    return implode('<br />', $summary);
  }

  /**
   * Exclude the field formatter settings summary as required.
   */
  public function getExcludedSettingsSummary(array &$definition = []) {
    $settings     = &$definition['settings'];
    $excludes     = empty($definition['excludes']) ? [] : $definition['excludes'];
    $plugin_id    = isset($definition['plugin_id']) ? $definition['plugin_id'] : '';
    $blazy        = $plugin_id && strpos($plugin_id, 'blazy') !== FALSE;
    $image_styles = image_style_options(TRUE);

    unset($image_styles['']);

    $excludes['current_view_mode'] = TRUE;

    if ($blazy) {
      $excludes['optionset'] = TRUE;
    }

    if (!empty($settings['responsive_image_style'])) {
      foreach (['ratio', 'breakpoints', 'background', 'sizes'] as $key) {
        $excludes[$key] = TRUE;
      }
    }

    if (empty($settings['grid'])) {
      foreach (['grid', 'grid_medium', 'grid_small', 'visible_items'] as $key) {
        $excludes[$key] = TRUE;
      }
    }

    // Remove exluded settings.
    foreach ($excludes as $key => $value) {
      if (isset($settings[$key])) {
        unset($settings[$key]);
      }
    }

    foreach ($settings as $key => $setting) {
      if ($key == 'style' || $key == 'responsive_image_style' || empty($settings[$key])) {
        continue;
      }
      if (strpos($key, 'style') !== FALSE && isset($image_styles[$settings[$key]])) {
        $settings[$key] = $image_styles[$settings[$key]];
      }
    }
  }

  /**
   * Returns available fields for select options.
   */
  public function getFieldOptions($instance, $field_types, $target_type = 'file', array $bundles = []) {
    $options = [];
    $allowed_bundles = array_filter($this->getBundles($instance, $bundles));

    // Add panelizer support.
    if ($instance['entity_type'] == 'ctools') {
      $bundles = array_filter($bundles);
      foreach ($bundles as $bundle_type => $types) {
        foreach ($types as $type) {
          $instance = field_info_instance($bundle_type, $instance['field_name'], $type);
          $allowed_bundles = $this->getBundles($instance, $bundles);
          $options += $this->getFieldOptionsInternal($allowed_bundles, $target_type, $field_types);
        }
      }
    }
    else {
      $options = $this->getFieldOptionsInternal($allowed_bundles, $target_type, $field_types);
    }

    if (empty($options)) {
      $fields = field_info_fields();
      $bundle_instance = $instance['field_name'];

      foreach ($fields as $name => $field) {
        $infos = field_info_instance($target_type, $name, $bundle_instance);
        if (empty($field_types) && $infos['label']) {
          $options[$name] = $infos['label'];
        }
        else {
          if (in_array($target_type, array_keys($field['bundles']))
              && in_array($bundle_instance, $field['bundles'][$target_type])
              && in_array($field['type'], $field_types)) {
            $options[$name] = $infos['label'];
          }
        }
      }
    }

    return $options;
  }

  /**
   * Returns the expected bundles.
   */
  private function getBundles($instance, array $bundles = []) {
    // Paragraphs.
    $allowed_bundles = isset($instance['settings']['allowed_bundles']) ? $instance['settings']['allowed_bundles'] : [];

    // File entity.
    if (empty($allowed_bundles)) {
      $allowed_bundles = isset($instance['widget']['settings']['allowed_types']) ? $instance['widget']['settings']['allowed_types'] : [];
    }

    return $allowed_bundles;
  }

  /**
   * Helper function to get list of supported field base on field_types.
   */
  private function getFieldOptionsInternal($allowed_bundles, $target_type, $field_types) {
    $options = [];

    foreach ($allowed_bundles as $bundle_name => $bundle) {
      if ($bundle !== -1) {
        $fields = field_info_instances($target_type, $bundle_name);
        foreach ($fields as $name => $field) {
          if (in_array($name, $this->getExcludedFieldOptions())) {
            continue;
          }

          $info = field_info_field($name);
          if (empty($field_types)) {
            $options[$name] = $field['label'];
          }
          else {
            if (in_array($target_type, array_keys($info['bundles']))
              && in_array($info['type'], $field_types)
            ) {
              $options[$name] = $field['label'];
            }
          }
        }
      }
    }

    return $options;
  }

  /**
   * Declutters options from less relevant options.
   */
  public function getExcludedFieldOptions() {
    $excludes = 'field_document_size field_id field_media_in_library field_mime_type field_source field_tweet_author field_tweet_id field_tweet_url field_media_video_embed_field field_instagram_shortcode field_instagram_url';
    $excludes = explode(' ', $excludes);
    $excludes = array_combine($excludes, $excludes);

    drupal_alter('blazy_excluded_field_options', $excludes);
    return $excludes;
  }

  /**
   * Returns Picture for select options.
   */
  public function getResponsiveImageOptions() {
    if (function_exists('picture_get_mapping_options')) {
      return picture_get_mapping_options();
    }
    return [];
  }

}
