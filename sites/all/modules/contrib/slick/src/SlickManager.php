<?php

namespace Drupal\slick;

use Drupal\slick\Entity\Slick;
use Drupal\blazy\Utility\NestedArray;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyManagerBase;

/**
 * Implements SlickManagerInterface.
 */
class SlickManager extends BlazyManagerBase implements SlickManagerInterface {

  /**
   * Static cache for the skin definition.
   *
   * @var array
   */
  protected $skinDefinition;

  /**
   * Static cache for the skins by group.
   *
   * @var array
   */
  protected $skinsByGroup;

  /**
   * Static cache for the skins by group.
   *
   * @var array
   */
  protected $optionsetByGroup;

  /**
   * The easing library.
   *
   * @var string|bool
   */
  protected $easingPath;

  /**
   * The supported skins.
   *
   * @var array
   */
  private static $skins = ['lightbox', 'main', 'thumbnail', 'arrows', 'dots'];

  /**
   * Returns the supported skins.
   */
  public static function getConstantSkins() {
    return self::$skins;
  }

  /**
   * {@inheritdoc}
   */
  public function config($key = '', $default = NULL, $id = 'slick.settings', array $defaults = []) {
    return parent::config($key, $default, $id, $id == 'slick.settings' ? SlickDefault::formSettings() : $defaults);
  }

  /**
   * {@inheritdoc}
   */
  public function typecast(array &$config, $id = 'slick.settings') {
    if ($id == 'slick.settings') {
      foreach (SlickDefault::formSettings() as $key => $value) {
        if (isset($config[$key])) {
          settype($config[$key], gettype($value));
        }
      }
    }
  }

  /**
   * Returns easing library path if available, else FALSE.
   */
  public function getEasingPath() {
    if (!isset($this->easingPath)) {
      $library_easing = libraries_get_path('easing') ?: libraries_get_path('jquery.easing');
      if ($library_easing) {
        $easing_path = $library_easing . '/jquery.easing.min.js';
        // Composer via bower-asset puts the library within `js` directory.
        if (!is_file($easing_path)) {
          $easing_path = $library_easing . '/js/jquery.easing.min.js';
        }
      }
      $this->easingPath = isset($easing_path) && is_file($easing_path) ? $easing_path : FALSE;
    }
    return $this->easingPath;
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array $attach) {
    $load = parent::attach($attach);

    // Load optional easing library.
    if ($this->getEasingPath()) {
      $load['library'][] = ['slick', 'easing'];
    }

    // Load optional colorbox, or mousewheel.
    foreach (['colorbox', 'mousewheel'] as $component) {
      if (!empty($attach[$component])) {
        $load['library'][] = ['slick', $component];
      }
    }

    // Load the main slick initializer.
    $load['library'][] = ['slick', 'load'];

    // Only attach a skin if so configured.
    if (!empty($attach['skin'])) {
      $this->attachSkin($load, $attach);
    }

    // Attach default JS settings to allow responsive displays have a lookup,
    // excluding wasted/trouble options, e.g.: PHP string vs JS object.
    $excludes = explode(' ', 'mobileFirst appendArrows appendDots asNavFor prevArrow nextArrow respondTo');
    $excludes = array_combine($excludes, $excludes);
    $load['js'][] = [
      'data' => ['slick' => array_diff_key(Slick::defaultSettings(), $excludes)],
      'type' => 'setting',
    ];

    drupal_alter('slick_attach', $load, $attach);
    return $load;
  }

  /**
   * Provides skins only if required.
   */
  public function attachSkin(array &$load, $attach = []) {
    if ($this->config('slick_css', TRUE)) {
      $load['library'][] = ['slick', 'css'];
    }

    if ($this->config('module_css', TRUE)) {
      $load['library'][] = ['slick', 'theme'];
    }

    if (!empty($attach['thumbnail_effect'])) {
      $load['library'][] = ['slick', 'thumbnail.' . $attach['thumbnail_effect']];
    }

    if (!empty($attach['down_arrow'])) {
      $load['library'][] = ['slick', 'arrows.down'];
    }

    foreach (self::getConstantSkins() as $group) {
      $skin = $group == 'main' ? $attach['skin'] : (isset($attach['skin_' . $group]) ? $attach['skin_' . $group] : '');
      if (!empty($skin)) {
        $skins = $this->getSkinsByGroup($group);
        $provider = isset($skins[$skin]['provider']) ? $skins[$skin]['provider'] : 'slick';
        $load['library'][] = ['slick', $provider . '.' . $group . '.' . $skin];
      }
    }
  }

