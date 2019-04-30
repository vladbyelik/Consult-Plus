<?php

/**
 * @file
 * Hooks and API provided by the Blazy module.
 */

/**
 * @defgroup blazy_api Blazy API
 * @{
 * Information about the Blazy usages.
 *
 * Modules may implement any of the available hooks to interact with Blazy.
 * Blazy may be configured using the web interface using formatters, or Views.
 * However below is a few sample coded ones.
 *
 * Calling theme_blazy() directly is useless. Please use the provided API below.
 * Most heavy liftings are performed at BlazyManager::preRender() where they
 * belong to. At Blazy 8.x-1.x, it is still possible to call theme_blazy(), but
 * this will be removed later at Blazy 8.x-2.x+. The only reason is supporting
 * direct call has produced a few duplicated lines. As we are perfecting Blazy
 * API for both simple and complex needs, we strive to minimize dups.
 *
 * A single image sample.
 * @code
 * function my_module_render_blazy() {
 *   // URI is required, set it via settings array or image object.
 *   // Using image as an object, not array, is simply to reflect a D8 backport.
 *   // Create an optional fake image object containing image metadata:
 *   $item         = new \stdClass();
 *   $item->width  = 640;
 *   $item->height = 360;
 *   $item->alt    = t('Awesome image');
 *   $item->uri    = 'public://logo.jpg';
 *
 *   // Or convert an existing image or file entity image array into an object:
 *   $file = file_load(123);
 *   $item = (object) $file;
 *
 *   // Provides info for Blazy to do its job via $settings array.
 *   $settings = [
 *     // URI is stored in #settings property so to allow traveling around video
 *     // and lightboxes before being passed into theme_blazy().
 *     'uri' => 'public://logo.jpg',
 *
 *     // Explicitly request for Blazy.
 *     // This allows Slick lazyLoad to not load Blazy.
 *     // This `lazy` defines the HTML output, and makes sense for Slick
 *     // which has extra lazyload names: ondemand, anticipated, etc
 *     'lazy' => 'blazy',
 *
 *     // In order to attach the Blazy library an sich, call for `blazy`:
 *     'blazy' => TRUE,
 *
 *     // Optionally provide an image style. Valid URI is a must:
 *     'image_style' => 'thumbnail',
 *
 *     // Optionally require one of Blazy media_switch features depending on
 *     // the available module, of course:
 *     // colorbox, photobox, photoswipe, media (Image to iframe), and set its
 *     // value to TRUE to load its own library.
 *     // This `media_switch` defines the HTML output, CSS classes, link, etc.
 *     'media_switch' => 'colorbox',
 *
 *     // The below `colorbox` key is to load the colorbox library.
 *     // We do this separately as relying on `media_switch` alone to load
 *     // library may conflict with Blazy filter set globally once.
 *     // It will negate each other.
 *     // With media_switch defining HTML, and colorbox for library, it allows
 *     // us to have multiple media_switch, and relevant libraries loaded on the
 *     // same page.
 *     'colorbox' => TRUE,
 *   ];
 *
 *   // Pass $data containing $item and $settings into BlazyManager::getBlazy().
 *   // It was poorly named BlazyManager::getImage() at D8 while Blazy may also
 *   // contain Media video with iframe element. Probably getMedia() is cool,
 *   // but let's stick to getBlazy() for now as Blazy also works without Image
 *   // nor Media video, such as with just a DIV element for CSS background.
 *   $data = ['item' => $item, 'settings' => $settings];
 *   $build = blazy()->getBlazy($data);
 *
 *   // Finally load the library, or include it into a parent container.
 *   // Or make Blazy available wherever needed at /admin/config/media/blazy.
 *   // Just dump the provided $settings as the argument, or refine it as needed
 *   // @see \Drupal\blazy\BlazyManagerBase::attach().
 *   $build['#attached'] = blazy()->attach($settings);
 *
 *   return $build;
 * }
 * @endcode
 * @see \Drupal\blazy\BlazyDefault::imageSettings()
 *
 * A multiple image sample.
 *
 * For advanced usages with multiple images, and a few Blazy features such as
 * lightboxes, lazyloaded images, or iframes, including CSS background and
 * aspect ratio, etc.:
 *   o Invoke blazy(), and or blazy('formatter'), services.
 *   o Use blazy()->getBlazy() method to work with images and
 *     pass relevant settings which request for particular Blazy features
 *     accordingly.
 *   o Use blazy()->attach($attach) to load relevant libraries.
 *     Where $attach can contain any of supported blazy libraries:
 *     colorbox, photobox, media (Image to iframe), blazy, grid, column, etc.
 *     Just set the value to TRUE to require one.
 * @code
 * function my_module_render_blazy_multiple() {
 *   // @see the above my_module_render_blazy() for details.
 *   $settings = [];
 *
 *   // Build images.
 *   $build = [
 *     // Load images via blazy()->getBlazy().
 *     // See below ...Formatter::buildElements() for consistent samples.
 *   ];
 *
 *   // Finally attach libraries as requested via $settings.
 *   $build['#attached'] = blazy()->attach($settings);
 *
 *   return $build;
 * }
 * @endcode
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyManagerTrait::buildElements()
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyVideoFormatter::buildElements()
 * @see \Drupal\gridstack\Plugin\Field\FieldFormatter\GridStackFileFormatterBase::buildElements()
 * @see \Drupal\slick\Plugin\Field\FieldFormatter\SlickFileFormatterBase::buildElements()
 * @see \Drupal\blazy\BlazyManager::getBlazy()
 * @see \Drupal\blazy\BlazyDefault::imageSettings()
 *
 *
 * Pre-render callback sample to modify/ extend Blazy output.
 * @code
 * function my_module_pre_render(array $image) {
 *   $settings = isset($image['#settings']) ? $image['#settings'] : [];
 *
 *   // Video's HREF points to external site, adds URL to local image.
 *   if (!empty($settings['box_url']) && !empty($settings['embed_url'])) {
 *     $image['#url_attributes']['data-box-url'] = $settings['box_url'];
 *   }
 *
 *   return $image;
 * }
 * @endcode
 * @see hook_blazy_alter()
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters Blazy attachments to add own library, drupalSettings, and JS template.
 *
 * @param array $load
 *   The array of loaded library being modified.
 * @param array $settings
 *   The available array of settings.
 *
 * @ingroup blazy_api
 */
