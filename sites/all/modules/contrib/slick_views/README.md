
# Slick Views

This adds two new display styles to views called:

* **Slick Carousel**
* **Slick Grouping**

Similar to how you select **HTML List** or **Unformatted List** as display
styles.

This module doesn't require Views UI to be enabled but it is required if you
want to configure your Views display using Slick carousel through the web
interface. This ensures you can leave Views UI off once everything is setup.


## DEPENDENCIES:

* [Views](http://dgo.to/views)
* [Blazy](http://dgo.to/blazy)
* [Slick](http://dgo.to/slick), 3.x above

Be sure to install the [Slick example](http://dgo.to/slick_extras) to avoid
adventures in the first place.


## INSTALLATION
Install the module as usual, more info can be found
[Drupal 7](http://drupal.org/documentation/install/modules-themes/modules-7)


## MIGRATING FROM 2.x TO 3.x
**Important!**

Migration is not supported, yet. Consider 3.x for new sites only.
The relevant updates are provided, but we haven't thoroughly reviewed the
migration path with large scale of slick instances, nor theme changes.
Migrate at your own risk.

Read more about Slick migration at the main Slick module **docs/UPGRADE.md**.


## SLICK VIEWS 3.x CHANGES
In order to avoid dup themes for the same slick, Slick Views 3.x has:

* Removed **slick-views.tpl.php** for **theme_slick_wrapper()**, or
  **slick-wrapper.tpl.php**.
* Removed **slick_views.theme.inc** for **slick.theme.inc.**.
* Removed **SlickViews.inc** for
  **Drupal\slick_views\Plugin\views\style\SlickViews**.

If you use, or customize the removed templates, the new variable replacements
are provided as follows:

* **rows** becomes **items**.
* **options** becomes **settings**.
* No more **view** object, it is decoupled from Views. If any demand or need for
  this **view** object, we may re-include it along the way.
* The new template suggestions are provided, except for `default` display.

  + **Previously**: slick-views--VIEW_NAME.tpl.php, etc.
  + **Now**: slick-wrapper--VIEW_NAME.tpl.php, etc.


## OPTIONSETS:
Arm yourself with proper optionsets. To create one, go to:

**/admin/config/media/slick**

Be sure to install the Slick UI module first, included in the main Slick module,
otherwise no such URL, and regular access denied error.


## USAGE:
Slick Views comes with two flavors: **Slick Carousel** and **Slick Grouping**.

Go to Views UI **/admin/structure/views**, add a new view, and a block.

### Usage #1
Displaying multiple (rendered) entities for the slides.

* Choose **Slick Carousel** under the Format.
* Choose available optionsets you have created at **/admin/config/media/slick**
* Choose **Rendered entity** or **Content** under **Format > Show**, and its
  View mode.

Themeing is related to their own entity display outside the Views UI.

**Example use case**:

* Blogs, teams, testimonials, case studies sliders, etc.

### Usage #2
Displaying multiple entities using selective fields for the slides.

* Choose **Slick Carousel** under the Format.
* Choose available optionsets you have created at **/admin/config/media/slick**.
* Choose **Fields** under **Format > Show**.
* Add fields, and do custom works or markups. If having a multi-value Image
  field, recommended to only display 1.

Themeing is all yours inside the Views UI.

**Example use case**:

* similar as above.

### Usage #3
Displaying a single multiple-value field in a single entity display for the
slides. Use it either with contextual filter by NID, or filter criteria by NID.

* Under **Pager**, choose **Display a specified number of items** with "1 item".
* Choose **Unformatted list** under the Format, not **Slick Carousel**.
* Add a multi-value Image, Media or Field collection field.
* Click the field under the Fields, choose **Slick Carousel** under Formatter.
* Adjust the settings.
* Be sure to Display "all" or any number > 1 under **Multiple Field settings**.
* Check **Use field template** under **Style settings**, otherwise no field
  visible.

Themeing is mostly taken care of by slick_fields.module in terms of layout, with
the goodness of Views to provide better markups manually.

**Example use case**:

* Front or inner individual slideshow based on the entity ID, or individual user
  slideshow.


### Usage #4
A combination of (#1 or #2) and #3 to build nested slicks.

**Example use case**:

* A home slideshow containing multiple videos per slide for quick overview.
* A large product/ portfolio slideshow containing a grid of slides.
* A news slideshow containing latest related news items per slide.


## GOTCHAS:
If you are choosing a single multi-value field (such as images, Media files, or
Field collection fields) rather displaying various fields from multiple nodes,
make sure to:

* Choose an **Unformatted list** Format, not **Slick Carousel**.
* Choose **Slick Carousel** for the actual field when configuring the field.
* Check **Use field template** under **Style Settings** so that the Slick field
  themeing is picked-up. if confusing, just toggle the option, and see the
  output, you'll know which works.

More info relevant to each option is available at their form display by hovering
over them, and click a dark question mark.


## AUTHOR/MAINTAINER/CREDITS
* [Gaus Surahman](https://drupal.org/user/159062)
* [Contributors](https://www.drupal.org/node/2497045/committers)
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


## READ MORE
See the project page at drupal.org:

[Slick Views](http://drupal.org/project/slick_views)

More info relevant to each option is available at their form display by hovering
over them, and click a dark question mark.

See the Slick docs at:

* [Slick website](http://kenwheeler.github.io/slick/)
* [Slick at Github](https://github.com/kenwheeler/slick/)
