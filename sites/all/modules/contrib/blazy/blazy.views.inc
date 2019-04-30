<?php

/**
 * @file
 * Provides views data for blazy.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function blazy_views_data_alter(&$data) {
  $data['file_managed']['blazy_file'] = [
    'title' => 'Blazy',
    'help' => t('Displays a File entity using Blazy, if applicable. Suitable for huge galleries.'),
    'field' => [
      'id' => 'blazy_file',
      'click sortable' => FALSE,
      'handler' => 'Drupal\blazy\Plugin\views\field\BlazyViewsFieldFile',
    ],
  ];
}

/**
 * Implements hook_views_plugins().
 */
function blazy_views_plugins() {
  return [
    'style' => [
      'blazy' => [
        'title' => t('Blazy Grid'),
        'help' => t('Display the results in a Blazy Grid.'),
        'handler' => 'Drupal\blazy\Plugin\views\style\BlazyViews',
        'file' => 'BlazyViews.php',
        'path' => drupal_get_path('module', 'blazy') . '/src/Plugin/views/style',
        'register theme' => FALSE,
        'uses grouping' => FALSE,
        'uses row plugin' => TRUE,
        'uses options' => TRUE,
        'type' => 'normal',
        'even empty' => FALSE,
      ],
    ],
  ];
}
