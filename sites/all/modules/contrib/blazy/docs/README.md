
# ABOUT BLAZY
Provides integration with bLazy and or Intersection Observer API to lazy load
and multi-serve images to save bandwidth and server requests. The user will have
faster load times and save data usage if they don't browse the whole page.

## REQUIREMENTS
1. bLazy library:
   * [Download bLazy](https://github.com/dinbror/blazy)
   * Extract it as is, rename **blazy-master** to **blazy**, so the assets are:

      + **/sites/../libraries/blazy/blazy.min.js**

2. PHP 5.6+ (let us know if Blazy can go lower?)

3. Any of autoloader modules:
   * [registry_autoload](http://dgo.to/registry_autoload)
   * [xautoload](http://dgo.to/xautoload)
   * [autoload](http://dgo.to/autoload)
   * Others?

  Skip if you already one have, I meant, have one. If you have others, please
  create a feature to include it in the module `hook_requirements()`.

## INSTALLATION

### Two steps below are crucial, otherwise Blazy complains missing dependencies:
1. Visit **/admin/modules** and install one of autoload modules:
   * [registry_autoload](http://dgo.to/registry_autoload)
   * [xautoload](http://dgo.to/xautoload)
   * [autoload](http://dgo.to/autoload)

   Save! Do not install Blazy, yet, since Blazy has no hard dependency on any.
   The order above indicates priority. The first found will be locked.
   You can install another, and uninstall the other which suits you better.

2. Install Blazy. Once an autoloader is installed, it will be locked for
   Blazy usage later to avoid accidental removal.

As long as you stick to these 2 steps, it should be just fine like regular
modules with hard dependencies.

### Known issues:
* **autoload**: must run `drush aur` and `drush cc` on Blazy activation, or
  fatal. The same procedure applies whenever blazy-related modules are
  activated, or adding new classes. Especially during DEV, Alpha, Beta,
  before RC. If not using drush, consider the other two:

  **registry_autoload**, **xautoload**.

If any issue with other autoloaders, kindly let us know. Blazy doesn't have a
hard dependency on any, it is on your own discretion.

**At any rate, solution is available:**

* know how to run `drush cc all` or clear cache.
* Only **at worst** case, clear registry, or if you don't drush, install and
  know how to use registry_rebuild.module safely.

### <del>WTF</del> FTW?
Blazy uses classes as a direct backport of 8.x.

### More info:
[Drupal 7](http://drupal.org/documentation/install/modules-themes/modules-7)


## RECOMMENDED
* [Markdown](http://dgo.to/markdown)

  To make reading this README a breeze at [Blazy UI help](/admin/help/blazy_ui)


## FEATURES
* Supports core Image.
* Supports Picture.
* Supports Colorbox/ Photobox/ PhotoSwipe, etc, also multimedia lightboxes.
* Multi-serving lazyloaded images, including multi-breakpoint CSS backgrounds.
* Lazyload video iframe urls via custom coded, or Media.
* Supports inline images and iframes with lightboxes, and grid or CSS3 Masonry
  via Blazy Filter. Enable Blazy filter at **/admin/config/content/formats**,
  and check out instruction at **/filter/tips**.
* Blazy Grid formatter for Image, Media and Text with multi-value.
* Delay loading for below-fold images until 100px (configurable) before they are
  visible at viewport.
* A simple effortless CSS loading indicator.
* It doesn't take over all images, so it can be enabled as needed via Blazy
  formatters, or its supporting modules.


## OPTIONAL FEATURES
* Views fields for File Entity and Media integration, see Slick Browser.
* Views style plugin Blazy Grid for Grid Foundation or CSS3 Masonry.
* Field formatters: Blazy with Media integration.


## USAGES
Be sure to enable Blazy UI which can be uninstalled at production later.

* Go to Manage display page, e.g.:
  [Admin page displays](/admin/structure/types/manage/page/display)

* Find **Blazy** formatter under **Manage display**.

* Go to [Blazy UI](/admin/config/media/blazy) to manage few global options,
  including enabling support to bring Picture into blazy-related formatters.


### USAGES: BLAZY FOR MULTIMEDIA GALLERY VIA VIEWS UI
1. Add a Views style **Blazy Grid** for entities containing Media or Image.
2. Add a Blazy formatter for the Media or Image field.
3. Add any lightbox under **Media switcher** option.
4. Limit the values to 1 under **Multiple field settings** > **Display**.
5. Be sure to leave **Use field template** under **Style settings** unchecked.
   If checked, the gallery is locked to a single entity, that is no Views
   gallery, but gallery per field.

Check out the relevant sub-module docs for details.


## MODULES THAT INTEGRATE WITH OR REQUIRE BLAZY
* [Blazy PhotoSwipe](http://dgo.to/blazy_photoswipe)
* [Intense](http://dgo.to/intense)
* [Slick](http://dgo.to/slick)
* [Slick Lightbox](http://dgo.to/slick_lightbox)
* [Slick Views](http://dgo.to/slick_views)
* [Zooming](http://dgo.to/zooming)
* [ElevateZoomPlus](http://dgo.to/elevatezoomplus)

Most duplication efforts from the above modules will be merged into
\Drupal\blazy\Dejavu or anywhere else namespace.

**What dups?**

The most obvious is the removal of formatters from Intense, Zooming,
Slick Lightbox, Blazy PhotoSwipe, and other (quasi-)lightboxes. Any lightbox
supported by Blazy can use Blazy, or Slick formatters if applicable instead.
We do not have separate formatters when its prime functionality is embedding
a lightbox, or superceded by Blazy.

Blazy provides a versatile and reusable formatter for a few known lightboxes
with extra advantages:

lazyloading, grid, multi-serving images, Responsive image,
CSS background, captioning, etc.

Including making those lightboxes available for free at Views Field for
File entity, Media and Blazy Filter for inline images.

If you are developing lightboxes and using Blazy, I would humbly invite you
to give Blazy a try, and consider joining forces with Blazy, and help improve it
for the above-mentioned advantages. We are also continuously improving and
solidifying the API to make advanced usages a lot easier, and DX friendly.
Currently, of course, not perfect, but have been proven to play nice with at
least 7 lightboxes, and likely more.


## SIMILAR MODULES
[Lazyloader](https://www.drupal.org/project/lazyloader)


## CURRENT DEVELOPMENT STATUS
A full release should be reasonable after proper feedbacks from the community,
some code cleanup, and optimization where needed. Patches are very much welcome.

Not all D8 features are backported, yet, such as Picture integration, non-blazy-
grid multimedia gallery via Views UI, etc.


## PROGRAMATICALLY
See blazy.api.php for details.


## PERFORMANCE TIPS:
* If breakpoints provided with tons of images, using image styles with ANY crop
  is recommended to avoid image dimension calculation with individual images.
  The image dimensions will be set once, and inherited by all images as long as
  they contain word crop. If using scaled image styles, regular calculation
  applies.


## AUTHOR/MAINTAINER/CREDITS
* [Gaus Surahman](https://www.drupal.org/user/159062)
* [Contributors](https://www.drupal.org/node/2663268/committers)
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


## READ MORE
See the project page on drupal.org:

[Blazy module](http://drupal.org/project/blazy)

See the bLazy docs at:

* [Blazy library](https://github.com/dinbror/blazy)
* [Blazy website](http://dinbror.dk/blazy/)
