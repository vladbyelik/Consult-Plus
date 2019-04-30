<?php

namespace Drupal\slick_ui\Form;

use Drupal\slick\Entity\Slick;
use Drupal\slick_ui\Controller\SlickListBuilder;

/**
 * Extends base form for slick instance configuration form.
 */
class SlickForm extends SlickFormBase {

  use SlickListBuilder;

  /**
   * The form elements.
   *
   * @var array
   */
  protected $formElements;

  /**
   * {@inheritdoc}
   */
  public function edit_form(&$form, &$form_state) {
    parent::edit_form($form, $form_state);

    $slick          = $form_state['item'];
    $options        = $slick->getOptions() ?: [];
    $tooltip        = ['class' => ['is-tooltip']];
    $tooltip_bottom = $tooltip + ['data-blazy-tooltip' => 'wide', 'data-blazy-tooltip-direction' => 'bottom'];
    $admin_css      = $this->manager->config('admin_css', TRUE, 'blazy.settings');

    $form['#attributes']['class'][] = 'form--slick form--blazy form--optionset has-tooltip';

    $form['info']['label']['#attributes']['class'][] = 'is-tooltip';
    $form['info']['name']['#attributes']['class'][] = 'is-tooltip';
    $form['info']['label']['#prefix'] = '<div class="form__header-container clearfix"><div class="form__header form__half form__half--first has-tooltip clearfix">';
    $form['info']['name']['#suffix'] = '</div>';

    $form['skin'] = [
      '#type'          => 'select',
      '#title'         => t('Skin'),
      '#options'       => $this->admin->getSkinsByGroupOptions(),
      '#empty_option'  => t('- None -'),
      '#default_value' => isset($form_state['values']['skin']) ? $form_state['values']['skin'] : $slick->skin,
      '#description'   => t('Skins allow swappable layouts like next/prev links, split image and caption, etc. However a combination of skins and options may lead to unpredictable layouts, get yourself dirty. See <b>/admin/help/slick_ui</b> for details on Skins. Only useful for custom work, and ignored/overridden by slick formatters or sub-modules.'),
      '#attributes'    => $tooltip_bottom,
      '#prefix'        => '<div class="form__header form__half form__half--last has-tooltip clearfix">',
    ];

    $collection = isset($slick->collection) ? $slick->collection : '';
    $form['collection'] = [
      '#type'          => 'select',
      '#title'         => t('Collection'),
      '#options'       => [
        'main'      => t('Main'),
        'thumbnail' => t('Thumbnail'),
      ],
      '#empty_option'  => t('- None -'),
      '#default_value' => isset($form_state['values']['collection']) ? $form_state['values']['collection'] : $collection,
      '#description'   => t('Group this optionset to avoid confusion for optionset selections. Leave empty to make it available for all.'),
      '#attributes'    => $tooltip_bottom,
    ];

    $form['breakpoints'] = [
      '#title'         => t('Breakpoints'),
      '#type'          => 'textfield',
      '#default_value' => isset($form_state['values']['breakpoints']) ? $form_state['values']['breakpoints'] : $slick->breakpoints,
      '#description'   => t('The number of breakpoints added to Responsive display, max 9. This is not Breakpoint Width (480px, etc).'),
      '#ajax' => [
        'callback' => 'slick_ui_add_breakpoints',
        'wrapper'  => 'edit-breakpoints-ajax-wrapper',
        'event'    => 'blur',
      ],
      '#attributes' => $tooltip_bottom,
      '#maxlength'  => 1,
    ];

    $optimized = isset($slick->optimized) ? $slick->optimized : '';
    $form['optimized'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Optimized'),
      '#default_value' => isset($form_state['values']['optimized']) ? $form_state['values']['optimized'] : $optimized,
      '#description'   => t('Check to optimize the stored options. Anything similar to defaults will not be stored, except those required by sub-modules and theme_slick(). Like you hand-code/ cherry-pick the needed options, and are smart enough to not repeat defaults, and free up memory. The rest are taken care of by JS. Uncheck only if theme_slick() can not satisfy the needs, and more hand-coded preprocess is needed which is less likely in most cases.'),
      '#access'        => $slick->name != 'default',
      '#attributes'    => $tooltip_bottom,
    ];

    if ($slick->name == 'default') {
      $form['breakpoints']['#suffix'] = '</div></div>';
    }
    else {
      $form['optimized']['#suffix'] = '</div></div>';
    }

    if ($admin_css) {
      $form['optimized']['#field_suffix'] = '&nbsp;';
      $form['optimized']['#title_display'] = 'before';
    }

    // Options.
    $form['options'] = [
      '#type'    => 'vertical_tabs',
      '#tree'    => TRUE,
      '#parents' => ['options'],
    ];

    // Main JS options.
    $form['settings'] = [
      '#type'       => 'fieldset',
      '#tree'       => TRUE,
      '#title'      => t('Settings'),
      '#attributes' => ['class' => ['fieldset--settings', 'has-tooltip']],
      '#group'      => 'options',
      '#parents'    => ['options', 'settings'],
    ];

    foreach ($this->getFormElements() as $name => $element) {
      $element['default'] = isset($element['default']) ? $element['default'] : '';
      $value = isset($slick->options['settings'][$name]) ? $slick->options['settings'][$name] : $element['default'];
      $form['settings'][$name] = [
        '#title'         => isset($element['title']) ? $element['title'] : '',
        '#default_value' => (NULL !== $value) ? $value : $element['default'],
      ];

      if (isset($element['type'])) {
        $form['settings'][$name]['#type'] = $element['type'];
        if ($element['type'] != 'hidden') {
          $form['settings'][$name]['#attributes'] = $tooltip;
        }
        else {
          // Ensures hidden element doesn't screw up the states.
          unset($element['states']);
        }

        if ($element['type'] == 'textfield') {
          $form['settings'][$name]['#size'] = 20;
          $form['settings'][$name]['#maxlength'] = 255;
        }
      }

      if (isset($element['options'])) {
        $form['settings'][$name]['#options'] = $element['options'];
      }

      if (isset($element['empty_option'])) {
        $form['settings'][$name]['#empty_option'] = $element['empty_option'];
      }

      if (isset($element['description'])) {
        $form['settings'][$name]['#description'] = $element['description'];
      }

      if (isset($element['states'])) {
        $form['settings'][$name]['#states'] = $element['states'];
      }

      // Expand textfield for easy edit.
      if (in_array($name, ['prevArrow', 'nextArrow'])) {
        $form['settings'][$name]['#attributes']['class'][] = 'js-expandable';
      }

      if (isset($element['field_suffix'])) {
        $form['settings'][$name]['#field_suffix'] = $element['field_suffix'];
      }

      if (is_int($element['default'])) {
        $form['settings'][$name]['#maxlength'] = 60;
        $form['settings'][$name]['#attributes']['class'][] = 'form-text--int';
      }

      if ($admin_css && !isset($element['field_suffix']) && is_bool($element['default'])) {
        $form['settings'][$name]['#field_suffix'] = '&nbsp;';
        $form['settings'][$name]['#title_display'] = 'before';
      }

      $form['settings'][$name]['#attributes']['data-blazy-form-item'] = drupal_strtolower(str_replace('_', '-', $name));
    }

    // Responsive JS options.
    // https://github.com/kenwheeler/slick/issues/951
    $form['responsives'] = [
      '#type'        => 'fieldset',
      '#title'       => t('Responsive display'),
      '#collapsible' => FALSE,
      '#tree'        => TRUE,
      '#group'       => 'options',
      '#parents'     => ['options', 'responsives'],
      '#description' => t('Containing breakpoints and settings objects. Settings set at a given breakpoint/screen width is self-contained and does not inherit the main settings, but defaults. Be sure to set Breakpoints option above.'),
    ];

    $form['responsives']['responsive'] = [
      '#type'        => 'fieldset',
      '#title'       => t('Responsive'),
      '#collapsible' => FALSE,
      '#group'       => 'responsives',
      '#parents'     => ['options', 'responsives', 'responsive'],
      '#prefix'      => '<div id="edit-breakpoints-ajax-wrapper">',
      '#suffix'      => '</div>',
      '#attributes'  => ['class' => ['has-tooltip', 'fieldset--responsive--ajax']],
    ];

    // Add some information to the form state for easier form altering.
    $breakpoints_count = isset($form_state['values']['breakpoints']) ? $form_state['values']['breakpoints'] : $slick->breakpoints;
    $form_state['breakpoints_count'] = $breakpoints_count;

    if ($form_state['breakpoints_count'] > 0) {
      $slick_responsive_options = $this->getResponsiveFormElements($form_state['breakpoints_count']);

      foreach ($slick_responsive_options as $i => $responsives) {
        // Individual breakpoint fieldset depends on the breakpoints amount.
        $form['responsives']['responsive'][$i] = [
          '#type'        => $responsives['type'],
          '#title'       => $responsives['title'],
          '#collapsible' => TRUE,
          '#collapsed'   => TRUE,
          '#group'      => 'responsive',
          '#attributes' => [
            'class' => [
              'fieldset--responsive',
              'fieldset--breakpoint-' . $i,
              'has-tooltip',
            ],
          ],
        ];

        unset($responsives['title'], $responsives['type']);
        foreach ($responsives as $key => $responsive) {
          switch ($key) {
            case 'breakpoint':
            case 'unslick':
              $form['responsives']['responsive'][$i][$key] = [
                '#type'          => $responsive['type'],
                '#title'         => $responsive['title'],
                '#default_value' => isset($options['responsives']['responsive'][$i][$key]) ? $options['responsives']['responsive'][$i][$key] : $responsive['default'],
                '#description'   => $responsive['description'],
                '#attributes'    => $tooltip,
              ];

              if ($responsive['type'] == 'textfield') {
                $form['responsives']['responsive'][$i][$key]['#size'] = 20;
                $form['responsives']['responsive'][$i][$key]['#maxlength'] = 255;
              }

              if (is_int($responsive['default'])) {
                $form['responsives']['responsive'][$i][$key]['#maxlength'] = 60;
              }

              if (isset($responsive['field_suffix'])) {
                $form['responsives']['responsive'][$i][$key]['#field_suffix'] = $responsive['field_suffix'];
              }

              if ($admin_css && !isset($responsive['field_suffix']) && $responsive['type'] == 'checkbox') {
                $form['responsives']['responsive'][$i][$key]['#field_suffix'] = '&nbsp;';
                $form['responsives']['responsive'][$i][$key]['#title_display'] = 'before';
              }
              break;

            case 'settings':
              $form['responsives']['responsive'][$i][$key] = [
                '#type'       => $responsive['type'],
                '#title'      => $responsive['title'],
                '#open'       => TRUE,
                '#group'      => $i,
                '#states'     => ['visible' => [':input[name*="[responsive][' . $i . '][unslick]"]' => ['checked' => FALSE]]],
                '#attributes' => [
                  'class' => [
                    'fieldset--settings',
                    'fieldset--breakpoint-' . $i,
                    'has-tooltip',
                  ],
                ],
              ];

              unset($responsive['title'], $responsive['type']);

              // @fixme, boolean default is ignored at index 0 only.
              foreach ($responsive as $k => $item) {
                $item['default'] = isset($item['default']) ? $item['default'] : '';
                $form['responsives']['responsive'][$i][$key][$k] = [
                  '#title'         => isset($item['title']) ? $item['title'] : '',
                  '#default_value' => isset($options['responsives']['responsive'][$i][$key][$k]) ? $options['responsives']['responsive'][$i][$key][$k] : $item['default'],
                  '#description'   => isset($item['description']) ? $item['description'] : '',
                  '#attributes'    => $tooltip,
                ];

                if (isset($item['type'])) {
                  $form['responsives']['responsive'][$i][$key][$k]['#type'] = $item['type'];
                }

                // Specify proper states for the breakpoint form elements.
                if (isset($item['states'])) {
                  $states = '';
                  switch ($k) {
                    case 'pauseOnHover':
                    case 'pauseOnDotsHover':
                    case 'autoplaySpeed':
                      $states = ['visible' => [':input[name*="[' . $i . '][settings][autoplay]"]' => ['checked' => TRUE]]];
                      break;

                    case 'centerPadding':
                      $states = ['visible' => [':input[name*="[' . $i . '][settings][centerMode]"]' => ['checked' => TRUE]]];
                      break;

                    case 'touchThreshold':
                      $states = ['visible' => [':input[name*="[' . $i . '][settings][touchMove]"]' => ['checked' => TRUE]]];
                      break;

                    case 'swipeToSlide':
                      $states = ['visible' => [':input[name*="[' . $i . '][settings][swipe]"]' => ['checked' => TRUE]]];
                      break;

                    case 'verticalSwiping':
                      $states = ['visible' => [':input[name*="[' . $i . '][settings][vertical]"]' => ['checked' => TRUE]]];
                      break;
                  }

                  if ($states) {
                    $form['responsives']['responsive'][$i][$key][$k]['#states'] = $states;
                  }
                }

                if (isset($item['options'])) {
                  $form['responsives']['responsive'][$i][$key][$k]['#options'] = $item['options'];
                }

                if (isset($item['empty_option'])) {
                  $form['responsives']['responsive'][$i][$key][$k]['#empty_option'] = $item['empty_option'];
                }

                if (isset($item['field_suffix'])) {
                  $form['responsives']['responsive'][$i][$key][$k]['#field_suffix'] = $item['field_suffix'];
                }

                if ($admin_css && !isset($item['field_suffix']) && is_bool($item['default'])) {
                  $form['responsives']['responsive'][$i][$key][$k]['#field_suffix'] = '&nbsp;';
                  $form['responsives']['responsive'][$i][$key][$k]['#title_display'] = 'before';
                }

                $form['responsives']['responsive'][$i][$key][$k]['#attributes']['data-blazy-form-item'] = drupal_strtolower(str_replace('_', '-', $k));
              }
              break;

            default:
              break;
          }
        }
      }
    }

    // Attach Slick admin library.
    if ($admin_css) {
      $form['#attached']['library'][] = ['slick_ui', 'ui'];
    }
  }

