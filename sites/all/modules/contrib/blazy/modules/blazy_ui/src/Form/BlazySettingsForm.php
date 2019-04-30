<?php

namespace Drupal\blazy_ui\Form;

use Drupal\blazy\BlazyDefault;
use Drupal\blazy\BlazyManagerInterface;

/**
 * Defines blazy admin settings form.
 */
class BlazySettingsForm {

  /**
   * The blazy manager service.
   *
   * @var Drupal\blazy\BlazyManagerInterface
   */
  protected $manager;

  /**
   * Class constructor.
   */
  public function __construct(BlazyManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm() {
    $form['admin_css'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Admin CSS'),
      '#default_value' => $this->manager->config('admin_css', TRUE),
      '#description'   => t('Uncheck to disable blazy related admin compact form styling, only if not compatible with your admin theme.'),
    ];

    $form['responsive_image'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Support Picture module'),
      '#default_value' => $this->manager->config('responsive_image', FALSE),
      '#description'   => t('Check to support the <a href="@url">Picture</a> module. Be sure to use blazy-related formatters.', ['@url' => 'https://dgo.to/picture']),
      '#disabled'      => !function_exists('picture_mapping_load'),
    ];

    $form['unbreakpoints'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Disable custom breakpoints'),
      '#default_value' => $this->manager->config('breakpoints', FALSE),
      '#description'   => t('Check to permanently disable custom breakpoints which is always disabled when choosing a Picture. Only reasonable if consistently using Picture.'),
    ];

    $form['one_pixel'] = [
      '#type'          => 'checkbox',
      '#title'         => t('One pixel placeholder'),
      '#default_value' => $this->manager->config('one_pixel', TRUE),
      '#description'   => t('By default a one pixel image is the placeholder for lazyloaded Picture. Useful to perform a lot better. Uncheck to disable, and use Drupal-managed smallest/fallback image style instead. Be sure to add proper dimensions or at least min-height/min-width via CSS accordingly to avoid layout reflow since Aspect ratio is not supported with Picture yet. Disabling this will result in downloading fallback image as well for non-PICTURE element (double downloads).'),
    ];

    $form['placeholder'] = [
      '#type'          => 'textfield',
      '#title'         => t('Placeholder'),
      '#default_value' => $this->manager->config('placeholder', ''),
      '#description'   => t('Overrides global 1px placeholder. Can be URL, e.g.: https://mysite.com/blank.gif. Only useful if continuously using Views rewrite results, see <a href="@url">#2908861</a>. Alternatively use <code>hook_blazy_settings_alter()</code> for more fine-grained control. Leave it empty to use default Data URI to avoid extra HTTP requests. If you have 100 images on a page, you will save 100 extra HTTP requests by leaving it empty.', ['@url' => 'https://drupal.org/node/2908861']),
    ];

    $form['blazy'] = [
      '#type'        => 'fieldset',
      '#tree'        => TRUE,
      '#title'       => t('Blazy settings'),
      '#description' => t('The following settings are related to Blazy library.'),
    ];

    $form['blazy']['loadInvisible'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Load invisible'),
      '#default_value' => $this->manager->config('blazy.loadInvisible', FALSE),
      '#description'   => t('Check if you want to load invisible (hidden) elements.'),
    ];

    $form['blazy']['offset'] = [
      '#type'          => 'textfield',
      '#title'         => t('Offset'),
      '#default_value' => $this->manager->config('blazy.offset', 100),
      '#description'   => t("The offset controls how early you want the elements to be loaded before they're visible. Default is <strong>100</strong>, so 100px before an element is visible it'll start loading."),
      '#field_suffix'  => 'px',
      '#maxlength'     => 5,
      '#size'          => 10,
    ];

    $form['blazy']['saveViewportOffsetDelay'] = [
      '#type'          => 'textfield',
      '#title'         => t('Save viewport offset delay'),
      '#default_value' => $this->manager->config('blazy.saveViewportOffsetDelay', 50),
      '#description'   => t('Delay for how often it should call the saveViewportOffset function on resize. Default is <strong>50</strong>ms.'),
      '#field_suffix'  => 'ms',
      '#maxlength'     => 5,
      '#size'          => 10,
    ];

    $form['blazy']['validateDelay'] = [
      '#type'          => 'textfield',
      '#title'         => t('Set validate delay'),
      '#default_value' => $this->manager->config('blazy.validateDelay', 25),
      '#description'   => t('Delay for how often it should call the validate function on scroll/resize. Default is <strong>25</strong>ms.'),
      '#field_suffix'  => 'ms',
      '#maxlength'     => 5,
      '#size'          => 10,
    ];

    $form['io'] = [
      '#type'        => 'fieldset',
      '#tree'        => TRUE,
      '#open'        => TRUE,
      '#title'       => t('Intersection Observer API settings (<b>Experimental!</b>)'),
      '#description' => t('The following settings are related to <a href="@url">IntersectionObserver API</a>.', ['@url' => 'https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API']),
    ];

    $form['io']['enabled'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Enable IntersectionObserver API'),
      '#default_value' => $this->manager->config('io.enabled', FALSE),
      '#description'   => t('Check if you want to use IntersectionObserver API for modern browsers, and Blazy for oldies.'),
    ];

    $form['io']['unblazy'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Unload bLazy'),
      '#default_value' => $this->manager->config('io.unblazy'),
      '#description'   => t("Check if you are happy with IO. This will not load the original bLazy library, no fallback. Watch out for JS errors at browser consoles, and uncheck if any, or unsure. Blazy is just ~1KB gzip. Clear caches!"),
    ];

    $form['io']['rootMargin'] = [
      '#type'          => 'textfield',
      '#title'         => t('rootMargin'),
      '#default_value' => $this->manager->config('io.rootMargin', '0px'),
      '#description'   => t("Margin around the root. Can have values similar to the CSS margin property, e.g. <code>10px 20px 30px 40px</code> (top, right, bottom, left). The values can be percentages. This set of values serves to grow or shrink each side of the root element's bounding box before computing intersections. Defaults to all zeros."),
      '#maxlength'     => 120,
      '#size'          => 20,
    ];

    $form['io']['threshold'] = [
      '#type'          => 'textfield',
      '#title'         => t('threshold'),
      '#default_value' => $this->manager->config('io.threshold', '0'),
      '#description'   => t("Either a single number or an array of numbers which indicate at what percentage of the target's visibility the observer's callback should be executed. If you only want to detect when visibility passes the 50% mark, you can use a value of 0.5. If you want the callback to run every time visibility passes another 25%, you would specify the array [<code>0, 0.25, 0.5, 0.75, 1</code>] (without brackets). The default is 0 (meaning as soon as even one pixel is visible, the callback will be run). A value of 1.0 means that the threshold isn't considered passed until every pixel is visible."),
      '#maxlength'     => 120,
      '#size'          => 20,
    ];

    $form['io']['disconnect'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Disconnect'),
      '#default_value' => $this->manager->config('io.disconnect', FALSE),
      '#description'   => t('Check if you want to disconnect IO once all images loaded. If you keep seeing eternal blue loader while an image should be already loaded, this means it is not working yet in all cases. Just uncheck this.'),
    ];

    $form['visibility'] = [
      '#type'          => 'radios',
      '#title'         => t('Show Blazy on specific pages'),
      '#description'   => t('Blazy uses formatters. However for Blazy HTML filter to work with inline media (specific for D7), we must define pages to load Blazy library here. Only Blazy CSS and JS are loaded where Blazy filter is active. Blazy never overrides images globally.'),
      '#options'       => [0 => t('All pages except those listed'), 1 => t('Only the listed pages')],
      '#default_value' => $this->manager->config('visibility', 0),
    ];

    $form['pages'] = [
      '#type'          => 'textarea',
      '#title'         => '<span class="element-invisible">' . t('Pages') . '</span>',
      '#default_value' => $this->manager->config('pages', BlazyDefault::PAGES),
      '#description'   => t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", [
        '%blog'          => 'blog',
        '%blog-wildcard' => 'blog/*',
        '%front'         => '<front>',
      ]),
    ];

    // Allows sub-modules to provide its own settings.
    $form['extras'] = [
      '#type'   => 'fieldset',
      '#tree'   => TRUE,
      '#title'  => t('Extra settings'),
      '#access' => FALSE,
    ];

    $form['#submit'][] = 'blazy_ui_submit_form';

    return system_settings_form($form);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm($form, &$form_state) {
    $defaults = BlazyDefault::formSettings();
    $data = [];

    // Always run typecasting on submit.
    $this->manager->typecast($form_state['values'], 'blazy.settings', TRUE);
    foreach ($defaults as $key => $value) {
      if (isset($form_state['values'][$key])) {
        $data[$key] = $form_state['values'][$key];
      }
    }

    // Merge all separate variables into blazy.settings for simplicity.
    variable_set('blazy.settings', array_merge((array) $this->manager->config(), $data));

    // Safe to remove old array since already merged above.
    foreach ($defaults as $key => $value) {
      if (isset($form_state['values'][$key])) {
        unset($form_state['values'][$key]);
      }
    }
  }

}
