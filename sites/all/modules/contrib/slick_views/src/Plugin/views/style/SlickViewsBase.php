<?php

namespace Drupal\slick_views\Plugin\views\style;

use Drupal\blazy\Dejavu\BlazyStylePluginBase;
use Drupal\slick\SlickDefault;
use Drupal\slick\SlickManager;
use Drupal\slick\Form\SlickAdmin;

/**
 * The base class common for Slick style plugins.
 */
abstract class SlickViewsBase extends BlazyStylePluginBase {

  /**
   * The slick service manager.
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * Returns the blazy manager.
   */
  public function manager() {
    if (!isset($this->manager)) {
      $this->manager = new SlickManager();
    }
    return $this->manager;
  }

  /**
   * Returns the slick admin.
   */
  public function admin() {
    if (!isset($this->admin)) {
      $this->admin = new SlickAdmin($this->manager());
    }
    return $this->admin;
  }

  /**
   * {@inheritdoc}
   */
  public function init(&$view, &$display, $options = NULL) {
    parent::init($view, $display, $options);
    // Even empty active to call render; where library is attached if required.
    if ($view->use_ajax) {
      $this->definition['even empty'] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function option_definition() {
    $options = [];
    foreach (SlickDefault::extendedSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }

    drupal_alter('slick_views_options_info', $options);
    return $options + parent::option_definition();
  }

  /**
   * Returns the defined scopes for the current form.
   */
  protected function getDefinedFormScopes() {
    // Pass the common field options relevant to this style.
    $fields = [
      'captions',
      'classes',
      'images',
      'layouts',
      'links',
      'overlays',
      'thumbnails',
      'thumb_captions',
      'titles',
    ];

    // Fetches the returned field definitions to be used to define form scopes.
    $definition = $this->getDefinedFieldOptions($fields);
    foreach (['id', 'nav', 'thumb_positions', 'vanilla'] as $key) {
      $definition[$key] = TRUE;
    }

    $definition['forms'] = ['fieldable' => TRUE, 'grid' => TRUE];
    $definition['opening_class'] = 'form--views';
    $definition['_views'] = TRUE;
    return $definition;
  }

  /**
   * Build the Slick settings form.
   */
  public function buildSettingsForm(&$form, $definition) {
    $this->admin()->buildSettingsForm($form, $definition);

    $title = '<p class="form__header form__title">';
    $title .= t('Check Vanilla for content/custom markups, not fields. <small>See it under <strong>Format > Show</strong> section. Otherwise slick markups apply which require some fields added below.</small>');
    $title .= '</p>';

    $form['opening']['#markup'] .= $title;

    if (isset($form['image'])) {
      $form['image']['#description'] .= ' ' . t('Use Blazy formatter to have it lazyloaded. Other supported Formatters: Colorbox, Intense, Responsive image, Video Embed Field, Youtube Field.');
    }

    if (isset($form['overlay'])) {
      $form['overlay']['#description'] .= ' ' . t('Be sure to CHECK "<strong>Style settings > Use field template</strong>" _only if using Slick formatter for nested sliders, otherwise keep it UNCHECKED!');
    }

    // Bring in dots thumbnail effect normally used by Slick Image formatter.
    $form['thumbnail_effect'] = [
      '#type' => 'select',
      '#title' => t('Dots thumbnail effect'),
      '#options' => [
        'hover' => t('Hoverable'),
        'grid' => t('Static grid'),
      ],
      '#empty_option' => t('- None -'),
      '#description' => t('Dependent on a Skin, Dots and Thumbnail image options. No asnavfor/ Optionset thumbnail is needed. <ol><li><strong>Hoverable</strong>: Dots pager are kept, and thumbnail will be hidden and only visible on dot mouseover, default to min-width 120px.</li><li><strong>Static grid</strong>: Dots are hidden, and thumbnails are displayed as a static grid acting like dots pager.</li></ol>Alternative to asNavFor aka separate thumbnails as slider.'),
      '#weight' => -100,
    ];
  }

  /**
   * Overrides StylePluginBase::render().
   */
  protected function buildSettings() {
    $settings = parent::buildSettings();

    // Prepare needed settings to work with.
    $settings['item_id']      = 'slide';
    $settings['caption']      = array_filter($settings['caption']);
    $settings['namespace']    = 'slick';
    $settings['nav']          = !$settings['vanilla'] && $settings['optionset_thumbnail'] && isset($this->view->result[1]);
    $settings['overridables'] = empty($settings['override']) ? array_filter($settings['overridables']) : $settings['overridables'];

    return $settings;
  }

  /**
   * Returns slick contents.
   */
  public function buildElements(array $settings, $rows) {
    $build   = [];
    $view    = $this->view;
    $item_id = $settings['item_id'];

    foreach ($rows as $index => $row) {
      $view->row_index = $index;

      $slide = [];
      $thumb = $slide[$item_id] = [];

      // Provides a potential unique thumbnail different from the main image.
      if (!empty($settings['thumbnail'])) {
        $thumbnail = $this->getFieldRenderable($row, 0, $settings['thumbnail']);
        if (isset($thumbnail['rendered']['#item'])) {
          $item = $thumbnail['rendered']['#item'];
          // @todo re-check at D7.
          $settings['thumbnail_uri'] = is_object($item) ? $item->uri : $item['uri'];
        }
      }

      $slide['settings'] = $settings;

      // Use Vanilla slick if so configured, ignoring Slick markups.
      if (!empty($settings['vanilla'])) {
        $slide[$item_id] = $view->style_plugin->row_plugin->render($row);
      }
      else {
        // Otherwise, extra works. With a working Views cache, no big deal.
        $this->buildElement($slide, $row, $index);

        // Build thumbnail navs if so configured.
        if (!empty($settings['nav'])) {
          $thumb[$item_id] = empty($settings['thumbnail']) ? [] : $this->getFieldRendered($index, $settings['thumbnail']);
          $thumb['caption'] = empty($settings['thumbnail_caption']) ? [] : $this->getFieldRendered($index, $settings['thumbnail_caption']);

          $build['thumb']['items'][$index] = $thumb;
        }
      }

      if (!empty($settings['class'])) {
        $class = $this->getFieldString($row, $settings['class'], $index, TRUE);
        // Ensures useless field name is overriden, even if empty.
        $slide['settings']['class'] = empty($class) ? '' : $class;
      }

      if (empty($slide[$item_id]) && !empty($settings['image'])) {
        $slide[$item_id] = $this->getFieldRendered($index, $settings['image']);
      }

      $build['items'][$index] = $slide;
      unset($slide, $thumb);
    }

    unset($view->row_index);
    return $build;
  }

}