  /**
   * Defines available options for the main and responsive settings.
   *
   * @return array
   *   All available Slick options.
   *
   * @see http://kenwheeler.github.io/slick
   */
  public function getFormElements() {
    if (!isset($this->formElements)) {
      $elements = [];

      $elements['mobileFirst'] = [
        'type'        => 'checkbox',
        'title'       => t('Mobile first'),
        'description' => t('Responsive settings use mobile first calculation, or equivalent to min-width query.'),
      ];

      $elements['asNavFor'] = [
        'type'        => 'textfield',
        'title'       => t('asNavFor target'),
        'description' => t('Leave empty if using sub-modules to have it auto-matched. Set the slider to be the navigation of other slider (Class or ID Name). Use selector identifier ("." or "#") accordingly. See HTML structure section at <b>/admin/help/slick_ui</b> for more info. Overriden by field formatter, or Views style.'),
      ];

      $elements['accessibility'] = [
        'type'        => 'checkbox',
        'title'       => t('Accessibility'),
        'description' => t('Enables tabbing and arrow key navigation.'),
      ];

      $elements['adaptiveHeight'] = [
        'type'        => 'checkbox',
        'title'       => t('Adaptive height'),
        'description' => t('Enables adaptive height for SINGLE slide horizontal carousels. This is useless with variableWidth.'),
      ];

      $elements['autoplay'] = [
        'type'        => 'checkbox',
        'title'       => t('Autoplay'),
        'description' => t('Enables autoplay.'),
      ];

      $elements['autoplaySpeed'] = [
        'type'        => 'textfield',
        'title'       => t('Autoplay speed'),
        'description' => t('Autoplay speed in milliseconds.'),
      ];

      $elements['pauseOnHover'] = [
        'type'        => 'checkbox',
        'title'       => t('Pause on hover'),
        'description' => t('Pause autoplay on hover.'),
      ];

      $elements['pauseOnDotsHover'] = [
        'type'        => 'checkbox',
        'title'       => t('Pause on dots hover'),
        'description' => t('Pause autoplay when a dot is hovered.'),
      ];

      $elements['arrows'] = [
        'type'        => 'checkbox',
        'title'       => t('Arrows'),
        'description' => t('Show prev/next arrows.'),
      ];

      $elements['prevArrow'] = [
        'type'        => 'textfield',
        'title'       => t('Previous arrow'),
        'description' => t("Customize the previous arrow markups. Be sure to keep the expected class: slick-prev."),
      ];

      $elements['nextArrow'] = [
        'type'        => 'textfield',
        'title'       => t('Next arrow'),
        'description' => t("Customize the next arrow markups. Be sure to keep the expected class: slick-next."),
      ];

      $elements['downArrow'] = [
        'type'        => 'checkbox',
        'title'       => t('Use arrow down'),
        'description' => t('Arrow down to scroll down into a certain page section. Be sure to provide its target selector.'),
      ];

      $elements['downArrowTarget'] = [
        'type'        => 'textfield',
        'title'       => t('Arrow down target'),
        'description' => t('Valid CSS selector to scroll to, e.g.: #main, or #content.'),
      ];

      $elements['downArrowOffset'] = [
        'type'         => 'textfield',
        'title'        => t('Arrow down offset'),
        'description'  => t('Offset when scrolled down from the top.'),
        'field_suffix' => 'px',
      ];

      $elements['centerMode'] = [
        'type'        => 'checkbox',
        'title'       => t('Center mode'),
        'description' => t('Enables centered view with partial prev/next slides. Use with odd numbered slidesToShow counts.'),
      ];

      $elements['centerPadding'] = [
        'type'        => 'textfield',
        'title'       => t('Center padding'),
        'description' => t('Side padding when in center mode (px or %). Be aware, too large padding at small breakpoint will screw the slide calculation with slidesToShow.'),
      ];

      $elements['dots'] = [
        'type'        => 'checkbox',
        'title'       => t('Dots'),
        'description' => t('Show dot indicators.'),
      ];

      $elements['dotsClass'] = [
        'type'        => 'textfield',
        'title'       => t('Dot class'),
        'description' => t('Class for slide indicator dots container. Do not prefix with a dot (.). If you change this, edit its CSS accordingly.'),
      ];

      $elements['appendDots'] = [
        'type'        => 'textfield',
        'title'       => t('Append dots'),
        'description' => t('Change where the navigation dots are attached (Selector, htmlString). If you change this, be sure to provide its relevant markup. Try <strong>.slick__arrow</strong> to achieve this style: <br />&lt; o o o o o o o &gt;<br />Be sure to enable Arrows in such a case.'),
      ];

      $elements['draggable'] = [
        'type'        => 'checkbox',
        'title'       => t('Draggable'),
        'description' => t('Enable mouse dragging.'),
      ];

      $elements['fade'] = [
        'type'        => 'checkbox',
        'title'       => t('Fade'),
        'description' => t('Enable fade. Warning! This wants slidesToShow 1. Larger than 1, and Slick may be screwed up.'),
      ];

      $elements['focusOnSelect'] = [
        'type'        => 'checkbox',
        'title'       => t('Focus on select'),
        'description' => t('Enable focus on selected element (click).'),
      ];

      $elements['infinite'] = [
        'type'        => 'checkbox',
        'title'       => t('Infinite'),
        'description' => t('Infinite loop sliding. Will create clones which may result in lightbox images being duplicated.'),
      ];

      $elements['initialSlide'] = [
        'type'        => 'textfield',
        'title'       => t('Initial slide'),
        'description' => t('Slide to start on.'),
      ];

      $elements['lazyLoad'] = [
        'type'         => 'select',
        'title'        => t('Lazy load'),
        'options'      => $this->getLazyloadOptions(),
        'empty_option' => t('- None -'),
        'description'  => t("Set lazy loading technique. Ondemand will load the image as soon as you slide to it. Progressive loads one image after the other when the page loads. Anticipated preloads images, and requires Slick 1.6.1+. To share images for Pinterest, leave empty, otherwise no way to read actual image src. It supports Blazy module to delay loading below-fold images until 100px before they are visible at viewport, and/or have a bonus lazyLoadAhead when the beforeChange event fired.", ['@url' => '//www.drupal.org/project/imageinfo_cache']),
      ];

      $elements['mouseWheel'] = [
        'type'        => 'checkbox',
        'title'       => t('Enable mousewheel'),
        'description' => t('Be sure to download the <a href="@mousewheel" target="_blank">mousewheel</a> library, and it is available at <em>/sites/.../libraries/mousewheel/jquery.mousewheel.min.js</em>.', ['@mousewheel' => '//github.com/brandonaaron/jquery-mousewheel']),
      ];

      $elements['randomize'] = [
        'type'        => 'checkbox',
        'title'       => t('Randomize'),
        'description' => t('Randomize the slide display, useful to manipulate cached blocks.'),
      ];

      $responds = ['window', 'slider', 'min'];
      $elements['respondTo'] = [
        'type'        => 'select',
        'title'       => t('Respond to'),
        'description' => t("Width that responsive object responds to. Can be 'window', 'slider' or 'min' (the smaller of the two)."),
        'options'     => array_combine($responds, $responds),
      ];

      $elements['rows'] = [
        'type'        => 'textfield',
        'title'       => t('Rows'),
        'description' => t("Setting this to more than 1 initializes grid mode. Use slidesPerRow to set how many slides should be in each row."),
      ];

      $elements['slidesPerRow'] = [
        'type'        => 'textfield',
        'title'       => t('Slides per row'),
        'description' => t("With grid mode intialized via the rows option, this sets how many slides are in each grid row."),
      ];

      $elements['slide'] = [
        'type'        => 'textfield',
        'title'       => t('Slide element'),
        'description' => t("Element query to use as slide. Slick will use any direct children as slides, without having to specify which tag or selector to target."),
      ];

      $elements['slidesToShow'] = [
        'type'        => 'textfield',
        'title'       => t('Slides to show'),
        'description' => t('Number of slides to show at a time. If 1, it will behave like slideshow, more than 1 a carousel. Provide more if it is a thumbnail navigation with asNavFor. Only works with odd number slidesToShow counts when using centerMode (e.g.: 3, 5, 7, etc.). Not-compatible with variableWidth.'),
      ];

      $elements['slidesToScroll'] = [
        'type'        => 'textfield',
        'title'       => t('Slides to scroll'),
        'description' => t('Number of slides to scroll at a time, or steps at each scroll.'),
      ];

      $elements['speed'] = [
        'type'         => 'textfield',
        'title'        => t('Speed'),
        'description'  => t('Slide/Fade animation speed in milliseconds.'),
        'field_suffix' => 'ms',
      ];

      $elements['swipe'] = [
        'type'        => 'checkbox',
        'title'       => t('Swipe'),
        'description' => t('Enable swiping.'),
      ];

      $elements['swipeToSlide'] = [
        'type'        => 'checkbox',
        'title'       => t('Swipe to slide'),
        'description' => t('Allow users to drag or swipe directly to a slide irrespective of slidesToScroll.'),
      ];

      $elements['edgeFriction'] = [
        'type'        => 'textfield',
        'title'       => t('Edge friction'),
        'description' => t("Resistance when swiping edges of non-infinite carousels. If you don't want resistance, set it to 1. Default: 0.35."),
      ];

      $elements['touchMove'] = [
        'type'        => 'checkbox',
        'title'       => t('Touch move'),
        'description' => t('Enable slide motion with touch.'),
      ];

      $elements['touchThreshold'] = [
        'type'        => 'textfield',
        'title'       => t('Touch threshold'),
        'description' => t('Swipe distance threshold. Default: 5.'),
      ];

      $elements['useCSS'] = [
        'type'        => 'checkbox',
        'title'       => t('Use CSS'),
        'description' => t('Enable/disable CSS transitions.'),
      ];

      $elements['cssEase'] = [
        'type'        => 'textfield',
        'title'       => t('CSS ease'),
        'description' => t('CSS3 animation easing. <a href="@ceaser">Learn</a> <a href="@bezier">more</a>. Ignored if <strong>CSS ease override</strong> is provided.', ['@ceaser' => '//matthewlein.com/ceaser/', '@bezier' => '//cubic-bezier.com']),
      ];

      $elements['cssEaseBezier'] = [
        'type'        => 'hidden',
      ];

      $elements['cssEaseOverride'] = [
        'title'        => t('CSS ease override'),
        'type'         => 'select',
        'options'      => $this->getCssEasingOptions(),
        'empty_option' => t('- None -'),
        'description'  => t('If provided, this will override the CSS ease with the pre-defined CSS easings based on <a href="@ceaser">CSS Easing Animation Tool</a>. This field will stay empty. Leave it empty to use your own CSS ease.', ['@ceaser' => 'http://matthewlein.com/ceaser/']),
      ];

      $elements['useTransform'] = [
        'type'        => 'checkbox',
        'title'       => t('Use CSS Transforms'),
        'description' => t('Enable/disable CSS transforms.'),
      ];

      $elements['easing'] = [
        'title'        => t('jQuery easing'),
        'type'         => 'select',
        'options'      => $this->getJsEasingOptions(),
        'empty_option' => t('- None -'),
        'description'  => t('Add easing for jQuery animate as fallback. Use with <a href="@easing">easing</a> libraries or default easing methods. Optionally install <a href="@jqeasing">jqeasing module</a>. This will be ignored and replaced by CSS ease for supporting browsers, or effective if useCSS is disabled.', ['@jqeasing' => '//drupal.org/project/jqeasing', '@easing' => '//gsgd.co.uk/sandbox/jquery/easing/']),
      ];

      $elements['variableWidth'] = [
        'type'        => 'checkbox',
        'title'       => t('Variable width'),
        'description' => t('Disables automatic slide width calculation. Best with uniform image heights, use scale height image effect. Useless with adaptiveHeight, and non-uniform image heights. Useless with slidesToShow > 1 if the container is smaller than the amount of visible slides. Troubled with lazyLoad ondemand.'),
      ];

      $elements['vertical'] = [
        'type'        => 'checkbox',
        'title'       => t('Vertical'),
        'description' => t('Vertical slide direction. See <a href="@url" target="_blank">relevant issue</a>.', ['@url' => '//github.com/kenwheeler/slick/issues/1001']),
      ];

      $elements['verticalSwiping'] = [
        'type'        => 'checkbox',
        'title'       => t('Vertical swiping'),
        'description' => t('Changes swipe direction to vertical.'),
      ];

      $elements['waitForAnimate'] = [
        'type'        => 'checkbox',
        'title'       => t('Wait for animate'),
        'description' => t('Ignores requests to advance the slide while animating.'),
      ];

      // Defines the default values if available.
      $defaults = Slick::defaultSettings();
      foreach ($elements as $name => $element) {
        $default = $element['type'] == 'checkbox' ? FALSE : '';
        $default = isset($defaults[$name]) ? $defaults[$name] : $default;
        $elements[$name]['default'] = $default;
        if (isset($elements[$name]['description'])) {
          $default_printed = $default;
          if (is_bool($default)) {
            $default_printed = $default ? 'TRUE' : 'FALSE';
          }
          elseif (is_string($default) && empty($default)) {
            $default_printed = '" "';
          }
          // No need to translate this useless default values.
          $elements[$name]['description'] .= '<br>Default: <b>' . $default_printed . '.</b>';
        }
      }

      foreach (Slick::getDependentOptions() as $parent => $items) {
        foreach ($items as $name) {
          if (isset($elements[$name])) {
            $states = ['visible' => [':input[name*="options[settings][' . $parent . ']"]' => ['checked' => TRUE]]];
            if (!isset($elements[$name]['states'])) {
              $elements[$name]['states'] = $states;
            }
            else {
              $elements[$name]['states'] = array_merge($elements[$name]['states'], $states);
            }
          }
        }
      }

      $this->formElements = $elements;
    }

    return $this->formElements;
  }

