<?php

/**
 * @file
 * Contains core functions for the Views module support.
 */

/**
 * Implements hook_views_plugins().
 */
function slick_views_views_plugins() {
  return [
    'style' => [
      'slick' => [
        'title' => t('Slick Carousel'),
        'help' => t('Display the results in a Slick carousel.'),
        'handler' => 'Drupal\slick_views\Plugin\views\style\SlickViews',
        'file' => 'SlickViews.php',
        'path' => drupal_get_path('module', 'slick_views') . '/src/Plugin/views/style',
        'register theme' => FALSE,
        'theme' => 'slick_wrapper',
        'theme file' => 'slick.theme.inc',
        'theme path' => drupal_get_path('module', 'slick') . '/templates',
        'uses grouping' => FALSE,
        'uses row plugin' => TRUE,
        'uses options' => TRUE,
        'type' => 'normal',
        'even empty' => FALSE,
      ],
      'slick_grouping' => [
        'title' => t('Slick Grouping'),
        'help' => t('Display the results in a grouping Slick carousel.'),
        'handler' => 'Drupal\slick_views\Plugin\views\style\SlickGrouping',
        'file' => 'SlickGrouping.php',
        'path' => drupal_get_path('module', 'slick_views') . '/src/Plugin/views/style',
        'register theme' => FALSE,
        'theme' => 'slick_wrapper',
        'theme file' => 'slick.theme.inc',
        'theme path' => drupal_get_path('module', 'slick') . '/templates',
        'uses grouping' => TRUE,
        'uses row plugin' => TRUE,
        'uses options' => TRUE,
        'type' => 'normal',
        'even empty' => FALSE,
      ],
    ],
  ];
}
