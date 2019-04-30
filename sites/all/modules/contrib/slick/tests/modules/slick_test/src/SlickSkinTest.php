<?php

namespace Drupal\slick_test;

use Drupal\slick\SlickSkinInterface;

/**
 * Implements SlickSkinInterface as registered via hook_slick_skins_info().
 */
class SlickSkinTest implements SlickSkinInterface {

  /**
   * {@inheritdoc}
   */
  public function skins() {
    $path = drupal_get_path('module', 'slick_test');
    $skins = [
      'test' => [
        'name' => 'Test',
        'description' => t('Test slick skins.'),
        'group' => 'main',
        'provider' => 'slick_test',
        'css' => [
          $path . '/css/slick.theme--test.css' => [],
        ],
        'options' => [
          'zoom' => TRUE,
        ],
      ],
    ];

    return $skins;
  }

  /**
   * {@inheritdoc}
   */
  public function arrows() {
    $path = drupal_get_path('module', 'slick_test');
    $skins = [
      'arrows' => [
        'name' => 'Arrows',
        'description' => t('Test slick arrows.'),
        'provider' => 'slick_test',
        'group' => 'arrows',
        'css' => [
          $path . '/css/slick.theme--arrows.css' => [],
        ],
      ],
    ];

    return $skins;
  }

  /**
   * {@inheritdoc}
   */
  public function dots() {
    $path = drupal_get_path('module', 'slick_test');
    $skins = [
      'dots' => [
        'name' => 'Dots',
        'description' => t('Test slick dots.'),
        'provider' => 'slick_test',
        'group' => 'dots',
        'css' => [
          $path . '/css/slick.theme--dots.css' => [],
        ],
      ],
    ];

    return $skins;
  }

}