  /**
   * Removes problematic options for the responsive Slick.
   *
   * The problematic options are those that should exist once for a given Slick
   *   instance, or no easy way to deal with in the responsive context.
   *   JS takes care of the relevant copy on each responsive setting instead.
   *
   * @return array
   *   An array of cleaned out options.
   */
  public function cleanFormElements() {
    $excludes = [
      'accessibility',
      'appendArrows',
      'appendDots',
      'asNavFor',
      'dotsClass',
      'downArrow',
      'downArrowTarget',
      'downArrowOffset',
      'easing',
      'lazyLoad',
      'mobileFirst',
      'mouseWheel',
      'nextArrow',
      'prevArrow',
      'randomize',
      'slide',
      'useCSS',
      'useTransform',
    ];
    return array_diff_key($this->getFormElements(), array_combine($excludes, $excludes));
  }

  /**
   * Defines available options for the responsive Slick.
   *
   * @param int $count
   *   The number of breakpoints.
   *
   * @return array
   *   An array of Slick responsive options.
   */
  public function getResponsiveFormElements($count = 0) {
    $elements = [];
    $range = range(0, ($count - 1));
    $breakpoints = array_combine($range, $range);

    foreach ($breakpoints as $key => $breakpoint) {
      $elements[$key] = [
        'type'  => 'fieldset',
        'title' => t('Breakpoint #@key', ['@key' => ($key + 1)]),
      ];

      $elements[$key]['breakpoint'] = [
        'type'         => 'textfield',
        'title'        => t('Breakpoint'),
        'description'  => t('Breakpoint width in pixel. If mobileFirst enabled, equivalent to min-width, otherwise max-width.'),
        'default'      => '',
        'field_suffix' => 'px',
      ];

      $elements[$key]['unslick'] = [
        'type'        => 'checkbox',
        'title'       => t('Unslick'),
        'description' => t("Disable Slick at a given breakpoint. Note, you can't window shrink this, once you unslick, you are unslicked."),
        'default'     => FALSE,
      ];

      $elements[$key]['settings'] = [
        'type'  => 'fieldset',
        'title' => t('Settings'),
      ];

      // Duplicate relevant main settings.
      foreach ($this->cleanFormElements() as $name => $responsive) {
        $elements[$key]['settings'][$name] = $responsive;
      }
    }
    return $elements;
  }

