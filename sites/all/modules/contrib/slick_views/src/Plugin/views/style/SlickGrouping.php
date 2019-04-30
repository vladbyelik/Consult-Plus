<?php

namespace Drupal\slick_views\Plugin\views\style;

use Drupal\blazy\Blazy;

/**
 * Slick style plugin with grouping support.
 *
 * @ingroup views_style_plugins
 */
class SlickGrouping extends SlickViewsBase {

  /**
   * {@inheritdoc}
   */
  public function option_definition() {
    $options = parent::option_definition();
    foreach (['limit', 'optionset'] as $key) {
      $options['grouping_' . $key] = ['default' => ''];
    }

    return $options;
  }

  /**
   * Overrides parent::buildOptionsForm().
   */
  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $definition = $this->getDefinedFormScopes();

    $states = [
      'visible' => [
        'select[name*="[grouping][0][field]"]' => ['!value' => ''],
      ],
    ];

    if (!isset($form['grouping_limit'])) {
      $form['grouping_limit'] = [
        '#type' => 'textfield',
        '#title' => t('Grouping limit'),
        '#default_value' => $this->options['grouping_limit'],
        '#description' => t('Limit the amount of rows per group. Leave it empty, or 0, for no limit. Applicable only to the first level. Be sure having enough rows.'),
        '#enforced' => TRUE,
        '#states' => $states,
      ];
    }

    if (!isset($form['grouping_optionset'])) {
      $form['grouping_optionset'] = [
        '#type' => 'select',
        '#title' => t('Grouping optionset'),
        '#options' => $this->admin()->getOptionsetsByGroupOptions('main'),
        '#default_value' => $this->options['grouping_optionset'],
        '#description' => t('If provided, the grouping header will be treated as Slick tabs and acts like simple filters. Else regular stacking slicks. Requires: Optionset thumbnail, Vanilla unchecked, and Randomize option disabled for all optionsets, else impressing broken grouping due to reordered slides. Combine with grids to have a complete insanity.'),
        '#enforced' => TRUE,
        '#states' => $states,
      ];
    }

    $groupings = $this->options['grouping'] ?: [];

    for ($i = 0; $i <= count($groupings); $i++) {
      foreach (['rendered', 'rendered_strip'] as $key) {
        $form['grouping'][$i][$key]['#field_suffix'] = '&nbsp;';
        $form['grouping'][$i][$key]['#title_display'] = 'before';
      }
    }

    $this->buildSettingsForm($form, $definition);