  /**
   * Returns slick skins registered via hook_slick_skins_info(), or defaults.
   *
   * @see \Drupal\blazy\BlazyManagerBase::buildSkins()
   */
  public function getSkins() {
    if (!isset($this->skinDefinition)) {
      $methods = ['skins', 'arrows', 'dots'];
      $this->skinDefinition = $this->buildSkins('slick', '\Drupal\slick\SlickSkin', $methods);
    }
    return $this->skinDefinition;
  }

  /**
   * Returns available slick skins by group.
   */
  public function getSkinsByGroup($group = '', $option = FALSE) {
    if (!isset($this->skinsByGroup[$group])) {
      $skins         = $grouped = $ungrouped = [];
      $nav_skins     = in_array($group, ['arrows', 'dots']);
      $defined_skins = $nav_skins ? $this->getSkins()[$group] : $this->getSkins()['skins'];

      foreach ($defined_skins as $skin => $properties) {
        $item = $option ? check_plain($properties['name']) : $properties;
        if (!empty($group)) {
          if (isset($properties['group'])) {
            if ($properties['group'] != $group) {
              continue;
            }
            $grouped[$skin] = $item;
          }
          elseif (!$nav_skins) {
            $ungrouped[$skin] = $item;
          }
        }
        $skins[$skin] = $item;
      }
      $this->skinsByGroup[$group] = $group ? array_merge($ungrouped, $grouped) : $skins;
    }
    return $this->skinsByGroup[$group];
  }

  /**
   * Returns available slick optionsets by collection for select options.
   */
  public function getOptionsetByGroupOptions($group = '') {
    if (!isset($this->optionsetByGroup[$group])) {
      $optionsets = $collected = $uncollected = [];
      $slicks = Slick::loadMultiple();
      foreach ($slicks as $slick) {
        $name = check_plain($slick->label);
        $id = $slick->name;
        $current_collection = $slick->collection;
        if (!empty($group)) {
          if ($current_collection) {
            if ($current_collection != $group) {
              continue;
            }
            $collected[$id] = $name;
          }
          else {
            $uncollected[$id] = $name;
          }
        }
        $optionsets[$id] = $name;
      }

      $this->optionsetByGroup[$group] = $group ? array_merge($uncollected, $collected) : $optionsets;
    }
    return $this->optionsetByGroup[$group];
  }