  /**
   * Returns modifiable lazyload options.
   */
  public function getLazyloadOptions() {
    $options = [
      'anticipated' => t('Anticipated'),
      'blazy'       => t('Blazy'),
      'ondemand'    => t('On demand'),
      'progressive' => t('Progressive'),
    ];

    drupal_alter('slick_lazyload_options_info', $options);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function edit_form_validate(&$form, &$form_state) {
    parent::edit_form_validate($form, $form_state);

    // Update CSS Bezier version.
    $override = $form_state['values']['options']['settings']['cssEaseOverride'];
    if ($override) {
      $override = $this->getBezier($override);
    }

    // Update cssEaseBezier value based on cssEaseOverride.
    $form_state['values']['options']['settings']['cssEaseBezier'] = $override;
  }

  /**
   * {@inheritdoc}
   */
  public function edit_form_submit(&$form, &$form_state) {
    parent::edit_form_submit($form, $form_state);

    // Optimized if so configured.
    $slick = $form_state['item'];
    $default = $slick->name == 'default';
    if ($default) {
      return;
    }

    $defaults = Slick::defaultSettings();
    $required = $this->getOptionsRequiredByTemplate();
    $settings = $form_state['values']['options']['settings'];
    $optimized = $form_state['values']['optimized'];

    // Cast the values.
    Slick::typecast($settings);

    $main_settings = $settings;
    if ($optimized) {
      // Remove wasted dependent options if disabled, empty or not.
      $slick->removeWastedDependentOptions($settings);
      $main = array_diff_assoc($defaults, $required);
      $main_settings = array_diff_assoc($settings, $main);
    }

    $slick->setSettings($main_settings);

    if (isset($form_state['values']['options']['responsives'])
      && $responsives = $form_state['values']['options']['responsives']['responsive']) {
      foreach ($responsives as $delta => &$responsive) {

        settype($responsive['breakpoint'], 'int');
        settype($responsive['unslick'], 'bool');

        if (!empty($responsive['unslick'])) {
          $slick->setResponsiveSettings([], $delta);
        }
        else {
          Slick::typecast($responsive['settings']);

          $responsive_settings = $responsive['settings'];
          if ($optimized) {
            $slick->removeWastedDependentOptions($responsive['settings']);
            $responsive_settings = array_diff_assoc($responsive['settings'], $defaults);
          }

          $slick->setResponsiveSettings($responsive_settings, $delta);
          $slick->setResponsiveSettings($responsive['breakpoint'], $delta, 'breakpoint');
          $slick->setResponsiveSettings($responsive['unslick'], $delta, 'unslick');
        }
      }
    }
  }

}
