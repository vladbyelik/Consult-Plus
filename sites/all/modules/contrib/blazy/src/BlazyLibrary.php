<?php

namespace Drupal\blazy;

/**
 * Provides Blazy library definitions.
 */
class BlazyLibrary {

  /**
   * Checks if Blazy should be active, related to BlazyFilter at D7.
   *
   * @var bool
   */
  private $isActive;

  /**
   * The libraries definition.
   *
   * @var array
   */
  protected $libraries;

  /**
   * The libraries info definition.
   *
   * @var array
   */
  protected $librariesInfo;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $manager;

  /**
   * Constructs a BlazyLibrary instance.
   */
  public function __construct(BlazyManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * Implements hook_library().
   */
  public function library() {
    if (!isset($this->libraries)) {
      $info       = system_get_info('module', 'blazy');
      $path       = drupal_get_path('module', 'blazy');
      $components = $path . '/css/components';
      $js_library = ['group' => JS_LIBRARY];
      $js_default = ['group' => JS_DEFAULT];
      $common     = [
        'website' => 'https://drupal.org/project/blazy',
        'version' => empty($info['version']) ? '1.x' : $info['version'],
      ];

      $libraries['blazy'] = [
        'title' => 'Blazy',
        'website' => 'https://github.com/dinbror/blazy',
        'js' => [
          libraries_get_path('blazy') . '/blazy.min.js' => [$js_library, 'weight' => -6],
        ],
      ];

      $libraries['dblazy'] = [
        'js' => [$path . '/js/dblazy.min.js' => [$js_library, 'weight' => -5.5]],
      ];

      $libraries['bio'] = [
        'js' => [$path . '/js/bio.min.js' => [$js_library, 'weight' => -5.4]],
        'dependencies' => [['blazy', 'dblazy']],
      ];

      $libraries['bio.media'] = [
        'js' => [$path . '/js/bio.media.min.js' => [$js_library, 'weight' => -5.3]],
        'dependencies' => [['blazy', 'bio']],
      ];

      $libraries['load'] = [
        'js' => [$path . '/js/blazy.load.min.js' => [$js_default, 'weight' => -3]],
        'css' => [$components . '/blazy.loading.css' => []],
        'dependencies' => [
          ['blazy', 'blazy'],
          ['blazy', 'dblazy'],
          ['blazy', 'bio.media'],
        ],
      ];

      $libraries['bio.ajax'] = [
        'js' => [$path . '/js/bio.ajax.min.js' => [$js_default]],
        'dependencies' => [['system', 'drupal.ajax'], ['blazy', 'load']],
      ];

      if ($this->manager->config('io.enabled', FALSE) && $this->manager->config('io.unblazy', FALSE)) {
        $libraries['load']['dependencies'] = [['blazy', 'bio.media']];
      }

      foreach (['admin', 'column', 'filter', 'grid', 'lightbox', 'ratio'] as $item) {
        $libraries[$item] = [
          'css' => [$components . '/blazy.' . $item . '.css' => []],
        ];
        if ($item == 'admin') {
          $libraries[$item]['js'] = [$path . '/js/blazy.admin.min.js' => []];
        }
      }

      foreach (['blazybox', 'colorbox', 'photobox', 'media'] as $item) {
        $css = $item == 'photobox' ? 'lightbox' : $item;
        $libraries[$item] = [
          'js' => [$path . '/js/blazy.' . $item . '.min.js' => [$js_default, 'weight' => -0.01]],
          'css' => [$components . '/blazy.' . $css . '.css' => []],
          'dependencies' => [['blazy', 'load']],
        ];
        if ($item != 'media') {
          $libraries[$item]['dependencies'][] = ['blazy', 'lightbox'];

          // Doh, colorbox has no core library definitions to depend on.
          if ($item == 'colorbox' && $colorbox = libraries_get_path('colorbox')) {
            $libraries['colorbox']['js'][$colorbox . '/jquery.colorbox-min.js'] = [$js_library, 'weight' => -4];
          }
        }
      }

      if (module_exists('photobox')) {
        $libraries['photobox']['dependencies'][] = ['photobox', 'photobox'];
      }
      elseif ($photobox = libraries_get_path('photobox')) {
        $libraries['photobox']['js'][$photobox . '/photobox/jquery.photobox.js'] = [$js_library, 'weight' => -4];
        $libraries['photobox']['css'][$photobox . '/photobox/photobox.css'] = [];
      }

      foreach ($libraries as &$library) {
        $library += $common;
        // jQuery is required at D7.
        if (isset($library['js'])) {
          $library['dependencies'][] = ['system', 'jquery.once'];
        }
      }

      $this->libraries = $libraries;
    }
    return $this->libraries;
  }

  /**
   * Implements hook_libraries_info().
   */
  public function librariesInfo() {
    if (!isset($this->librariesInfo)) {
      $libraries['blazy'] = [
        'name' => 'Blazy',
        'vendor url' => 'http://dinbror.dk/blazy/',
        'download url' => 'https://github.com/dinbror/blazy',
        'version arguments' => [
          'file' => 'blazy.min.js',
          'pattern' => '@v([0-9a-zA-Z\.-]+)@',
          'lines' => 5,
        ],
        'files' => ['js' => ['blazy.min.js']],
        'variants' => [
          'minified' => [
            'files' => [
              'js' => [
                'blazy.min.js',
              ],
            ],
          ],
          'source' => ['files' => ['js' => ['blazy.js']]],
        ],
      ];

      $this->librariesInfo = $libraries;
    }
    return $this->librariesInfo;
  }

  /**
   * Checks if Blazy is for the current URL, required by BlazyFilter at D7.
   *
   * @return bool
   *   TRUE if Blazy is active for the current page.
   */
  public function isActive() {
    if (!isset($this->isActive)) {
      $this->isActive = FALSE;
      // Make it possible deactivate Blazy with
      // parameter ?blazy=no in the url.
      if (isset($_GET['blazy']) && $_GET['blazy'] == 'no') {
        return $this->isActive;
      }

      // Code from the block_list function in block.module.
      $path = drupal_get_path_alias($_GET['q']);
      $pages = $this->manager->config('pages', BlazyDefault::PAGES, 'blazy.settings');

      // Compare with the internal and path alias (if any).
      $page_match = drupal_match_path($path, $pages);
      if ($path != $_GET['q']) {
        $page_match = $page_match || drupal_match_path($_GET['q'], $pages);
      }
      $page_match = $this->manager->config('visibility', 0, 'blazy.settings') == 0 ? !$page_match : $page_match;

      // Allow other modules to change the state of blazy for the current URL.
      drupal_alter('blazy_active', $page_match);
      $this->isActive = $page_match;
    }
    return $this->isActive;
  }

  /**
   * Implements hook_page_build().
   */
  public function pageBuild(&$page) {
    // We do this here because no attachments are supported at filter D7.
    if ($this->isActive() && $filters = $this->manager->config('filters', [], 'blazy.settings')) {
      $attach = ['blazy' => TRUE, 'filter' => TRUE, 'ratio' => TRUE];
      foreach ($filters as $format) {
        // Prevents blocking field formatters since this is done globally.
        if (isset($format['media_switch']) && $switch = $format['media_switch']) {
          $attach[$switch] = $switch;
        }

        foreach (['column', 'grid'] as $key) {
          if (isset($format[$key]) && $format[$key]) {
            $attach[$key] = $format[$key];
          }
        }
      }
      $page['page_bottom']['blazy']['#attached'] = $this->manager->attach($attach);
    }
  }

}