function hook_blazy_attach_alter(array &$load, array $settings = []) {
  if (!photoswipe_assets_loaded() && !empty($settings['photoswipe'])) {
    libraries_load('photoswipe');

    $template = ['#theme' => 'photoswipe_container'];
    $template = preg_replace(['/<!--(.|\s)*?-->/', '/\s+/'], ' ', drupal_render($template));
    $load['js'][] = [
      'data' => [
        'photoswipe' => [
          'options' => variable_get('photoswipe_settings', photoswipe_get_default_settings()),
          'container' => trim($template),
        ],
      ],
      'type' => 'setting',
    ];

    $load['library'][] = ['blazy_photoswipe', 'load'];
    photoswipe_assets_loaded(TRUE);
  }
}

/**
 * Alters available lightboxes for Media switch select option at Blazy UI.
 *
 * @param array $lightboxes
 *   The array of lightbox options being modified.
 *
 * @see https://www.drupal.org/project/blazy_photoswipe
 *
 * @ingroup blazy_api
 */
function hook_blazy_lightboxes_alter(array &$lightboxes) {
  $lightboxes[] = 'photoswipe';
}

/**
 * Alters Blazy individual item output to support a custom lightbox.
 *
 * @param array $build
 *   The renderable array of image being modified.
 * @param array $settings
 *   The available array of settings.
 *
 * @ingroup blazy_api
 */
function hook_blazy_alter(array &$build, array $settings = []) {
  if (!empty($settings['media_switch']) && $settings['media_switch'] == 'photoswipe') {
    $build['#pre_render'][] = 'my_module_pre_render';
  }
}

/**
 * Alters the entire Blazy output to support own features.
 *
 * In a case of ElevateZoom Plus, it adds a prefix large image preview before
 * the Blazy Grid elements by adding an extra #theme_wrappers via #pre_render
 * element.
 *
 * @param array $build
 *   The renderable array of Blazy output being modified.
 * @param array $settings
 *   The available array of settings.
 *
 * @ingroup blazy_api
 */