  /**
   * {@inheritdoc}
   */
  public function slick(array $build = []) {
    foreach (SlickDefault::themeProperties() as $key) {
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    return empty($build['items']) ? [] : [
      '#theme'      => 'slick',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => ['slick_pre_render'],
    ];
  }

  /**
   * Prepare attributes for the known module features, not necessarily users'.
   */
  public function prepareAttributes(array $build = []) {
    $settings = $build['settings'];
    $attributes = isset($build['attributes']) ? $build['attributes'] : [];
    $classes = [];
    $skin = $settings['skin'];
    if ($skin) {
      $classes[] = 'skin--' . str_replace('_', '-', $skin);
      if (strpos($skin, 'boxed') !== FALSE) {
        $classes[] = 'skin--boxed';
      }
      if (strpos($skin, 'split') !== FALSE) {
        $classes[] = 'skin--split';
      }
    }

    if ($settings['nav']) {
      $classes[] = $settings['display'];
    }
    if ($settings['vertical']) {
      $classes[] = 'vertical';
    }
    if ($settings['optionset']) {
      $classes[] = 'optionset--' . str_replace('_', '-', $settings['optionset']);
    }

    if ($settings['display'] == 'main') {
      // Sniffs for Views to allow block__no_wrapper, views__no_wrapper, etc.
      if ($settings['view_name'] && $settings['current_view_mode']) {
        $classes[] = 'view--' . str_replace('_', '-', $settings['view_name']);
        $classes[] = 'view--' . str_replace('_', '-', $settings['view_name'] . '--' . $settings['current_view_mode']);
      }

      // Blazy can still lazyload an unslick.
      if ($settings['lazy'] == 'blazy' || !empty($settings['blazy'])) {
        $attributes['class'][] = 'blazy';
        $attributes['data-blazy'] = empty($settings['blazy_data']) ? '' : drupal_json_encode($settings['blazy_data']);
      }

      // Provide a context for lightbox, or multimedia galleries, save for grid.
      if (!empty($settings['media_switch'])) {
        $switch = str_replace('_', '-', $settings['media_switch']);
        $classes[] = $switch;

        // Only if not using grid, output the gallery attribute.
        if (empty($settings['grid'])) {
          $attributes['data-' . $switch . '-gallery'] = TRUE;
        }
      }
    }
    elseif ($settings['display'] == 'thumbnail') {
      if ($settings['thumbnail_caption']) {
        $classes[] = 'has-caption';
      }
    }

    foreach ($classes as $class) {
      $attributes['class'][] = 'slick--' . $class;
    }
    return $attributes;
  }

  /**
   * Builds the Slick instance as a structured array ready for ::renderer().
   */
  public function preRender(array $element) {
    $build = $element['#build'];
    unset($element['#build']);

    $settings = &$build['settings'];
    $settings += SlickDefault::htmlSettings();

    // Adds helper class if thumbnail on dots hover provided.
    // The thumbnail_style is provided by formatter, thumbnail by Slick Views.
    if (!empty($settings['thumbnail_effect']) && (!empty($settings['thumbnail_style']) || !empty($settings['thumbnail']))) {
      $dots_class[] = 'slick-dots--thumbnail-' . $settings['thumbnail_effect'];
    }

    // Adds dots skin modifier class if provided.
    if (!empty($settings['skin_dots'])) {
      $dots_class[] = 'slick-dots--' . str_replace('_', '-', $settings['skin_dots']);
    }

    // Merge dot classes with the custom defined at optionset.
    if (isset($dots_class) && !empty($build['optionset'])) {
      $dots_class[] = $build['optionset']->getSetting('dotsClass') ?: 'slick-dots';
      $js['dotsClass'] = implode(" ", $dots_class);
    }

    // Overrides common options to re-use an optionset.
    if ($settings['display'] == 'main') {
      if (!empty($settings['override'])) {
        foreach ($settings['overridables'] as $key => $override) {
          $js[$key] = empty($override) ? FALSE : TRUE;
        }
      }

      // Hijack items, and build the Slick grid if so configured.
      if (!empty($settings['grid']) && !empty($settings['visible_items'])) {
        $build['items'] = $this->buildGrid($build['items'], $settings);
      }
    }

    $build['attributes'] = $this->prepareAttributes($build);
    $build['options'] = isset($js) ? array_merge($build['options'], $js) : $build['options'];

    drupal_alter('slick_optionset', $build['optionset'], $settings);

    // Pass the array to render array.
    foreach (SlickDefault::themeProperties() as $key) {
      $element["#$key"] = $build[$key];
    }

    unset($build);
    return $element;
  }

  /**
   * Returns items as a grid display.
   */
  public function buildGrid(array $items, array &$settings = []) {
    // Enforces unslick with less items. A slideshow should slide, else destroy.
    if (empty($settings['unslick']) && !empty($settings['count'])) {
      $settings['unslick'] = $settings['count'] < $settings['visible_items'];
    }

    // Display all items if unslick is enforced for plain grid to lightbox.
    if (!empty($settings['unslick'])) {
      $settings['display']      = 'main';
      $settings['current_item'] = 'grid';
      $settings['count']        = 2;

      $grids[0] = $this->buildGridItem($items, 0, $settings);
    }
    else {
      // Otherwise do chunks to have a grid carousel, and also update count.
      $preserve_keys     = !empty($settings['preserve_keys']);
      $grid_items        = array_chunk($items, $settings['visible_items'], $preserve_keys);
      $settings['count'] = count($grid_items);

      foreach ($grid_items as $delta => $grid_item) {
        $grids[] = $this->buildGridItem($grid_item, $delta, $settings);
      }
    }
    return $grids;
  }

  /**
   * Returns items as a grid item display.
   */
  public function buildGridItem(array $items, $delta, array $settings = []) {
    $slide = [
      '#theme'      => 'slick_grid',
      '#items'      => $items,
      '#delta'      => $delta,
      '#settings'   => $settings,
      '#attributes' => $this->prepareGridAttributes($settings),
    ];
    return ['slide' => $slide, 'settings' => $settings];
  }

  /**
   * Prepare attributes for the known module features, not necessarily users'.
   */
  public function prepareGridAttributes(array $settings = []) {
    // By default Slick only supports Grid Foundation, adds relevant grid_id for
    // optional Style: CSS3 Columns, and probably future flexbox.
    $grid_id = empty($settings['style']) ? 'grid' : $settings['style'];
    $classes[] = 'block-columngrid block-' . $grid_id;
    $classes[] = $settings['unslick'] ? 'slick__grid' : 'slide__content';

    $settings['grid_large'] = $settings['grid'];
    foreach (['small', 'medium', 'large'] as $grid) {
      if ($column = $settings['grid_' . $grid]) {
        $classes[] = $grid . '-block-' . $grid_id . '-' . $column;
      }
    }

    foreach ($classes as $class) {
      $attributes['class'][] = $class;
    }

    // Support a grid of lightbox or inline multimedia gallery.
    if (!empty($settings['media_switch'])) {
      $switch = str_replace('_', '-', $settings['media_switch']);
      $attributes['data-' . $switch . '-gallery'] = TRUE;
    }
    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build = []) {
    foreach (SlickDefault::themeProperties() as $key) {
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    $settings = $build['settings'];
    $cache = [];
    if (!empty($settings['cache'])) {
      $cache['#cache']['cid'] = $this->getCacheId($settings);
      $cache['#cache']['expire'] = $settings['cache'] == CACHE_TEMPORARY ? CACHE_TEMPORARY : REQUEST_TIME + $settings['cache'];
    }

    $slick = [
      '#theme'      => 'slick_wrapper',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => ['slick_pre_render_wrapper'],
    ] + $cache;

    drupal_alter('slick_build', $slick, $settings);
    return empty($build['items']) ? [] : $slick;
  }

  /**
   * Prepare attributes for the known module features, not necessarily users'.
   */
  public function prepareWrapperAttributes(array $settings = []) {
    $classes = [];
    if (!empty($settings['skin'])) {
      $classes[] = str_replace('_', '-', $settings['skin']);
    }
    if (!empty($settings['skin_thumbnail'])) {
      $classes[] = str_replace('_', '-', $settings['skin_thumbnail']);
    }
    if (!empty($settings['vertical'])) {
      $classes[] = 'v';
    }
    if (!empty($settings['vertical_tn'])) {
      $classes[] = 'v-tn';
    }
    if (!empty($settings['thumbnail_position'])) {
      $classes[] = 'tn-' . $settings['thumbnail_position'];
      if (strpos($settings['thumbnail_position'], 'over') !== FALSE) {
        $classes[] = 'tn-overlay';
        $classes[] = 'tn-' . str_replace('over-', '', $settings['thumbnail_position']);
      }
    }

    $attributes['class'][] = 'slick-wrapper';
    foreach ($classes as $class) {
      $attributes['class'][] = 'slick-wrapper--' . $class;
    }
    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function preRenderWrapper($element) {
    $build = $element['#build'];
    unset($element['#build']);

    // One slick_theme() to serve multiple displays: main, overlay, thumbnail.
    $settings = array_merge(SlickDefault::htmlSettings(), $build['settings']);
    $id       = $settings['id'] ?: Blazy::getHtmlId('slick');
    $thumb_id = $id . '-thumbnail';
    $options  = $build['options'];
    $switch   = $settings['media_switch'];
    $thumbs   = isset($build['thumb']) ? $build['thumb'] : [];

    // Prevents unused thumb going through the main display.
    unset($build['thumb']);

    // Supports programmatic options defined within skin definitions to allow
    // addition of options with other libraries integrated with Slick without
    // modifying optionset such as for Zoom, Reflection, Slicebox, Transit, etc.
    if (!empty($settings['skin']) && $skins = $this->getSkinsByGroup('main')) {
      if (isset($skins[$settings['skin']]['options'])) {
        $options = array_merge($options, $skins[$settings['skin']]['options']);
      }
    }

    // Load the optionset to work with.
    $optionset            = $build['optionset'] ?: Slick::loadWithFallback($settings['optionset']);
    $settings['count']    = empty($settings['count']) ? count($build['items']) : $settings['count'];
    $settings['id']       = $id;
    $settings['nav']      = $settings['nav'] ?: (!empty($settings['optionset_thumbnail']) && isset($build['items'][1]));
    $settings['navpos']   = $settings['nav'] && !empty($settings['thumbnail_position']);
    $settings['vertical'] = $optionset->getSetting('vertical');
    $mousewheel           = $optionset->getSetting('mouseWheel');

    // If thumbnail navigation is required, build one.
    if ($settings['nav']) {
      $options['asNavFor'] = "#{$thumb_id}-slider";
      $optionset_thumbnail = Slick::loadWithFallback($settings['optionset_thumbnail']);
      $mousewheel = $optionset_thumbnail->getSetting('mouseWheel');
      $settings['vertical_tn'] = $optionset_thumbnail->getSetting('vertical');
    }
    else {
      // Pass extra attributes such as those from Commerce product variations to
      // theme_slick() since we have no asNavFor wrapper here.
      if (isset($element['#attributes'])) {
        $build['attributes'] = empty($build['attributes']) ? $element['#attributes'] : NestedArray::mergeDeep($build['attributes'], $element['#attributes']);
      }
    }

    // Attach libraries.
    if ($switch && $switch != 'content') {
      $settings[$switch] = empty($settings[$switch]) ? $switch : $settings[$switch];
    }

    // Supports Blazy multi-breakpoint or lightbox images if provided.
    // Cases: Blazy within Views gallery, or references without direct image.
    if (!empty($settings['check_blazy']) && !empty($settings['first_image'])) {
      $this->isBlazy($settings, $settings['first_image']);
    }

    // Pass needed build items into slick.
    $settings['mousewheel'] = $mousewheel;
    $settings['down_arrow'] = $optionset->getSetting('downArrow');
    $settings['lazy']       = $settings['lazy'] ?: $optionset->getSetting('lazyLoad');
    $settings['blazy']      = empty($settings['blazy']) ? $settings['lazy'] == 'blazy' : $settings['blazy'];
    $settings               = array_filter($settings);
    $build['options']       = $options;
    $build['optionset']     = $optionset;
    $build['settings']      = $settings;

    // Build the Slick wrapper elements, and add attachments.
    $attachments            = $this->attach($settings);
    $element['#settings']   = $settings;
    $element['#attached']   = empty($build['attached']) ? $attachments : NestedArray::mergeDeep($build['attached'], $attachments);
    $element['#attributes'] = $this->prepareWrapperAttributes($settings);

    // Build the main Slick.
    $slick[0] = $this->slick($build);

    // Build the thumbnail Slick.
    if (!empty($settings['nav']) && $thumbs) {
      $build = [];
      foreach (['items', 'options', 'settings'] as $key) {
        $build[$key] = isset($thumbs[$key]) ? $thumbs[$key] : [];
      }

      $settings                     = array_merge($settings, $build['settings']);
      $settings['optionset']        = $settings['optionset_thumbnail'];
      $settings['skin']             = $settings['skin_thumbnail'];
      $settings['display']          = 'thumbnail';
      $build['optionset']           = $optionset_thumbnail;
      $build['settings']            = array_filter($settings);
      $build['options']['asNavFor'] = "#{$id}-slider";

      $slick[1] = $this->slick($build);
    }

    // Reverse slicks if thumbnail position is provided to get CSS float work.
    if (!empty($settings['navpos'])) {
      $slick = array_reverse($slick);
    }

    // Collect the slick instances.
    $element['#items'] = $slick;
    unset($build);
    return $element;
  }

}
