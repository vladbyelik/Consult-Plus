<?php

namespace Drupal\blazy;

use Drupal\blazy\Utility\NestedArray;

/**
 * Implements BlazyManagerInterface.
 */
abstract class BlazyManagerBase implements BlazyManagerInterface {

  /**
   * Checks if the image style contains crop in the effect name.
   *
   * @var array
   */
  private $isCrop;

  /**
   * Returns available styles with crop in the effect name.
   *
   * @var array
   */
  private $cropStyles;

  /**
   * Checks if image dimensions are set.
   *
   * @var bool
   */
  private $isDimensionSet;

  /**
   * The available optionsets for all blazy-related modules.
   *
   * @var array
   */
  protected $optionsetOptions;

  /**
   * The supported lightboxes.
   *
   * @var array
   */
  protected $lightboxes = [];

  /**
   * Checks if blazy is attached.
   *
   * @var bool
   */
  private $isBlazyAttached;

  /**
   * The blazy IO settings.
   *
   * @var object
   */
  protected $isIoSettings;

  /**
   * Returns any config, or keyed by the $key.
   *
   * @todo use D8 approach for this.
   */
  public function config($key = '', $default = NULL, $id = 'blazy.settings', array $defaults = []) {
    $config = variable_get($id, $defaults);

    // Somebody likes deleting variables for no apparent reasons, make em happy.
    if (!$config) {
      return $default;
    }

    if (!$key) {
      return $config;
    }

    // Support once level array with dot notation.
    if (strpos($key, ".") !== FALSE) {
      $parts = explode(".", $key);
      // @todo $value = NestedArray::getValue($config, (array) $parts);
      $value = isset($config[$parts[0]][$parts[1]]) ? $config[$parts[0]][$parts[1]] : $default;
    }
    else {
      $value = isset($config[$key]) ? $config[$key] : $default;
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function typecast(array &$config, $id = 'blazy.settings') {
    // Do nothing, allows extenders without UI to forget about it.
  }

  /**
   * Returns the cache id based on few generic setting values.
   */
  public function getCacheId(array $settings = []) {
    $keys = isset($settings['cache_metadata']['keys']) ? $settings['cache_metadata']['keys'] : [$settings['id']];
    $keys[] = count(array_filter($settings));
    return implode(':', $keys);
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array $attach) {
    $load = [];
    $lazy = !empty($attach['blazy']) || (isset($attach['lazy']) && $attach['lazy'] == 'blazy');

    // Allow both variants of grid or column to co-exist for different fields.
    if (!empty($attach['style'])) {
      foreach (['column', 'grid'] as $grid) {
        $attach[$grid] = $attach['style'];
      }
    }

    $components = ['column', 'filter', 'grid', 'media', 'ratio'];
    foreach ($components as $component) {
      if (!empty($attach[$component])) {
        $load['library'][] = ['blazy', $component];
      }
    }

    foreach (['lightbox', 'colorbox', 'photobox'] as $box) {
      if (!empty($attach[$box])) {
        $load['library'][] = ['blazy', $box];
      }
    }

    // Picture integration.
    if (!empty($attach['resimage'])) {
      $load['library'][] = ['picture', 'picturefill_head'];
      $load['library'][] = ['picture', 'picturefill'];
      $load['library'][] = ['picture', 'picture.ajax'];

      if ($lazy) {
        $load['library'][] = ['picture', 'lazysizes'];
        $load['library'][] = ['picture', 'lazysizes_aspect_ratio'];
      }
    }

    // Core Blazy libraries.
    if (!isset($this->isBlazyAttached)) {
      // Core Blazy libraries, enforced to prevent JS error when optional.
      $load['js'][] = [
        'data' => [
          'blazy' => $this->config('blazy', BlazyDefault::formSettings()['blazy']),
          'blazyIo' => $this->getIoSettings($attach),
        ],
        'type' => 'setting',
      ];
      $load['library'][] = ['blazy', 'load'];

      $this->isBlazyAttached = TRUE;
    }

    // Adds AJAX helper to revalidate IO, if using IO with VIS, or alike.
    if (!empty($attach['use_ajax'])) {
      $load['library'][] = ['blazy', 'bio.ajax'];
    }

    drupal_alter('blazy_attach', $load, $attach);
    return $load;
  }

  /**
   * Returns drupalSettings for IO.
   */
  public function getIoSettings(array $attach = []) {
    if (!isset($this->isIoSettings)) {
      $thold = trim($this->config('io.threshold', '0', 'blazy.settings'));
      $number = strpos($thold, '.') !== FALSE ? (float) $thold : (int) $thold;
      $thold = strpos($thold, ',') !== FALSE ? array_map('trim', explode(',', $thold)) : [$number];

      // Respects hook_blazy_attach_alter() for more fine-grained control.
      foreach (['enabled', 'disconnect', 'rootMargin', 'threshold'] as $key) {
        $default = $key == 'rootMargin' ? '0px' : FALSE;
        $value = $key == 'threshold' ? $thold : $this->config('io.' . $key, $default);
        $io[$key] = isset($attach['io.' . $key]) ? $attach['io.' . $key] : $value;
      }

      // Prevents drupal_array_merge_deep from duplicating threshold values.
      $this->isIoSettings = (object) $io;
    }

    return $this->isIoSettings;
  }

  /**
   * Collects defined skins as registered via hook_MODULE_NAME_skins_info().
   */
  public function buildSkins($namespace, $skin_class, $methods = []) {
    $cid = $namespace . ':skins';

    if ($cache = cache_get($cid)) {
      return $cache->data;
    }

    $classes = module_invoke_all($namespace . '_skins_info');
    $classes = array_merge([$skin_class], (array) $classes);
    $items   = $skins = [];
    foreach ($classes as $class) {
      if (class_exists($class)) {
        $reflection = new \ReflectionClass($class);
        if ($reflection->implementsInterface($skin_class . 'Interface')) {
          $skin = new $class();
          if (empty($methods) && method_exists($skin, 'skins')) {
            $items = $skin->skins();
          }
          else {
            foreach ($methods as $method) {
              $items[$method] = method_exists($skin, $method) ? $skin->{$method}() : [];
            }
          }
        }
      }
      $skins = NestedArray::mergeDeep($skins, $items);
    }

    cache_set($cid, $skins, 'cache', CACHE_PERMANENT);

    return $skins;
  }

  /**
   * {@inheritdoc}
   */
  public function getLightboxes() {
    $boxes = $this->lightboxes + ['colorbox', 'photobox'];

    $lightboxes = [];
    foreach (array_unique($boxes) as $lightbox) {
      if (function_exists($lightbox . '_theme')) {
        $lightboxes[] = $lightbox;
      }
    }

    drupal_alter('blazy_lightboxes', $lightboxes);
    return array_unique($lightboxes);
  }

  /**
   * {@inheritdoc}
   */
  public function setLightboxes($lightbox) {
    $this->lightboxes[] = $lightbox;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanUpBreakpoints(array &$settings) {
    if (!empty($settings['breakpoints'])) {
      $breakpoints = array_filter(array_map('array_filter', $settings['breakpoints']));

      $settings['breakpoints'] = NestedArray::filter($breakpoints, function ($breakpoint) {
        return !(is_array($breakpoint) && (empty($breakpoint['width']) || empty($breakpoint['image_style'])));
      });

      // Identify that Blazy can be activated only by breakpoints.
      if (empty($settings['blazy'])) {
        $settings['blazy'] = !empty($settings['breakpoints']);
      }
    }
  }

  /**
   * Returns available image styles with crop in the name.
   */
  public function cropStyles() {
    if (!isset($this->cropStyles)) {
      $this->cropStyles = [];
      foreach (image_styles() as $style) {
        foreach ($style['effects'] as $effect) {
          if (strpos($effect['name'], 'crop') !== FALSE) {
            $this->cropStyles[$style['name']] = $style['name'];
            break;
          }
        }
      }
    }
    return $this->cropStyles;
  }

  /**
   * {@inheritdoc}
   */
  public function isCrop($style) {
    if (!isset($this->isCrop[$style])) {
      $this->isCrop[$style] = $this->cropStyles() && isset($this->cropStyles()[$style]);
    }
    return $this->isCrop[$style];
  }

  /**
   * {@inheritdoc}
   */
  public function isBlazy(array &$settings, array $item = []) {
    $image = isset($item['item']) ? $item['item'] : NULL;

    // Blazy formatter within Views fields by supported modules.
    if (isset($item['settings'])) {
      $blazy = $item['settings'];

      // Allows breakpoints overrides such as GridStack multi-styled images.
      if (empty($settings['breakpoints']) && isset($blazy['breakpoints'])) {
        $settings['breakpoints'] = $blazy['breakpoints'];
      }

      $cherries = [
        'box_style',
        'image_style',
        'media_switch',
        'ratio',
        'thumbnail_style',
        'uri',
      ];

      foreach ($cherries as $key) {
        $fallback = isset($settings[$key]) ? $settings[$key] : '';
        $settings[$key] = isset($blazy[$key]) && empty($fallback) ? $blazy[$key] : $fallback;
      }

      $settings['first_item'] = $image;
      $settings['first_uri'] = empty($settings['first_uri']) ? $settings['uri'] : $settings['first_uri'];
      unset($settings['uri']);
    }

    // Allows lightboxes to provide its own optionsets.
    $switch = empty($settings['media_switch']) ? FALSE : $settings['media_switch'];
    if ($switch) {
      $settings[$switch] = empty($settings[$switch]) ? $switch : $settings[$switch];
    }

    // Provides data for the [data-blazy] attribute at the containing element.
    $this->cleanUpBreakpoints($settings);
    if (!empty($settings['breakpoints'])) {
      $this->buildDataBlazy($settings, $image);
    }

    unset($settings['first_image']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildDataBlazy(array &$settings, $item = NULL) {
    // Identify that Blazy can be activated by breakpoints, regardless results.
    $settings['blazy'] = TRUE;

    // Bail out if blazy_data has been defined at self::setDimensionsOnce().
    // Blazy doesn't always deal with image formatters, see self::isBlazy().
    if (!empty($settings['blazy_data'])) {
      return;
    }

    // This may be set at self::setDimensionsOnce() if using formatters, yet it
    // is not set from non-formatters like views fields, see self::isBlazy().
    if (empty($settings['original_width'])) {
      $settings['original_width'] = $item && isset($item->width) ? $item->width : NULL;
      $settings['original_height'] = $item && isset($item->height) ? $item->height : NULL;
    }

    $json = $sources = $styles = [];
    $end = end($settings['breakpoints']);

    // Check for cropped images at the 5 given styles before any hard work
    // Ok as run once at the top container regardless of thousand of images.
    foreach ($settings['breakpoints'] as $key => $breakpoint) {
      if ($this->isCrop($breakpoint['image_style'])) {
        $styles[$key] = TRUE;
      }
    }

    // Bail out if not all images are cropped at all breakpoints.
    // The site builder just don't read the performance tips section.
    if (count($styles) != count($settings['breakpoints'])) {
      return;
    }

    // We have all images cropped here.
    foreach ($settings['breakpoints'] as $key => $breakpoint) {
      if ($width = Blazy::widthFromDescriptors($breakpoint['width'])) {
        // If contains crop, sets dimension once, and let all images inherit.
        if (!empty($settings['ratio'])) {
          $dimensions = Blazy::transformDimensions($breakpoint['image_style'], $item);
          $padding = round((($dimensions['height'] / $dimensions['width']) * 100), 2);

          $json['dimensions'][$width] = $padding;

          // Only set padding-bottom for the last breakpoint to avoid FOUC.
          if ($end['width'] == $breakpoint['width']) {
            $settings['padding_bottom'] = $padding;
          }
        }

        // If BG, provide [data-src-BREAKPOINT], regardless uri or ratio.
        if (!empty($settings['background'])) {
          $sources[] = ['width' => (int) $width, 'src' => 'data-src-' . $key];
        }
      }
    }

    // As of Blazy v1.6.0 applied to BG only.
    if ($sources) {
      $json['breakpoints'] = $sources;
    }

    // Supported modules can add blazy_data as [data-blazy] to the container.
    // This also informs individual images to not work with dimensions any more
    // as _all_ breakpoint image styles contain 'crop'.
    if ($json) {
      $settings['blazy_data'] = $json;
    }
  }

  /**
   * Returns available optionsets for select options.
   */
  public function getOptionsetOptions(array $entities = []) {
    if (!isset($this->optionsetOptions)) {
      foreach ($entities as $key => $optionset) {
        $this->optionsetOptions[$key] = check_plain($optionset->label);
      }
      asort($this->optionsetOptions);
    }
    return $this->optionsetOptions;
  }

}
