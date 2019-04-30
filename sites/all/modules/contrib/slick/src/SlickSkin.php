<?php

namespace Drupal\slick;

/**
 * Implements SlickSkinInterface.
 */
class SlickSkin implements SlickSkinInterface {

  /**
   * {@inheritdoc}
   */
  public function skins() {
    $path = drupal_get_path('module', 'slick');
    $skins = [
      'default' => [
        'name' => 'Default',
        'css' => [
          $path . '/css/theme/slick.theme--default.css' => [],
        ],
      ],
      'asnavfor' => [
        'name' => 'Thumbnail: asNavFor',
        'css' => [
          $path . '/css/theme/slick.theme--asnavfor.css' => [],
        ],
        'description' => t('Affected thumbnail navigation only.'),
      ],
      'classic' => [
        'name' => 'Classic',
        'description' => t('Adds dark background color over white caption, only good for slider (single slide visible), not carousel (multiple slides visible), where small captions are placed over images.'),
        'css' => [
          $path . '/css/theme/slick.theme--classic.css' => [],
        ],
      ],
      'fullscreen' => [
        'name' => 'Full screen',
        'description' => t('Adds full screen display, works best with 1 slidesToShow.'),
        'css' => [
          $path . '/css/theme/slick.theme--full.css' => [],
          $path . '/css/theme/slick.theme--fullscreen.css' => [],
        ],
      ],
      'fullwidth' => [
        'name' => 'Full width',
        'description' => t('Adds .slide__constrained wrapper to hold caption overlay within the max-container.'),
        'css' => [
          $path . '/css/theme/slick.theme--full.css' => [],
          $path . '/css/theme/slick.theme--fullwidth.css' => [],
        ],
      ],
      'grid' => [
        'name' => 'Grid Foundation',
        'description' => t('Use slidesToShow > 1 to have more grid combination, only if you have considerable amount of grids, otherwise 1.'),
        'css' => [
          $path . '/css/theme/slick.theme--grid.css' => [],
        ],
      ],
      'split' => [
        'name' => 'Split',
        'description' => t('Puts image and caption side by side, requires any split layout option.'),
        'css' => [
          $path . '/css/theme/slick.theme--split.css' => [],
        ],
      ],
    ];

    foreach ($skins as $key => $skin) {
      $skins[$key]['group'] = $key == 'asnavfor' ? 'thumbnail' : 'main';
      $skins[$key]['provider'] = 'slick';
    }

    return $skins;
  }

}
