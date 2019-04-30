<?php

namespace Drupal\slick\Form;

use Drupal\blazy\Form\BlazyAdminExtended;
use Drupal\slick\SlickManagerInterface;

/**
 * Provides resusable admin functions, or form elements.
 */
class SlickAdmin extends BlazyAdminExtended implements SlickAdminInterface {

  /**
   * Constructs a SlickAdmin object.
   *
   * @param \Drupal\slick\SlickManagerInterface $manager
   *   The slick manager service.
   */
  public function __construct(SlickManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * Returns the main form elements.
   */
  public function buildSettingsForm(array &$form, $definition = []) {
    $definition['caches']           = isset($definition['caches']) ? $definition['caches'] : TRUE;
    $definition['namespace']        = 'slick';
    $definition['optionsets']       = isset($definition['optionsets']) ? $definition['optionsets'] : $this->getOptionsetsByGroupOptions('main');
    $definition['skins']            = isset($definition['skins']) ? $definition['skins'] : $this->getSkinsByGroupOptions('main');
    $definition['responsive_image'] = isset($definition['responsive_image']) ? $definition['responsive_image'] : TRUE;

    $forms = isset($definition['forms']) ? $definition['forms'] : [];
    foreach (['optionsets', 'skins'] as $key) {
      if (isset($definition[$key]['default'])) {
        ksort($definition[$key]);
        $definition[$key] = ['default' => $definition[$key]['default']] + $definition[$key];
      }
    }

    if (empty($definition['no_layouts'])) {
      $definition['layouts'] = isset($definition['layouts']) ? array_merge($this->getLayoutOptions(), $definition['layouts']) : $this->getLayoutOptions();
    }

    $this->openingForm($form, $definition);

    if (!empty($forms['image_style']) && !isset($form['image_style'])) {
      $this->imageStyleForm($form, $definition);
    }

    if (!empty($forms['media_switch']) && !isset($form['media_switch'])) {
      $this->mediaSwitchForm($form, $definition);
    }

    if (!empty($forms['grid']) && !isset($form['grid'])) {
      $this->gridForm($form, $definition);
      if (isset($form['style']['#description'])) {
        $form['style']['#description'] .= ' ' . t('CSS3 Columns is best with adaptiveHeight, non-vertical. Will use regular carousel as default style if left empty. Yet, both CSS3 Columns and Grid Foundation are respected as Grid displays when <strong>Grid large</strong> option is provided.');
      }
    }

    if (!empty($forms['fieldable']) && !isset($form['image'])) {
      $this->fieldableForm($form, $definition);
    }

    if (!empty($definition['breakpoints']) && !$this->manager()->config('unbreakpoints', FALSE, 'blazy.settings')) {
      parent::breakpointsForm($form, $definition);
    }

    $this->closingForm($form, $definition);
  }

  /**
   * Returns the opening form elements.
   */
  public function openingForm(array &$form, &$definition = []) {
    $readme       = module_exists('slick_ui') ? url('admin/help/slick_ui') : '/admin/help/slick_ui';
    $readme_field = module_exists('slick_fields') ? url('admin/help/slick_fields') : '/admin/help/slick_fields';
    $arrows       = $this->getSkinsByGroupOptions('arrows');
    $dots         = $this->getSkinsByGroupOptions('dots');

    if (!isset($form['optionset'])) {
      parent::openingForm($form, $definition);

      $form['optionset']['#title'] = t('Optionset main');

      if (module_exists('slick_ui')) {
        $form['optionset']['#description'] = t('Manage optionsets at <a href="@url" target="_blank">the optionset admin page</a>.', ['@url' => url('admin/config/media/slick')]);
      }
    }

    if (!empty($definition['nav']) || !empty($definition['thumbnails'])) {
      $form['optionset_thumbnail'] = [
        '#type'        => 'select',
        '#title'       => t('Optionset thumbnail'),
        '#options'     => $this->getOptionsetsByGroupOptions('thumbnail'),
        '#description' => t('If provided, asNavFor aka thumbnail navigation applies. Leave empty to not use thumbnail navigation.'),
        '#weight'      => -107,
      ];

      $form['skin_thumbnail'] = [
        '#type'        => 'select',
        '#title'       => t('Skin thumbnail'),
        '#options'     => $this->getSkinsByGroupOptions('thumbnail'),
        '#description' => t('Thumbnail navigation skin. See main <a href="@url" target="_blank">README</a> for details on Skins. Leave empty to not use thumbnail navigation.', ['@url' => $readme]),
        '#weight'      => -106,
      ];
    }

    if (count($arrows) > 0) {
      $form['skin_arrows'] = [
        '#type'        => 'select',
        '#title'       => t('Skin arrows'),
        '#options'     => $arrows ?: [],
        '#enforced'    => TRUE,
        '#description' => t('Implement \Drupal\slick\SlickSkinInterface::arrows() to add your own arrows skins, in the same format as SlickSkinInterface::skins().'),
        '#weight'      => -105,
      ];
    }

    if (count($dots) > 0) {
      $form['skin_dots'] = [
        '#type'        => 'select',
        '#title'       => t('Skin dots'),
        '#options'     => $dots ?: [],
        '#enforced'    => TRUE,
        '#description' => t('Implement \Drupal\slick\SlickSkinInterface::dots() to add your own dots skins, in the same format as SlickSkinInterface::skins().'),
        '#weight'      => -105,
      ];
    }

    if (!empty($definition['thumb_positions'])) {
      $form['thumbnail_position'] = [
        '#type'        => 'select',
        '#title'       => t('Thumbnail position'),
        '#options' => [
          'left'       => t('Left'),
          'right'      => t('Right'),
          'top'        => t('Top'),
          'over-left'  => t('Overlay left'),
          'over-right' => t('Overlay right'),
          'over-top'   => t('Overlay top'),
        ],
        '#description' => t('By default thumbnail is positioned at bottom. Hence to change the position of thumbnail. Only reasonable with 1 visible main stage at a time. Except any TOP, the rest requires Vertical option enabled for Optionset thumbnail, and a custom CSS height to selector <strong>.slick--thumbnail</strong> to avoid overflowing tall thumbnails, or adjust <strong>slidesToShow</strong> to fit the height. Further theming is required as usual. Overlay is absolutely positioned over the stage rather than sharing the space. See skin <strong>X VTabs</strong> for vertical thumbnail sample.'),
        '#states' => [
          'visible' => [
            'select[name*="[optionset_thumbnail]"]' => ['!value' => ''],
          ],
        ],
        '#weight'      => -96,
      ];
    }

    if (!empty($definition['thumb_captions'])) {
      $form['thumbnail_caption'] = [
        '#type'        => 'select',
        '#title'       => t('Thumbnail caption'),
        '#options'     => $definition['thumb_captions'],
        '#description' => t('Thumbnail caption maybe just title/ plain text. If Thumbnail image style is not provided, the thumbnail pagers will be just text like regular tabs.'),
        '#states' => [
          'visible' => [
            'select[name*="[optionset_thumbnail]"]' => ['!value' => ''],
          ],
        ],
        '#weight'      => 2,
      ];
    }

    if (isset($form['skin'])) {
      $form['skin']['#title'] = t('Skin main');
      $form['skin']['#description'] = t('Skins allow various layouts with just CSS. Some options below depend on a skin. However a combination of skins and options may lead to unpredictable layouts, get yourself dirty. E.g.: Skin Split requires any split layout option. Failing to choose the expected layout makes it useless. See <a href="@url" target="_blank">SKINS section</a> at /admin/help/slick_ui for details on Skins. Leave empty to DIY. Or use hook_slick_skins_info() and implement \Drupal\slick\SlickSkinInterface to register ones. Skins are permanently cached. Clear cache if new skins do not appear.', ['@url' => $readme]);
    }

    if (isset($form['layout'])) {
      $form['layout']['#description'] = t('Requires a skin. The builtin layouts affects the entire slides uniformly. Split half requires any skin Split. See Slick Fields <a href="@url" target="_blank">README</a> under "Slide layout" for more info. Leave empty to DIY.', ['@url' => $readme_field]);
    }

    $weight = -99;
    foreach (element_children($form) as $key) {
      if (!isset($form[$key]['#weight'])) {
        $form[$key]['#weight'] = ++$weight;
      }
    }
  }

  /**
   * Returns the image formatter form elements.
   */
  public function mediaSwitchForm(array &$form, $definition = []) {
    parent::mediaSwitchForm($form, $definition);

    if (isset($form['media_switch'])) {
      $form['media_switch']['#description'] = t('Depends on the enabled supported modules, or has known integration with Slick.<ol><li>Link to content: for aggregated small slicks.</li><li>Image to iframe: audio/video is hidden below image until toggled, otherwise iframe is always displayed, and draggable fails. Aspect ratio applies.</li><li>Colorbox.</li><li>Photobox. Be sure to select "Thumbnail style" for the overlay thumbnails.</li><li>Intense: image to fullscreen intense image.</li>');

      if (!empty($definition['multimedia']) && isset($definition['fieldable_form'])) {
        $form['media_switch']['#description'] .= ' ' . t('<li>Image rendered by its formatter: image-related settings here will be ignored: breakpoints, image style, CSS background, aspect ratio, lazyload, etc. Only choose if needing a special image formatter such as Image Link Formatter.</li>');
      }

      $form['media_switch']['#description'] .= ' ' . t('</ol> Try selecting "<strong>- None -</strong>" first before changing if trouble with this complex form states.');
    }

    if (isset($form['ratio']['#description'])) {
      $form['ratio']['#description'] .= ' ' . t('Required if using media to switch between iframe and overlay image, otherwise DIY.');
    }
  }

  /**
   * Returns the image formatter form elements.
   */
  public function imageStyleForm(array &$form, $definition = []) {
    $definition['thumbnail_style'] = isset($definition['thumbnail_style']) ? $definition['thumbnail_style'] : TRUE;
    $definition['ratios'] = isset($definition['ratios']) ? $definition['ratios'] : TRUE;

    $definition['thumbnail_effect'] = [
      'hover' => t('Hoverable'),
      'grid'  => t('Static grid'),
    ];

    if (!isset($form['image_style'])) {
      parent::imageStyleForm($form, $definition);

      $form['image_style']['#description'] = t('The main image style. This will be treated as the fallback image, which is normally smaller, if Breakpoints are provided, and if <strong>Use CSS background</strong> is disabled. Otherwise this is the only image displayed. Ignored by Responsive image option.');
    }

    if (isset($form['thumbnail_style'])) {
      $form['thumbnail_style']['#description'] = t('Usages: <ol><li>If <em>Optionset thumbnail</em> provided, it is for asNavFor thumbnail navigation.</li><li>For <em>Thumbnail effect</em>.</li><li>Photobox thumbnail.</li><li>Custom work via the provided data-thumb attributes: arrows with thumbnails, Photoswipe thumbnail, etc.</li></ol>Leave empty to not use thumbnails.');
    }

    if (isset($form['thumbnail_effect'])) {
      $form['thumbnail_effect']['#description'] = t('Dependent on a Skin, Dots and Thumbnail style options. No asnavfor/ Optionset thumbnail is needed. <ol><li><strong>Hoverable</strong>: Dots pager are kept, and thumbnail will be hidden and only visible on dot mouseover, default to min-width 120px.</li><li><strong>Static grid</strong>: Dots are hidden, and thumbnails are displayed as a static grid acting like dots pager.</li></ol>Alternative to asNavFor aka separate thumbnails as slider.');
    }

    if (isset($form['background'])) {
      $form['background']['#description'] .= ' ' . t('Works best with a single visible slide, skins full width/screen.');
    }
  }

  /**
   * Returns re-usable fieldable formatter form elements.
   */
  public function fieldableForm(array &$form, $definition = []) {
    parent::fieldableForm($form, $definition);

    if (isset($form['thumbnail'])) {
      $form['thumbnail']['#description'] = t("Only needed if <em>Optionset thumbnail</em> is provided. Maybe the same field as the main image, only different instance and image style. Leave empty to not use thumbnail pager.");
    }

    if (isset($form['overlay'])) {
      $form['overlay']['#title'] = t('Overlay media/slicks');
      $form['overlay']['#description'] = t('For audio/video, be sure the display is not image. For nested slicks, use the Slick carousel formatter for this field. Zebra layout is reasonable for overlay and captions.');
    }
  }

  /**
   * Returns re-usable grid elements across Slick field formatter and Views.
   */
  public function gridForm(array &$form, $definition = []) {
    if (!isset($form['grid'])) {
      parent::gridForm($form, $definition);
    }

    $header = t('Group individual item as block grid?<small>An older alternative to core <strong>Rows</strong> option. Only works if the total items &gt; <strong>Visible slides</strong>. <br />block grid != slidesToShow option, yet both can work in tandem.<br />block grid = Rows option, yet the first is module feature, the later core.</small>');

    $form['grid_header']['#markup'] = '<h3 class="form__title form__title--grid">' . $header . '</h3>';

    $form['grid']['#description'] = t('The amount of block grid columns for large monitors 64.063em - 90em. <br /><strong>Requires</strong>:<ol><li>Visible items,</li><li>Skin Grid for starter,</li><li>A reasonable amount of contents,</li><li>Optionset with Rows and slidesPerRow = 1.</li></ol>This is module feature, older than core Rows, and offers more flexibility. Leave empty to DIY, or to not build grids.');
  }

  /**
   * Returns the closing ending form elements.
   */
  public function closingForm(array &$form, $definition = []) {
    $form['override'] = [
      '#title'       => t('Override main optionset'),
      '#type'        => 'checkbox',
      '#description' => t('If checked, the following options will override the main optionset. Useful to re-use one optionset for several different displays.'),
      '#weight'      => 112,
      '#enforced'    => TRUE,
    ];

    $form['overridables'] = [
      '#type'        => 'checkboxes',
      '#title'       => t('Overridable options'),
      '#description' => t("Override the main optionset to re-use one. Anything dictated here will override the current main optionset. Unchecked means FALSE"),
      '#options'     => $this->getOverridableOptions(),
      '#weight'      => 113,
      '#enforced'    => TRUE,
      '#states' => [
        'visible' => [
          ':input[name$="[override]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    parent::closingForm($form, $definition);
  }

  /**
   * Returns overridable options to re-use one optionset.
   */
  public function getOverridableOptions() {
    $options = [
      'arrows'        => t('Arrows'),
      'autoplay'      => t('Autoplay'),
      'dots'          => t('Dots'),
      'draggable'     => t('Draggable'),
      'infinite'      => t('Infinite'),
      'mouseWheel'    => t('Mousewheel'),
      'randomize'     => t('Randomize'),
      'variableWidth' => t('Variable width'),
    ];

    drupal_alter('slick_overridable_options_info', $options);
    return $options;
  }

  /**
   * Returns default layout options for the core Image, or Views.
   */
  public function getLayoutOptions() {
    return [
      'bottom'      => t('Caption bottom'),
      'top'         => t('Caption top'),
      'right'       => t('Caption right'),
      'left'        => t('Caption left'),
      'center'      => t('Caption center'),
      'center-top'  => t('Caption center top'),
      'below'       => t('Caption below the slide'),
      'stage-right' => t('Caption left, stage right'),
      'stage-left'  => t('Caption right, stage left'),
      'split-right' => t('Caption left, stage right, split half'),
      'split-left'  => t('Caption right, stage left, split half'),
      'stage-zebra' => t('Stage zebra'),
      'split-zebra' => t('Split half zebra'),
    ];
  }

  /**
   * Returns available slick optionsets by collection.
   */
  public function getOptionsetsByGroupOptions($collection = '') {
    return $this->manager->getOptionsetByGroupOptions($collection);
  }

  /**
   * Returns available slick skins for select options.
   */
  public function getSkinsByGroupOptions($collection = '') {
    return $this->manager->getSkinsByGroup($collection, TRUE);
  }

}
