<?php

namespace Drupal\slick;

/**
 * Provides an interface defining Slick skins.
 *
 * The hook_hook_info() is deprecated, and no resolution by 1/16/16:
 *   #2233261: Deprecate hook_hook_info()
 *     Postponed till D9
 *
 * @see slick.api.php for more supported methods.
 * @see src/SlickSkin.php
 * @see slick_extras/src/SlickExtrasSkin.php
 * @see slick_extras/slick_example/src/SlickExampleSkin.php
 * @see tests/modules/slick_test/src/SlickSkinTest.php
 */
interface SlickSkinInterface {

  /**
   * Returns the Slick skins.
   *
   * This can be used to register skins for the Slick. Skins will be
   * available when configuring the Optionset, Field formatter, or Views style,
   * or custom coded slicks. It is permanently cached, so you won't see changes
   * when adding new ones till cache clearing.
   *
   * Slick skins get a unique CSS class to use for styling, e.g.:
   * If your skin name is "my_module_slick_carousel_rounded", the CSS class is:
   * slick--skin--my-module-slick-carousel-rounded.
   *
   * A skin can specify CSS and JS files to include when Slick is displayed,
   * except for a thumbnail skin which accepts CSS only.
   * Each skin with its assets (CSS, JS, or library dependencies) is registered
   * as regular Drupal libraries via hook_library() at SlickLibrary::library().
   * The final library can be loaded like: ['slick', 'provider.group.name'].
   *
   * Each skin supports a few keys:
   * - name: The human readable name of the skin.
   * - description: The description about the skin, for help and manage pages.
   * - dependencies: An array of library dependencies.
   * - css: An array of CSS files to attach.
   * - js: An array of JS files to attach, e.g.: image zoomer, reflection, etc.
   * - options: An array of JS options to be included within [data-slick] such
   *     as when integrating extra libraries defined at `js` which later can be
   *     accessed by JS via [data-slick] to work with.
   * - group: A string grouping the current skin: main, thumbnail.
   * - provider: A module name registering the skins.
   *
   * @return array
   *   The array of the main and thumbnail skins.
   */
  public function skins();

}