function hook_blazy_build_alter(array &$build, array $settings = []) {
  if (!empty($settings['elevatezoomplus'])) {
    $build['#pre_render'][] = 'elevatezoomplus_pre_render_build';
  }
}

/**
 * Alters blazy-related formatter form options to make site-builders happier.
 *
 * A less robust alternative to third party settings to pass the options to
 * blazy-related formatters within the designated compact form.
 * While third party settings offer more fine-grained control over a specific
 * formatter, this offers a swap to various blazy-related formatters at one go.
 * Any class extending \Drupal\blazy\BlazyDefault will be capable
 * to modify both form and UI options at one go.
 *
 * This requires 4 things: option definitions (this alter), schema, extended
 * forms, and front-end implementation of the provided options which can be done
 * via regular hook_preprocess().
 *
 * In addition to the schema, implement hook_blazy_complete_form_element_alter()
 * to provide the actual extended forms, see far below. And lastly, implement
 * the options at front-end via hook_preprocess().
 *
 * @param array $settings
 *   The settings being modified.
 * @param array $context
 *   The array containing class which defines or limit the scope of the options.
 *
 * @ingroup blazy_api
 */
function hook_blazy_base_settings_alter(array &$settings, array $context = []) {
  // One override for both various Slick field formatters and Slick views style.
  // SlickDefault extends BlazyDefault, hence capable to modify/ extend options.
  // These options will be available at many Slick formatters at one go.
  if ($context['class'] == 'Drupal\slick\SlickDefault') {
    $settings += ['color' => '', 'arrowpos' => '', 'dotpos' => ''];
  }
}

/**
 * Alters blazy settings inherited by all child elements.
 *
 * @param array $build
 *   The array containing: settings, or potential optionset for extensions.
 * @param object $items
 *   The Drupal\Core\Field\FieldItemListInterface items.
 *
 * @ingroup blazy_api
 */
function hook_blazy_settings_alter(array &$build, $items) {
  $settings = &$build['settings'];

  // Overrides one pixel placeholder on particular pages relevant if using Views
  // rewrite results which may strip out Data URI.
  // See https://drupal.org/node/2908861.
  if (isset($settings['entity_id']) && in_array($settings['entity_id'], [45, 67])) {
    $settings['placeholder'] = 'https://mysite.com/blank.gif';
  }
}

/**
 * Alters blazy-related formatter form elements.
 *
 * This takes advantage of Blazy taking care of a few elements finalizations,
 * such as adding #empty_option, extras CSS classes, checkboxes, states, etc.
 * This is run before hook_blazy_complete_form_element_alter().
 *
 * @param array $form
 *   The $form being modified.
 * @param array $definition
 *   The array defining the scope of form elements.
 *
 * @see \Drupal\blazy\Form\BlazyAdminBase::finalizeForm()
 *
 * @ingroup blazy_api
 */
function hook_blazy_form_element_alter(array &$form, array $definition = []) {
  // Limit the scope to Slick formatters, blazy, gridstack, etc. Or swap em all.
  if (isset($definition['namespace']) && $definition['namespace'] == 'slick') {
    // Extend the formatter form elements as needed.
  }
}

/**
 * Alters blazy-related formatter form elements.
 *
 * Modify anything Blazy forms output as you wish.
 * This is run after hook_blazy_form_element_alter().
 *
 * @param array $form
 *   The $form being modified.
 * @param array $definition
 *   The array defining the scope of form elements.
 *
 * @see \Drupal\blazy\Form\BlazyAdminBase::finalizeForm()
 *
 * @ingroup blazy_api
 */
function hook_blazy_complete_form_element_alter(array &$form, array $definition = []) {
  // Limit the scope to Slick formatters, blazy, gridstack, etc. Or swap em all.
  if (isset($definition['namespace']) && $definition['namespace'] == 'slick') {
    // Extend the formatter form elements as needed.
  }
}

/**
 * @} End of "addtogroup hooks".
 */
