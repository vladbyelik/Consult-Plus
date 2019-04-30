<?php

namespace Drupal\blazy\Form;

use Drupal\blazy\BlazyManagerInterface;

/**
 * A base for blazy admin integration to have re-usable methods in one place.
 *
 * @see \Drupal\gridstack\Form\GridStackAdmin
 * @see \Drupal\mason\Form\MasonAdmin
 * @see \Drupal\slick\Form\SlickAdmin
 * @see \Drupal\blazy\Form\BlazyAdminFormatterBase
 */
abstract class BlazyAdminBase implements BlazyAdminInterface {

  /**
   * A state that represents the responsive image style is disabled.
   */
  const STATE_RESPONSIVE_IMAGE_STYLE_DISABLED = 0;

  /**
   * A state that represents the media switch lightbox is enabled.
   */
  const STATE_LIGHTBOX_ENABLED = 1;

  /**
   * A state that represents the media switch iframe is enabled.
   */
  const STATE_IFRAME_ENABLED = 2;

  /**
   * A state that represents the thumbnail style is enabled.
   */
  const STATE_THUMBNAIL_STYLE_ENABLED = 3;

  /**
   * A state that represents the custom lightbox caption is enabled.
   */
  const STATE_LIGHTBOX_CUSTOM = 4;

  /**
   * A state that represents the image rendered switch is enabled.
   */
  const STATE_IMAGE_RENDERED_ENABLED = 5;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $manager;

  /**
   * The available view modes.
   *
   * @var array
   */
  protected $viewModeOptions;

