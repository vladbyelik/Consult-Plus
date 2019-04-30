
# Slick Carousel

Visit **/admin/help/slick_ui** once Slick UI installed to read this in comfort.

Slick is a powerful and performant slideshow/carousel solution leveraging Ken
Wheeler's [Slick Carousel](http://kenwheeler.github.io/slick).

Slick has gazillion options, please start with the very basic working
samples from [Slick Example](http://dgo.to/slick_extras) only if trouble to
build slicks. Spending 5 minutes or so will save you hours in building more
complex slideshows.

The module supports Slick 1.6 above until 1.8.1. Versions 1.9.0 and above are
not currently supported. Slick 2.x is just out 9/21/15, and hasn't been
officially supported now, Feb 2019.


## REQUIREMENTS
1. Slick library:
   * Download Slick archive **>= 1.6 && <= 1.8.1** from
     [Slick releases](https://github.com/kenwheeler/slick/releases)
   * Master branch (1.9.0) is not supported. Instead download, rename one of the
     official slick releases to slick. Extract and rename it to "slick", so the
     assets are at:
     + **/sites/../libraries/slick/slick/slick.css**
     + **/sites/../libraries/slick/slick/slick-theme.css** (optional)
     + **/sites/../libraries/slick/slick/slick.min.js**
     + Or any path supported by libraries.module.

2. [Download jqeasing](https://github.com/gdsmith/jquery.easing), so available:

   **/libraries/easing/jquery.easing.min.js**

   This is CSS easing fallback for non-supporting browsers.

3. PHP 5.6+

4. [Blazy](http://dgo.to/blazy), to reduce DRY stuffs, and as a bonus,
   advanced lazyloading such as delay lazyloading for below-fold sliders,
   iframe, (fullscreen) CSS background lazyloading, breakpoint dependent
   multi-serving images, lazyload ahead for smoother UX.
   Check out Blazy installation guides!


## INSTALLATION
### A sequential step below is crucial otherwise missing dependency error:
Skip #1 and #2 if Blazy was in place.  

1. Visit **/admin/modules** and install one of autoload modules:
   * [registry_autoload](http://dgo.to/registry_autoload)
   * [xautoload](http://dgo.to/xautoload)
   * [autoload](http://dgo.to/autoload)

   Save! Do not install Blazy, yet, since Blazy has no hard dependency on any.

2. Install Blazy.

3. Install Slick.

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

More info can be found:
[Drupal 7](http://drupal.org/documentation/install/modules-themes/modules-7)

## UPGRADING / MIGRATING FROM 2.x TO 3.x
* See **docs/UPGRADE.md** for details, or below if you read this at
  **/admin/help/slick_ui**.

## FEATURES
* Fully responsive. Scales with its container.
* Uses CSS3 when available. Fully functional when not.
* Swipe enabled. Or disabled, if you prefer.
* Desktop mouse dragging.
* Fully accessible with arrow key navigation.
* Built-in lazyLoad, and multiple breakpoint options.
* Random, autoplay, pagers, arrows, dots/text/tabs/thumbnail pagers etc...
* Supports pure text, responsive image, iframe, video carousels with
  aspect ratio. No extra jQuery plugin FitVids is required. Just CSS.
* Works with Views, core and contrib fields: Image, Media Entity.
* Optional and modular skins, e.g.: Carousel, Classic, Fullscreen, Fullwidth,
  Split, Grid or a multi row carousel.
* Various slide layouts are built with pure CSS goodness.
* Nested sliders/overlays, or multiple slicks within a single Slick via Views.
* Some useful hooks and drupal_alters for advanced works.
* Modular integration with various contribs to build carousels with multimedia
  lightboxes or inline multimedia.
* Media switcher: Image linked to content, Image to iframe, Image to colorbox,
  Image to photobox.
* Cacheability + lazyload = light + fast.


## SUB-MODULES
The Slick module has several sub-modules:
* slick_ui, included, to manage optionsets, can be uninstalled at production.

* slick_fields, included.

* [slick_views](http://dgo.to/slick_views), to get more complex slides.

* slick_devel, if you want to help testing and developing the Slick.

* slick_example, to get up and running quickly.
  Both are included in [slick_extras](http://dgo.to/slick_extras).


## INTEGRATION
Slick supports enhancements and more complex layouts.

## OPTIONAL
* [Media](http://dgo.to/media), to have richer contents: image, video, or a mix
  of em.
* [Colorbox](http://dgo.to/colorbox), to have grids/slides that open up image/
  video in overlay.
* [Photobox](http://dgo.to/photobox), idem ditto.
* [Picture](http://dgo.to/picture) for more robust responsive image.
* [Paragraphs](http://dgo.to/paragraphs), to get more complex slides at field
  level.  
* [Field Collection](http://dgo.to/field_collection), idem ditto.    
* [Mousewheel](https://github.com/brandonaaron/jquery-mousewheel) at:
  + **/libraries/mousewheel/jquery.mousewheel.min.js**


## OPTIONSETS
To create optionsets, go to:

  [Slick UI](/admin/config/media/slick)

Enable Slick UI sub-module first, otherwise regular **Access denied**.
They will be available at field formatter "Manage display", and Views UI.


## VIEWS AND FIELDS
Slick works with Views and as field display formatters.
Slick Views is available as a style plugin included at slick_views.module.
Slick Fields formatters included as a plugin which supports:
Image, Media, Field Collection, Paragraphs, Text. Read more at:

**/admin/help/slick_fields** or **slick_fields/README.md**.


## PROGRAMATICALLY
See **slick.api.php** for samples.


## CURRENT DEVELOPMENT STATUS
A full release should be reasonable after proper feedbacks from the community,
some code cleanup, and optimization where needed. Patches are very much welcome.

Alpha, Beta, DEV releases are for developers only. Beware of possible breakage.

However if it is broken, unless an update is explicitly required, clearing cache
should fix most issues during DEV phases. Prior to any update, always visit:
**/admin/config/development/performance**

And hit **Clear all caches** button once the new Slick is in place.
Regenerate CSS and JS as the latest fixes may contain changes to the assets.
Have the latest or similar release Blazy to avoid trouble in the first place.


## ROADMAP
* Bug fixes, code cleanup, optimization, and full release.


## HOW CAN YOU HELP?
Please consider helping in the issue queue, provide improvement, or helping with
documentation.

If you find this module helpful, please help back spread the love. Thanks.


## QUICK PERFORMANCE TIPS
* Use lazyLoad **ondemand / anticipated** for tons of images, not
  **progressive**. Unless within an ajaxified lightbox.
* Choose lazyload **Blazy** for carousels below the fold to delay loading them.
* Tick **Optimized** option on the top right of Slick optionset edit page.
* Use image style with regular sizes containing effect **crop** in the name.
  This way all images will inherit dimensions calculated once.
* Disable core library **slick-theme.css** as it contains font **slick** which
  may not be in use when using own icon font at:
  **/admin/config/media/slick/ui**
* Use Blazy multi-serving images, Responsive image, or Picture, accordingly.
* Uninstall Slick UI at production.
* Enable Drupal cache, and CSS/ JS assets aggregation.


## AUTHOR/MAINTAINER/CREDITS

* Slick 8.x by gausarts, and other authors below.
* Slick 7.x-3.x by gausarts, based on Slick 8.x-2.x with Blazy.
* Slick 7.x-2.x by gausarts, inspired by Flexslider with CTools integration.
* Slick 7.x-1.x by arshadcn, the original author.

### CREDITS
* [Gaus Surahman](https://drupal.org/user/159062)
* [Committers](https://www.drupal.org/node/2232779/committers)
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


## READ MORE
See the project page on drupal.org:

[Slick carousel](http://drupal.org/project/slick.)

More info relevant to each option is available at their form display by hovering
over them, and clicking a dark question mark.

See the Slick docs at:

* [Slick website](http://kenwheeler.github.io/slick/)
* [Slick at github](https://github.com/kenwheeler/slick/)