    if (isset($form['optionset_thumbnail'])) {
      $form['optionset_thumbnail']['#description'] .= ' ' . t('This will be used (taken over) for grouping tabs if Grouping optionset is provided. Including all thumbnail-related options: Skin tthumbnail, Thumbnail position.');
    }
  }

  /**
   * Overrides StylePluginBase::render().
   */
  public function render() {
    $sets     = parent::render();
    $settings = $this->options;
    $grouping = empty($settings['grouping']) ? [] : array_filter($settings['grouping']);
    $tabs     = !empty($settings['grouping_optionset']) && !empty($settings['optionset_thumbnail']);
    $tags     = ['span', 'a', 'em', 'strong', 'i', 'button'];

    if (!empty($grouping) && $tabs) {
      foreach ($sets as $set) {
        $options = [];
        $options['nav'] = TRUE;
        $options['skin'] = '';
        $options['skin_thumbnail'] = $settings['skin_thumbnail'];
        $options['thumbnail_position'] = $settings['thumbnail_position'];
        $options['optionset'] = $settings['grouping_optionset'];
        $options['optionset_thumbnail'] = $settings['optionset_thumbnail'];

        $slide = [
          'settings' => $options,
          'slide' => $set,
        ];

        $thumb['slide']['#markup'] = empty($set['#title']) ? '' : strip_tags($set['#title'], '<span><a><em><strong><i><button>');
        $thumb['slide']['#allowed_tags'] = $tags;

        $build['items'][] = $slide;
        $build['thumb']['items'][] = $thumb;
        unset($slide, $thumb);
      }

      $build['settings'] = $options;
      $sets = $this->manager()->build($build);
    }

    return $sets;
  }

  /**
   * Overrides StylePluginBase::renderRowGroup().
   */
  protected function render_row_group(array $rows = [], $level = 0) {
    $view      = $this->view;
    $settings  = $this->options;
    $view_name = $view->name;
    $view_mode = $view->current_display;
    $grouping  = empty($settings['grouping']) ? [] : array_filter($settings['grouping']);
    $id        = $grouping ? "{$view_name}-{$view_mode}-{$level}" : "{$view_name}-{$view_mode}";
    $id        = Blazy::getHtmlId('slick-views-' . $id, $settings['id']);
    $settings  = $this->buildSettings();

    // Prepare needed settings to work with.
    $settings['id'] = $id;
    if (empty($grouping) && empty($settings['grouping_optionset'])) {
      $settings['nav'] = !$settings['vanilla'] && $settings['optionset_thumbnail'] && isset($view->result[1]);
    }

    $build = $this->buildElements($settings, $rows);

    // Extracts Blazy formatter settings if available.
    if (empty($settings['vanilla']) && isset($build['items'][0])) {
      $this->blazyManager()->isBlazy($settings, $build['items'][0]);
    }

    // Supports Blazy multi-breakpoint images if using Blazy formatter.
    $settings['first_image'] = isset($rows[0]) ? $this->getFirstImage($rows[0]) : [];

    $build['settings'] = $settings;

    $elements = $this->manager()->build($build);

    // Attach library if there is no results and ajax is active,
    // otherwise library will not be attached on ajax callback.
    // Note the empty space, a trick to solve: Undefined variable: empty...
    // No markup is output, yet the library is still attached on the page.
    // When this is reached, the $elements is an empty array.
    if (empty($this->view->result) && $this->view->use_ajax) {
      $elements['#markup'] = ' ';
      $elements['#attached'] = $this->manager()->attach($settings);
    }

    return $elements;
  }

  /**
   * Overrides StylePluginBase::renderGroupingSets().
   *
   * @see https://www.drupal.org/node/2639300
   */
  public function render_grouping_sets($sets, $level = 0) {
    $output = [];
    $grouping = empty($this->options['grouping']) ? [] : array_filter($this->options['grouping']);

    foreach ($sets as $set) {
      $level = isset($set['level']) ? $set['level'] : 0;
      $row = reset($set['rows']);

      // Render as a grouping set.
      if (is_array($row) && isset($row['group'])) {
        $single_output = [
          '#theme' => views_theme_functions('views_view_grouping', $this->view, $this->display),
          '#view' => $this->view,
          '#grouping' => $grouping[$level],
          '#rows' => $set['rows'],
          '#title' => $set['group'],
        ];
      }
      // Render as a record set.
      else {
        $slick = $this->render_row_group($set['rows'], $level);

        // Views leaves the first grouping header to the style plugin.
        if (!empty($grouping) && $level == 0) {
          if (empty($this->options['grouping_optionset'])) {
            $content[0] = $slick;
            $content[0]['#prefix'] = '<h2 class="view-grouping-header">' . $set['group'] . '</h2>';

            $single_output[0] = $content;
            $single_output[0]['#prefix'] = '<div class="view-grouping">';
            $single_output[0]['#suffix'] = '</div>';
          }
          else {
            $single_output = $slick;
          }
        }
        else {
          $single_output = $slick;
        }
      }

      $single_output['#grouping_level'] = $level;
      $single_output['#title'] = $set['group'];

      $output[] = $single_output;
    }

    return drupal_render_children($output);
  }

  /**
   * Overrides StylePluginBase::renderGrouping().
   */
  public function render_grouping($records, $groupings = [], $group_rendered = NULL) {
    $sets = parent::render_grouping($records, $groupings, $group_rendered);
    $grouping = empty($groupings) ? [] : array_filter($groupings);

    // Only add limits for the first top level grouping to avoid recursiveness.
    if (!empty($grouping) && !empty($this->options['grouping_limit'])) {
      $new_sets = array_values($sets);
      $sets = [];

      foreach ($new_sets as $set) {
        $set['rows'] = array_slice($set['rows'], 0, $this->options['grouping_limit'], TRUE);
        $sets[] = $set;
      }
    }

    return $sets;
  }

}