  /**
   * Constructs a BlazyAdmin instance.
   */
  public function __construct(BlazyManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * Returns the blazy manager.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Returns shared form elements across field formatter and Views.
   */
  public function openingForm(array &$form, &$definition = []) {
    drupal_alter('blazy_form_element_definition', $definition);
    $forms = isset($definition['forms']) ? $definition['forms'] : [];

    // Display style: column, plain static grid, slick grid, slick carousel.
    // https://drafts.csswg.org/css-multicol
    if (!empty($forms['grid'])) {
      $form['style'] = [
        '#type'         => 'select',
        '#title'        => t('Display style'),
        '#description'  => t('Either <strong>CSS3 Columns</strong> (experimental pure CSS Masonry) or <strong>Grid Foundation</strong> requires <strong>Grid</strong>. Difference: <strong>Columns</strong> is best with irregular image sizes (scale width, empty height), affects the natural order of grid items. <strong>Grid</strong> with regular cropped ones. Unless required, leave empty to use default formatter, or style.'),
        '#enforced'     => TRUE,
        '#options'      => [
          'column' => t('CSS3 Columns'),
          'grid'   => t('Grid Foundation'),
        ],
        '#weight'       => -112,
        '#attributes'   => ['data-blazy-tooltip-direction' => 'bottom', 'data-blazy-form-item' => 'style'],
        '#required'     => !empty($definition['grid_required']),
      ];
    }

    if (!empty($definition['skins'])) {
      $form['skin'] = [
        '#type'        => 'select',
        '#title'       => t('Skin'),
        '#options'     => $definition['skins'],
        '#enforced'    => TRUE,
        '#description' => t('Skins allow various layouts with just CSS. Some options below depend on a skin. Leave empty to DIY. Or use the provided hook_info() and implement the skin interface to register ones.'),
        '#weight'      => -107,
      ];
    }

    if (!empty($definition['background'])) {
      $form['background'] = [
        '#type'        => 'checkbox',
        '#title'       => t('Use CSS background'),
        '#description' => t('Check this to turn the image into CSS background. This opens up the goodness of CSS, such as background cover, fixed attachment, etc. <br /><strong>Important!</strong> Requires a consistent Aspect ratio, otherwise collapsed containers. Unless a min-height is added manually to <strong>.media--background</strong> selector. Not compatible with Picture image.'),
        '#weight'      => -98,
      ];
    }

    if (!empty($definition['layouts'])) {
      $form['layout'] = [
        '#type'        => 'select',
        '#title'       => t('Layout'),
        '#options'     => $definition['layouts'],
        '#description' => t('Requires a skin. The builtin layouts affects the entire items uniformly. Leave empty to DIY.'),
        '#weight'      => 2,
      ];
    }

    if (!empty($definition['captions'])) {
      $form['caption'] = [
        '#type'        => 'checkboxes',
        '#title'       => t('Caption fields'),
        '#options'     => $definition['captions'],
        '#description' => t('Enable any of the following fields as captions. These fields are treated and wrapped as captions.'),
        '#weight'      => 80,
        '#attributes'  => ['class' => ['form-wrapper--caption'], 'data-blazy-form-item' => 'caption'],
      ];

      if ($this->showAltTitleFieldHint($definition)) {
        $form['caption']['#description'] = $this->showAltTitleFieldHint($definition);
      }
    }

    if (!empty($definition['target_type']) && !empty($definition['use_view_mode'])) {
      $form['view_mode'] = $this->baseForm($definition)['view_mode'];
    }

    $weight = -99;
    foreach (element_children($form) as $key) {
      if (!isset($form[$key]['#weight'])) {
        $form[$key]['#weight'] = ++$weight;
      }
    }
  }

  /**
   * Defines re-usable breakpoints form.
   *
   * @see https://html.spec.whatwg.org/multipage/embedded-content.html#attr-img-srcset
   * @see http://ericportis.com/posts/2014/srcset-sizes/
   * @see http://www.sitepoint.com/how-to-build-responsive-images-with-srcset/
   */
  public function breakpointsForm(array &$form, $definition = []) {
    $settings = isset($definition['settings']) ? $definition['settings'] : [];
    $title    = t('Leave Breakpoints empty to disable multi-serving images. <small>If provided, Blazy lazyload applies. Ignored if Picture is provided.<br /> If only two is needed, simply leave the rest empty. At any rate, the last should target the largest monitor. <br>Choose an <b>Aspect ratio</b> and use an image effect with <b>CROP</b> in its name for all styles for best performance. <br>It uses <strong>max-width</strong>, not <strong>min-width</strong>.</small>');

    $form['sizes'] = [
      '#type'        => 'textfield',
      '#title'       => t('Sizes'),
      '#description' => t('E.g.: (min-width: 1290px) 1290px, 100vw. Use sizes to implement different size image (different height, width) on different screen sizes along with the <strong>w (width)</strong> descriptor below. Ignored by Picture.'),
      '#weight'      => 114,
      '#attributes'  => ['class' => ['form-text--sizes']],
      '#prefix'      => '<h2 class="form__title form__title--breakpoints">' . $title . '</h2>',
    ];

    $breakpoints = $this->breakpointElements($definition);
    $headers = [
      t('Breakpoint'),
      t('Image style'),
      t('Max-width/Descriptor'),
    ];

    $form['breakpoints'] = [
      '#type'       => 'container',
      '#tree'       => TRUE,
      '#header'     => $headers,
      '#attributes' => ['class' => ['form-wrapper--table', 'form-wrapper--table-breakpoints']],
      '#weight'     => 115,
      '#enforced'   => TRUE,
    ];

    $form['breakpoints']['header'] = [
      '#type'       => 'container',
      '#attributes' => ['class' => ['form-wrapper--table-header']],
    ];

    foreach ($headers as $header) {
      $form['breakpoints']['header'][$header] = [
        '#type'   => 'item',
        '#markup' => $header,
      ];
    }

    foreach ($breakpoints as $breakpoint => $elements) {
      foreach ($elements as $key => &$element) {
        if ($key != 'breakpoint') {
          $value = isset($settings['breakpoints'][$breakpoint][$key]) ? $settings['breakpoints'][$breakpoint][$key] : '';
          $element['#default_value'] = $value;
        }
        $form['breakpoints'][$breakpoint][$key] = $element;
      }
    }
  }

  /**
   * Defines re-usable breakpoints form.
   */
  public function breakpointElements($definition = []) {
    foreach ($definition['breakpoints'] as $breakpoint) {
      $form[$breakpoint]['breakpoint'] = [
        '#type'   => 'item',
        '#markup' => $breakpoint,
        '#weight' => 1,
      ];

      // Regular #empty_option is not working with rendered.
      $form[$breakpoint]['image_style'] = [
        '#type'          => 'select',
        '#title'         => t('Image style'),
        '#title_display' => 'invisible',
        '#options'       => ['' => t('- None -')] + image_style_options(FALSE),
        '#weight'        => 2,
      ];

      $form[$breakpoint]['width'] = [
        '#type'          => 'textfield',
        '#title'         => t('Width'),
        '#title_display' => 'invisible',
        '#description'   => t('See <strong>XS</strong> for detailed info.'),
        '#maz_length'    => 32,
        '#size'          => 6,
        '#weight'        => 3,
        '#attributes'    => ['class' => ['form-text--width']],
      ];

      if ($breakpoint == 'xs') {
        $form[$breakpoint]['width']['#description'] = t('E.g.: <strong>640</strong>, or <strong>2x</strong>, or for <strong>small devices</strong> may be combined into <strong>640w 2x</strong> where <strong>x (pixel density)</strong> descriptor is used to define the device-pixel ratio, and <strong>w (width)</strong> descriptor is the width of image source and works in tandem with <strong>sizes</strong> attributes. Use <strong>w (width)</strong> if any issue/ unsure. Default to <strong>w</strong> if no descriptor provided for backward compatibility.');
      }
    }

    return $form;
  }

  /**
   * Returns re-usable grid elements across field formatter and Views.
   */
  public function gridForm(array &$form, $definition = []) {
    $range = range(1, 12);
    $grid_options = array_combine($range, $range);
    $required = !empty($definition['grid_required']);

    $header = t('Group individual items as block grid<small>Depends on the <strong>Display style</strong>.</small>');
    $form['grid_header'] = [
      '#type'   => 'item',
      '#markup' => '<h3 class="form__title form__title--grid">' . $header . '</h3>',
      '#access' => !$required,
    ];

    if ($required) {
      $description = t('The amount of block grid columns for large monitors 64.063em.');
    }
    else {
      $description = t('Select <strong>- None -</strong> first if trouble with changing form states. The amount of block grid columns for large monitors 64.063em+. <br /><strong>Requires</strong>:<ol><li>Visible items,</li><li>Skin Grid for starter,</li><li>A reasonable amount of contents.</li></ol>Leave empty to DIY, or to not build grids.');
    }
    $form['grid'] = [
      '#type'        => 'select',
      '#title'       => t('Grid large'),
      '#options'     => $grid_options,
      '#description' => $description,
      '#enforced'    => TRUE,
      '#required'    => $required,
    ];

    $form['grid_medium'] = [
      '#type'        => 'select',
      '#title'       => t('Grid medium'),
      '#options'     => $grid_options,
      '#description' => t('The amount of block grid columns for medium devices 40.063em - 64em.'),
    ];

    $form['grid_small'] = [
      '#type'        => 'select',
      '#title'       => t('Grid small'),
      '#options'     => $grid_options,
      '#description' => t('The amount of block grid columns for small devices 0 - 40em. Specific to <strong>CSS3 Columns</strong>, only 1 - 2 column is respected due to small real estate at smallest device.'),
    ];

    $form['visible_items'] = [
      '#type'        => 'select',
      '#title'       => t('Visible items'),
      '#options'     => array_combine(range(1, 32), range(1, 32)),
      '#description' => t('How many items per display at a time.'),
    ];

    $form['preserve_keys'] = [
      '#type'        => 'checkbox',
      '#title'       => t('Preserve keys'),
      '#description' => t('If checked, keys will be preserved. Default is FALSE which will reindex the grid chunk numerically.'),
      '#access'      => FALSE,
    ];

    $grids = [
      'grid_header',
      'grid_medium',
      'grid_small',
      'visible_items',
      'preserve_keys',
    ];

    foreach ($grids as $key) {
      $form[$key]['#enforced'] = TRUE;
      $form[$key]['#states'] = [
        'visible' => [
          'select[name$="[grid]"]' => ['!value' => ''],
        ],
      ];
    }
  }

  /**
   * Returns shared ending form elements across field formatter and Views.
   */
  public function closingForm(array &$form, $definition = []) {
    $form['current_view_mode'] = [
      '#type'          => 'hidden',
      '#default_value' => isset($definition['settings']['current_view_mode']) ? $definition['settings']['current_view_mode'] : 'default',
      '#weight'        => 120,
    ];

    $this->finalizeForm($form, $definition);
  }

  /**
   * Returns simple form elements common for Views field, EB widget, formatters.
   */
  public function baseForm($definition = []) {
    $settings      = isset($definition['settings']) ? $definition['settings'] : [];
    $lightboxes    = $this->manager()->getLightboxes();
    $image_styles  = image_style_options(FALSE);
    $is_responsive = function_exists('picture_get_mapping_options') && !empty($definition['responsive_image']);

    $form = [];
    if (empty($definition['no_image_style'])) {
      $form['image_style'] = [
        '#type'        => 'select',
        '#title'       => t('Image style'),
        '#options'     => $image_styles,
        '#description' => t('The content image style. This will be treated as the fallback image, which is normally smaller, if Breakpoints are provided. Otherwise this is the only image displayed.'),
        '#weight'      => -101,
      ];
    }

    if (isset($settings['media_switch'])) {
      $form['media_switch'] = [
        '#type'         => 'select',
        '#title'        => t('Media switcher'),
        '#options'      => [
          'content' => t('Image linked to content'),
        ],
        '#empty_option' => t('- None -'),
        '#description'  => t('May depend on the enabled supported or supportive modules: media, colorbox, photobox, photoswipe, intense, or any of blazy-supported lightboxes. Try selecting "<strong>- None -</strong>" first before changing if trouble with this complex form states.'),
        '#weight'       => -99,
      ];

      // Optional lightbox integration.
      if (!empty($lightboxes)) {
        foreach ($lightboxes as $lightbox) {
          $name = ucwords(str_replace('_', ' ', $lightbox));
          $form['media_switch']['#options'][$lightbox] = t('Image to @lightbox', ['@lightbox' => $name]);
        }

        // Re-use the same image style for both lightboxes.
        $form['box_style'] = [
          '#type'    => 'select',
          '#title'   => t('Lightbox image style'),
          '#options' => $image_styles,
          '#states'  => $this->getState(static::STATE_LIGHTBOX_ENABLED, $definition),
          '#weight'  => -98,
        ];

        if (!empty($definition['multimedia'])) {
          $form['box_media_style'] = [
            '#type'        => 'select',
            '#title'       => t('Lightbox video style'),
            '#options'     => $image_styles,
            '#description' => t('Allows different lightbox video dimensions. Or can be used to have a swipable video if Blazy PhotoSwipe installed.'),
            '#states'      => $this->getState(static::STATE_LIGHTBOX_ENABLED, $definition),
            '#weight'      => -98,
          ];
        }
      }

      // Adds common supported entities for media integration.
      if (!empty($definition['multimedia'])) {
        $form['media_switch']['#options']['media'] = t('Image to iFrame');
      }

      // http://en.wikipedia.org/wiki/List_of_common_resolutions
      $ratio = ['1:1', '3:2', '4:3', '8:5', '16:9', 'fluid', 'enforced'];
      if (empty($definition['no_ratio'])) {
        $form['ratio'] = [
          '#type'         => 'select',
          '#title'        => t('Aspect ratio'),
          '#options'      => array_combine($ratio, $ratio),
          '#empty_option' => t('- None -'),
          '#description'  => t('Aspect ratio to get consistently responsive images and iframes. And to fix layout reflow and excessive height issues. <a href="@dimensions" target="_blank">Image styles and video dimensions</a> must <a href="@follow" target="_blank">follow the aspect ratio</a>. If not, images will be distorted. Choose <strong>enforced</strong> if you can stick to one aspect ratio and want multi-serving, or Picture images. Try <strong>fluid</strong> if unsure. <a href="@link" target="_blank">Learn more</a>, or leave empty to DIY, or when working with multi-image-style plugin like GridStack. <br /><strong>Note!</strong> Only compatible with Blazy multi-serving images, but not Picture image, except for <b>enforced</b>.', [
            '@dimensions'  => '//size43.com/jqueryVideoTool.html',
            '@follow'      => '//en.wikipedia.org/wiki/Aspect_ratio_%28image%29',
            '@link'        => '//www.smashingmagazine.com/2014/02/27/making-embedded-content-work-in-responsive-design/',
          ]),
          '#weight'        => -93,
        ];

        if ($is_responsive) {
          $form['ratio']['#states'] = $this->getState(static::STATE_RESPONSIVE_IMAGE_STYLE_DISABLED, $definition);
        }
      }
    }

    if (!empty($definition['thumbnail_style'])) {
      $form['thumbnail_style'] = [
        '#type'        => 'select',
        '#title'       => t('Thumbnail style'),
        '#options'     => $image_styles,
        '#description' => t('Usages: Photobox/ PhotoSwipe thumbnail, or custom work with thumbnails. Leave empty to not use thumbnails.'),
        '#weight'      => -94,
      ];
    }

    if (!empty($definition['target_type']) && !empty($definition['use_view_mode'])) {
      $options = $this->getViewModeOptions($definition['target_type']);
      $form['view_mode'] = [
        '#type'        => 'select',
        '#options'     => $options,
        '#title'       => t('View mode'),
        '#description' => t('Required to grab the fields, or to have custom entity display as fallback display. If it has fields, be sure the selected "View mode" is enabled, and the enabled fields here are not hidden there. Create view modes using hook_entity_info_alter, or <a href="@url" target="_blank">entity_view_mode</a>.', ['@url' => '//drupal.org/project/entity_view_mode']),
        '#weight'      => -92,
        '#access'      => count($options) > 1,
        '#enforced'    => TRUE,
      ];
    }

    drupal_alter('blazy_base_form_element', $form, $definition);

    return $form;
  }

  /**
   * Returns re-usable media switch form elements.
   */
  public function mediaSwitchForm(array &$form, $definition = []) {
    $settings   = isset($definition['settings']) ? $definition['settings'] : [];
    $lightboxes = $this->manager()->getLightboxes();
    $is_token   = function_exists('token_theme');

    if (!isset($settings['media_switch'])) {
      return;
    }

    $form['media_switch'] = $this->baseForm($definition)['media_switch'];
    $form['media_switch']['#prefix'] = '<h3 class="form__title form__title--media-switch">' . t('Media switcher') . '</h3>';

    if (empty($definition['no_ratio'])) {
      $form['ratio'] = $this->baseForm($definition)['ratio'];
    }

    // Optional lightbox integration.
    if (!empty($lightboxes)) {
      $form['box_style'] = $this->baseForm($definition)['box_style'];

      if (!empty($definition['multimedia'])) {
        $form['box_media_style'] = $this->baseForm($definition)['box_media_style'];
      }

      $box_captions = [
        'auto'         => t('Automatic'),
        'alt'          => t('Alt text'),
        'title'        => t('Title text'),
        'alt_title'    => t('Alt and Title'),
        'title_alt'    => t('Title and Alt'),
        'entity_title' => t('Content title'),
        'custom'       => t('Custom'),
      ];

      if (!empty($definition['box_captions'])) {
        $form['box_caption'] = [
          '#type'        => 'select',
          '#title'       => t('Lightbox caption'),
          '#options'     => $box_captions,
          '#weight'      => -98,
          '#states'      => $this->getState(static::STATE_LIGHTBOX_ENABLED, $definition),
          '#description' => t('Automatic will search for Alt text first, then Title text. Try selecting <strong>- None -</strong> first when changing if trouble with form states.'),
        ];

        if ($this->showAltTitleFieldHint($definition)) {
          $form['box_caption']['#description'] = $this->showAltTitleFieldHint($definition);
        }

        $form['box_caption_custom'] = [
          '#title'       => t('Lightbox custom caption'),
          '#type'        => 'textfield',
          '#weight'      => -97,
          '#states'      => $this->getState(static::STATE_LIGHTBOX_CUSTOM, $definition),
          '#description' => t('Multi-value rich text field will be mapped to each image by its delta.'),
        ];

        if ($is_token) {
          $types = isset($definition['entity_type_id']) ? [$definition['entity_type_id']] : [];
          $types = isset($definition['target_type']) ? array_merge($types, [$definition['target_type']]) : $types;
          $token_tree = [
            '#theme'       => 'token_tree_link',
            '#text'        => t('Tokens'),
            '#token_types' => $types,
          ];

          $form['box_caption_custom']['#field_suffix'] = drupal_render($token_tree);
        }
        else {
          $form['box_caption_custom']['#description'] .= ' ' . t('Install Token module to browse available tokens.');
        }
      }
    }

    drupal_alter('blazy_media_switch_form_element', $form, $definition);
  }

  /**
   * Returns re-usable logic, styling and assets across fields and Views.
   */
  public function finalizeForm(array &$form, $definition = []) {
    $namespace = isset($definition['namespace']) ? $definition['namespace'] : 'slick';
    $settings = isset($definition['settings']) ? $definition['settings'] : [];
    $vanilla = !empty($definition['vanilla']) ? ' form--vanilla' : '';
    $grid = !empty($definition['grid_required']) ? ' form--grid-required' : '';
    $plugind_id = !empty($definition['plugin_id']) ? ' form--plugin-' . str_replace('_', '-', $definition['plugin_id']) : '';
    $count = empty($definition['captions']) ? 0 : count($definition['captions']);
    $count = empty($definition['captions_count']) ? $count : $definition['captions_count'];
    $wide = $count > 2 ? ' form--wide form--caption-' . $count : ' form--caption-' . $count;
    $fallback = $namespace == 'slick' ? 'form--slick' : 'form--' . $namespace . ' form--slick';
    $custom = isset($definition['opening_class']) ? ' ' . $definition['opening_class'] : '';
    $plugins = ' form--namespace-' . $namespace;
    $classes = $fallback . ' form--half has-tooltip' . $wide . $vanilla . $grid . $plugind_id . $custom . $plugins;

    if (!empty($definition['field_type'])) {
      $classes .= ' form--' . str_replace('_', '-', $definition['field_type']);
    }

    $form['opening'] = [
      '#markup' => '<div class="' . $classes . '">',
      '#weight' => -120,
    ];

    $form['closing'] = [
      '#markup' => '</div>',
      '#weight' => 120,
    ];

    $admin_css = $this->manager()->config('admin_css', TRUE, 'blazy.settings');
    $excludes = ['details', 'fieldset', 'hidden', 'markup', 'item', 'table'];
    $selects = ['cache', 'optionset', 'view_mode'];

    drupal_alter('blazy_form_element', $form, $definition);

    foreach (element_children($form) as $key) {
      // Works around for non-existent #wrapper_attributes at D7.
      $form[$key]['#attributes']['data-blazy-form-item'] = str_replace('_', '-', $key);

      if (isset($form[$key]['#type']) && !in_array($form[$key]['#type'], $excludes)) {
        if (!isset($form[$key]['#default_value']) && isset($settings[$key])) {
          $value = is_array($settings[$key]) ? array_values((array) $settings[$key]) : $settings[$key];

          if (!empty($definition['grid_required']) && $key == 'grid' && empty($settings[$key])) {
            $value = 3;
          }
          $form[$key]['#default_value'] = $value;
        }
        if (!isset($form[$key]['#attributes']) && isset($form[$key]['#description'])) {
          $form[$key]['#attributes']['class'][] = 'is-tooltip';
        }

        if ($admin_css) {
          if ($form[$key]['#type'] == 'checkbox' && $form[$key]['#type'] != 'checkboxes') {
            $form[$key]['#field_suffix'] = '&nbsp;';
            $form[$key]['#title_display'] = 'before';
          }
          elseif ($form[$key]['#type'] == 'checkboxes' && !empty($form[$key]['#options'])) {
            $form[$key]['#attributes']['class'][] = 'form-wrapper--checkboxes';
            $form[$key]['#attributes']['class'][] = 'form-wrapper--' . str_replace('_', '-', $key);
            $count = count($form[$key]['#options']);
            $form[$key]['#attributes']['class'][] = 'form-wrapper--count-' . ($count > 3 ? 'max' : $count);

            foreach ($form[$key]['#options'] as $i => $option) {
              $form[$key][$i]['#field_suffix'] = '&nbsp;';
              $form[$key][$i]['#title_display'] = 'before';
            }
          }
        }

        if ($form[$key]['#type'] == 'select' && !in_array($key, $selects)) {
          if (!isset($form[$key]['#empty_option']) && empty($form[$key]['#required'])) {
            $form[$key]['#empty_option'] = t('- None -');
          }
          if (!empty($form[$key]['#required'])) {
            unset($form[$key]['#empty_option']);
          }
        }

        if (!isset($form[$key]['#enforced']) && !empty($definition['vanilla']) && isset($form[$key]['#type'])) {
          $states['visible'][':input[name*="[vanilla]"]'] = ['checked' => FALSE];
          if (isset($form[$key]['#states'])) {
            $form[$key]['#states']['visible'][':input[name*="[vanilla]"]'] = ['checked' => FALSE];
          }
          else {
            $form[$key]['#states'] = $states;
          }
        }
      }

      if (isset($form[$key]['#access']) && $form[$key]['#access'] == FALSE) {
        unset($form[$key]['#default_value']);
      }
    }

    if ($admin_css) {
      $form['closing']['#attached']['library'][] = ['blazy', 'admin'];
    }

    drupal_alter('blazy_complete_form_element', $form, $definition);
  }

  /**
   * Returns time in interval for select options.
   */
  public function getCacheOptions() {
    $period = [
      0,
      60,
      180,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
    ];

    $period = drupal_map_assoc($period, 'format_interval');
    $period[0] = '<' . t('No caching') . '>';
    return $period + [CACHE_TEMPORARY => t('Persistent')];
  }

  /**
   * Returns available optionsets for select options.
   */
  public function getOptionsetOptions(array $entities = []) {
    return $this->manager->getOptionsetOptions($entities);
  }

  /**
   * Returns available view modes for select options.
   */
  public function getViewModeOptions($entity_type) {
    if (!isset($this->viewModeOptions)) {
      $this->viewModeOptions = ['default' => t('Default')];
      $view_mode_excludes = [
        'rss',
        'search_index',
        'search_result',
        'print',
        'token',
        'preview',
        'wysiwyg',
      ];

      $entity_info = entity_get_info($entity_type);

      if (!empty($entity_info['view modes'])) {
        foreach ($entity_info['view modes'] as $view_mode => $view_mode_settings) {
          if (in_array($view_mode, $view_mode_excludes)) {
            continue;
          }
          $this->viewModeOptions[$view_mode] = check_plain($view_mode_settings['label']);
        }
      }
    }

    return $this->viewModeOptions;
  }

  /**
   * Get one of the pre-defined states used in this form.
   *
   * Thanks to SAM152 at colorbox.module for the little sweet idea.
   *
   * @param string $state
   *   The state to get that matches one of the state class constants.
   * @param array $definition
   *   The foem definitions or settings.
   *
   * @return array
   *   A corresponding form API state.
   */
  protected function getState($state, array $definition = []) {
    $lightboxes = [];

    if ($boxes = $this->manager()->getLightboxes()) {
      foreach ($boxes as $lightbox) {
        $lightboxes[] = ['value' => $lightbox];
      }
    }

    $states = [
      static::STATE_RESPONSIVE_IMAGE_STYLE_DISABLED => [
        'visible' => [
          'select[name$="[responsive_image_style]"]' => ['value' => ''],
        ],
      ],
      static::STATE_LIGHTBOX_ENABLED => [
        'visible' => [
          'select[name*="[media_switch]"]' => $lightboxes,
        ],
      ],
      static::STATE_LIGHTBOX_CUSTOM => [
        'visible' => [
          'select[name$="[box_caption]"]' => ['value' => 'custom'],
          'select[name*="[media_switch]"]' => $lightboxes,
        ],
      ],
      static::STATE_IFRAME_ENABLED => [
        'visible' => [
          'select[name*="[media_switch]"]' => ['value' => 'media'],
        ],
      ],
      static::STATE_THUMBNAIL_STYLE_ENABLED => [
        'visible' => [
          'select[name$="[thumbnail_style]"]' => ['!value' => ''],
        ],
      ],
      static::STATE_IMAGE_RENDERED_ENABLED => [
        'visible' => [
          'select[name$="[media_switch]"]' => ['!value' => 'rendered'],
        ],
      ],
    ];
    return $states[$state];
  }

  /**
   * Helper function for getting correct bundle for manage field/display path.
   */
  protected function getBundlePath($instance) {
    $path = [];
    switch ($instance['entity_type']) {
      case 'bean':
        $path['field'] = 'admin/structure/block-types/manage/' . $instance['bundle'] . '/fields/' . $instance['field_name'];
        $path['display'] = 'admin/structure/block-types/manage/' . $instance['bundle'] . '/display';
        break;

      case 'taxonomy_term':
        $path['field'] = 'admin/structure/taxonomy/' . $instance['bundle'] . '/fields/' . $instance['field_name'];
        $path['display'] = 'admin/structure/taxonomy/' . $instance['bundle'] . '/display';
        break;

      default:
        $path['field'] = 'admin/structure/types/manage/' . $instance['bundle'] . '/fields/' . $instance['field_name'];
        $path['display'] = 'admin/structure/types/manage/' . $instance['bundle'] . '/display';
        break;
    }

    return $path;
  }

  /**
   * If the image field doesn't have the Title field enabled, tell the user.
   */
  protected function showAltTitleFieldHint($definition) {
    $instance = empty($definition['instance']) ? FALSE : $definition['instance'];
    if ($instance && (isset($instance['settings']['title_field'])
      && $instance['settings']['title_field'] == FALSE
      || isset($instance['settings']['alt_field'])
      && $instance['settings']['alt_field'] == FALSE)) {

      $bundle_path = $this->getBundlePath($instance);

      return t('You need to <a href="@url" target="_blank">enable both title and alt fields</a> to use them as caption.', [
        '@url' => url($bundle_path['field'],
          [
            'fragment' => 'edit-instance-settings-alt-field',
            'query' => [
              'destination' => $bundle_path['display'],
            ],
          ]
        ),
      ]);
    }
    return FALSE;
  }

}
