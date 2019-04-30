<?php

namespace Drupal\slick\Entity;

/**
 * Defines the Slick configuration entity.
 */
class Slick extends SlickBase implements SlickInterface {

  /**
   * The optionset group for easy selections.
   *
   * @var string
   */
  public $collection = '';

  /**
   * The skin name for the optionset.
   *
   * @var string
   */
  public $skin = '';

  /**
   * The number of breakpoints for the optionset.
   *
   * @var int
   */
  public $breakpoints = 0;

  /**
   * The flag indicating to optimize the stored options by removing defaults.
   *
   * @var bool
   */
  public $optimized = 0;

  /**
   * {@inheritdoc}
   */
  public function getSkin() {
    return $this->skin;
  }

  /**
   * {@inheritdoc}
   */
  public function getBreakpoints() {
    return $this->breakpoints;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollection() {
    return $this->collection;
  }

  /**
   * {@inheritdoc}
   */
  public function optimized() {
    return $this->optimized;
  }

  /**
   * Returns the typecast values.
   *
   * @param array $settings
   *   An array of Optionset settings.
   */
  public static function typecast(array &$settings = []) {
    if (empty($settings)) {
      return;
    }

    $defaults = self::defaultSettings();
    foreach ($defaults as $name => $value) {
      if (isset($settings[$name])) {
        // Seems double is ignored, and causes a missing schema, unlike float.
        $type = gettype($defaults[$name]);
        $type = $type == 'double' ? 'float' : $type;

        // Change float to integer if value is no longer float.
        if ($name == 'edgeFriction') {
          $type = $settings[$name] == '1' ? 'integer' : 'float';
        }

        settype($settings[$name], $type);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    if (!isset(static::$defaultSettings)) {
      static::$defaultSettings = [
        'mobileFirst'      => FALSE,
        'asNavFor'         => '',
        'accessibility'    => TRUE,
        'adaptiveHeight'   => FALSE,
        'autoplay'         => FALSE,
        'autoplaySpeed'    => 3000,
        'pauseOnHover'     => TRUE,
        'pauseOnDotsHover' => FALSE,
        'arrows'           => TRUE,
        'prevArrow'        => '<button type="button" data-role="none" class="slick-prev" aria-label="Previous" tabindex="0">Previous</button>',
        'nextArrow'        => '<button type="button" data-role="none" class="slick-next" aria-label="Next" tabindex="0">Next</button>',
        'downArrow'        => FALSE,
        'downArrowTarget'  => '',
        'downArrowOffset'  => 0,
        'centerMode'       => FALSE,
        'centerPadding'    => '50px',
        'dots'             => FALSE,
        'dotsClass'        => 'slick-dots',
        'appendDots'       => '$(element)',
        'draggable'        => TRUE,
        'fade'             => FALSE,
        'focusOnSelect'    => FALSE,
        'infinite'         => TRUE,
        'initialSlide'     => 0,
        'lazyLoad'         => 'ondemand',
        'mouseWheel'       => FALSE,
        'randomize'        => FALSE,
        'respondTo'        => 'window',
        'rows'             => 1,
        'slidesPerRow'     => 1,
        'slide'            => '',
        'slidesToShow'     => 1,
        'slidesToScroll'   => 1,
        'speed'            => 500,
        'swipe'            => TRUE,
        'swipeToSlide'     => FALSE,
        'edgeFriction'     => 0.35,
        'touchMove'        => TRUE,
        'touchThreshold'   => 5,
        'useCSS'           => TRUE,
        'cssEase'          => 'ease',
        'cssEaseBezier'    => '',
        'cssEaseOverride'  => '',
        'useTransform'     => TRUE,
        'easing'           => 'linear',
        'variableWidth'    => FALSE,
        'vertical'         => FALSE,
        'verticalSwiping'  => FALSE,
        'waitForAnimate'   => TRUE,
      ];
    }
    return static::$defaultSettings;
  }

  /**
   * Returns default database field property values.
   *
   * @return mixed[]
   *   An array of property values, keyed by property name.
   */
  public static function defaultProperties() {
    return parent::defaultProperties() + [
      'breakpoints' => 0,
      'collection'  => '',
      'optimized'   => 0,
      'skin'        => '',
    ];
  }

  /**
   * Returns the Slick responsive settings.
   *
   * @return array
   *   The responsive options.
   */
  public function getResponsiveOptions() {
    if (empty($this->breakpoints)) {
      return FALSE;
    }
    $options = [];
    if (isset($this->options['responsives']['responsive'])) {
      $responsives = $this->options['responsives'];
      if ($responsives['responsive']) {
        foreach ($responsives['responsive'] as $delta => $responsive) {
          if (empty($responsives['responsive'][$delta]['breakpoint'])) {
            unset($responsives['responsive'][$delta]);
          }
          if (isset($responsives['responsive'][$delta])) {
            $options[$delta] = $responsive;
          }
        }
      }
    }
    return $options;
  }

  /**
   * Sets the Slick responsive settings.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setResponsiveSettings($values, $delta = 0, $key = 'settings') {
    $this->options['responsives']['responsive'][$delta][$key] = $values;
    return $this;
  }

  /**
   * Strip out options containing default values so to have real clean JSON.
   *
   * @return array
   *   The cleaned out settings.
   */
  public function removeDefaultValues(array $js) {
    $config = [];
    $defaults = self::defaultSettings();

    // Remove wasted dependent options if disabled, empty or not.
    if (!$this->optimized) {
      $this->removeWastedDependentOptions($js);
    }

    $config = array_diff_assoc($js, $defaults);

    // Remove empty lazyLoad, or left to default ondemand, to avoid JS error.
    if (empty($config['lazyLoad'])) {
      unset($config['lazyLoad']);
    }

    // Do not pass arrows HTML to JSON object as some are enforced.
    $excludes = [
      'downArrow',
      'downArrowTarget',
      'downArrowOffset',
      'prevArrow',
      'nextArrow',
    ];
    foreach ($excludes as $key) {
      unset($config[$key]);
    }

    // Clean up responsive options if similar to defaults.
    if ($responsives = $this->getResponsiveOptions()) {
      $cleaned = [];
      foreach ($responsives as $key => $responsive) {
        $cleaned[$key]['breakpoint'] = $responsives[$key]['breakpoint'];

        // Destroy responsive slick if so configured.
        if (!empty($responsives[$key]['unslick'])) {
          $cleaned[$key]['settings'] = 'unslick';
          unset($responsives[$key]['unslick']);
        }
        else {
          // Remove wasted dependent options if disabled, empty or not.
          if (!$this->optimized) {
            $this->removeWastedDependentOptions($responsives[$key]['settings']);
          }
          $cleaned[$key]['settings'] = array_diff_assoc($responsives[$key]['settings'], $defaults);
        }
      }
      $config['responsive'] = $cleaned;
    }
    return $config;
  }

  /**
   * Removes wasted dependent options, even if not empty.
   */
  public function removeWastedDependentOptions(array &$js) {
    foreach (self::getDependentOptions() as $key => $option) {
      if (isset($js[$key]) && empty($js[$key])) {
        foreach ($option as $dependent) {
          unset($js[$dependent]);
        }
      }
    }

    if (!empty($js['useCSS']) && !empty($js['cssEaseBezier'])) {
      $js['cssEase'] = $js['cssEaseBezier'];
    }
    unset($js['cssEaseOverride'], $js['cssEaseBezier']);
  }

  /**
   * Defines the dependent options.
   *
   * @return array
   *   The dependent options.
   */
  public static function getDependentOptions() {
    $down_arrow = ['downArrowTarget', 'downArrowOffset'];
    return [
      'arrows'     => ['prevArrow', 'nextArrow', 'downArrow'] + $down_arrow,
      'downArrow'  => $down_arrow,
      'autoplay'   => ['pauseOnHover', 'pauseOnDotsHover', 'autoplaySpeed'],
      'centerMode' => ['centerPadding'],
      'dots'       => ['dotsClass', 'appendDots'],
      'swipe'      => ['swipeToSlide'],
      'useCSS'     => ['cssEase', 'cssEaseBezier', 'cssEaseOverride'],
      'vertical'   => ['verticalSwiping'],
    ];
  }

}
