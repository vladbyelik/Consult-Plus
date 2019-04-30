<?php

namespace Drupal\slick_ui\Controller;

use Drupal\blazy\BlazyGrid;

/**
 * Provides a listing of Slick optionsets.
 */
trait SlickListBuilder {

  /**
   * {@inheritdoc}
   */
  public function list_table_header() {
    $headers = parent::list_table_header();
    $breakpoints_header[] = ['data' => t('Breakpoint'), 'class' => ['ctools-export-ui-breakpoints']];
    array_splice($headers, 2, 0, $breakpoints_header);

    $skin_header[] = ['data' => t('Skin'), 'class' => ['ctools-export-ui-skin']];
    array_splice($headers, 3, 0, $skin_header);

    $collection_header[] = ['data' => t('Collection'), 'class' => ['ctools-export-ui-collection']];
    array_splice($headers, 4, 0, $collection_header);

    return $headers;
  }

  /**
   * {@inheritdoc}
   */
  public function list_build_row($entity, &$form_state, $operations) {
    parent::list_build_row($entity, $form_state, $operations);
    $skins = $this->manager->getSkins()['skins'];
    $name = $entity->name;
    $breakpoints = $entity->breakpoints;
    $skin = $entity->skin;
    $skin_name = $skin ? check_plain($skin) : t('None');

    if ($skin) {
      $description = isset($skins[$skin]['description']) && $skins[$skin]['description'] ? filter_xss($skins[$skin]['description']) : '';
      if ($description) {
        $skin_name .= '<br /><em>' . $description . '</em>';
      }
    }

    $breakpoints_row[] = [
      'data' => $breakpoints,
      'class' => ['ctools-export-ui-breakpoints'],
    ];
    array_splice($this->rows[$name]['data'], 2, 0, $breakpoints_row);

    $skin_row[] = [
      'data' => $skin_name,
      'class' => ['ctools-export-ui-skin'],
      'style' => "white-space: normal; word-wrap: break-word; max-width: 320px;",
    ];
    array_splice($this->rows[$name]['data'], 3, 0, $skin_row);

    $collection_row[] = [
      'data' => $entity->collection ?: t('- All -'),
      'class' => ['ctools-export-ui-collection'],
    ];
    array_splice($this->rows[$name]['data'], 4, 0, $collection_row);
  }

  /**
   * Overrides parent::list_form.
   */
  public function list_form(&$form, &$form_state) {
    parent::list_form($form, $form_state);

    $form['slick description']['#prefix'] = '<div class="ctools-export-ui-row ctools-export-ui-slick-description clearfix">';
    $form['slick description']['#markup'] = t("<p>Manage the Slick optionsets. Optionsets are Config Entities.</p><p>By default, when this module is enabled, a single optionset is created from configuration. Install Slick example module to speed up by cloning them. Use the Operations column to edit, clone and delete optionsets.<br /><strong class='error'>Important!</strong> Avoid overriding Default optionset as it is meant for Default -- checking and cleaning. Use Clone, or Add, instead. If you did, please clone it and revert, otherwise messes are yours.<br />Slick doesn't need Slick UI to run. It is always safe to uninstall (not only disable) Slick UI once done with optionsets, either stored in codes, or database.</p>");
    $form['slick description']['#suffix'] = '</div>';
  }

  /**
   * Adds some descriptive text to the slick optionsets list.
   *
   * @return array
   *   Renderable array.
   */
  public function list_render(&$form_state) {
    $build['parent'] = ['#markup' => parent::list_render($form_state)];

    $availaible_skins = [];
    $skins = $this->manager->getSkins()['skins'];

    foreach ($skins as $key => $skin) {
      $name = isset($skin['name']) ? $skin['name'] : $key;
      $group = isset($skin['group']) ? check_plain($skin['group']) : 'None';
      $provider = isset($skin['provider']) ? check_plain($skin['provider']) : 'Lory';
      $description = isset($skin['description']) ? check_plain($skin['description']) : t('No description');

      $markup = '<h3>' . t('@skin <br><small>Id: @id | Group: @group | Provider: @provider</small>', [
        '@skin' => $name,
        '@id' => $key,
        '@group' => $group,
        '@provider' => $provider,
      ]) . '</h3>';

      $markup .= '<p><em>&mdash; ' . $description . '</em></p>';

      $availaible_skins[$key] = [
        '#markup' => '<div class="messages status">' . $markup . '</div>',
      ];
    }

    ksort($availaible_skins);
    $availaible_skins = ['default' => $availaible_skins['default']] + $availaible_skins;

    $settings['grid'] = 3;
    $settings['grid_medium'] = 2;
    $settings['blazy'] = FALSE;
    $settings['style'] = 'column';

    $header = '<br><hr><h2>' . t('Available skins') . '</h2>';
    $header .= '<p>' . t('Some skin works best with a specific Optionset, and vice versa. Use matching names if found. Else happy adventure!') . '</p>';
    $build['skins_header']['#markup'] = $header;
    $build['skins_header']['#weight'] = 20;

    $build['skins'] = BlazyGrid::build($availaible_skins, $settings);
    $build['skins']['#weight'] = 21;
    $build['skins']['#attached'] = $this->manager->attach($settings);
    $build['skins']['#attached']['library'][] = ['blazy', 'admin'];

    return drupal_render_children($build);
  }

}
