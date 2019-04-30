
***
***

## <a name="upgrading"></a>UPGRADING / MIGRATING FROM 2.x TO 3.x

## Important!
Migration is not supported, yet. Consider 3.x for new sites only.

Although the relevant updates are provided, but we haven't thoroughly reviewed
the migration path with tons of slick instances, nor considering theme changes.
This branch is a backport of 8.x, so it may not be compatible with the previous
branch. It is a complete rewrite. Although Slick 3.x strives to stick
to 2.x convention, but may break it here and there.

The only advantage of this branch is Blazy, autoload, and a few 8.x goodness.

Migrate at your own risk! Be prepared this update may kill your kittens.

### If you insist, please carefully read below to reduce potential errors:

1. Please use a dev environment so you can revert without affecting production.
   Do regular backup routines.

2. Open **/admin/config/development/performance** or be ready to run
   **drush cc** to clear cache for any potential error.

3. Backup and **remove** custom slick-related .tpl files from your themes.
   Update them earlier or later as per changes below to avoid unknown issues.

   **This is currently the only major issue with the upgrade path.**

   Although Slick 3.x strives to stick to 2.x convention, but not always.

4. **Clear cache** to prevent the .tpl removal from blocking the update process.
   If you don't modify slick .tpl files, please skip steps #3, #4.

5. Running `/update.php` or `drush updb` is required.

6. Once all slick-related modules that you are using support Slick 3.x branch
   (Slick Views, Slick Extras, Slick entityreference, etc), you can disable
   deprecated functions at `/admin/config/media/slick/ui`.
   Until then keep it ENABLED!

### A sequential step below is critical otherwise potential errors:

**Do not update Slick to 3.x branch, yet, till you have Blazy installed.**

Skip #1 and #2 if Blazy was in place.

1. Install one of autoload modules: **registry_autoload, autoload, xautoload.**
   Skip if you already have one.

   Save! Do not install Blazy, yet, since Blazy has no hard dependency on any.

2. Install Blazy 7.x.
   Do not update Slick to 3.x branch, yet!

3. Update Slick to 3.x branch.
   Postpone Slick Views, Slick Extras, till the main Slick module is updated.

4. Run `/update.php` or `drush updb`:
   * to bulk convert old optionsets which were stdClass instances to be
     `\Drupal\slick\Entity\Slick` class instances.
   * to change the deprecated Slick formatter into the new ones automatically.

5. Note the errors, if any, and please report. Or continue below.

6. Update **Slick Views**, **Slick Extras** into 3.x branch, if you use it,
   else broken displays due to some stock skins moving.

   Run `/update.php` or `drush updb` again.

   If you use stock skins, install Slick Extras as some skins are moved into
   **Slick Extras**, not **Slick Example**.

   If you use **Slick Views** with the old Views template suggestions, and or
   modify Slick Views templates, please review your modifications here:
   https://cgit.drupalcode.org/slick_views/tree/README.md?h=7.x-3.x#n36

   Non-updated Slick contribs, says Slick entityreference, should be compatible
   with Slick 3.x till they have time to update to the new Slick codebase.

7. Regenerate CSS/ JS at `/admin/config/development/performance` as this affects
   libraries changes.

   **Routines**: Uncheck options. Save. Clear cache. Verify.

***
Shortly, do it one at a time. Not all at once like usual. If you are feeling
adventurous, feel free to do it all at once at your own risk. If successful,
kindly let us know, so that we can ditch deprecated formatters, and functions
prior to full release rather than keeping around the codebase.

If you modified Slick templates, update templates, see SLICK 3x CHANGES below.
***

### If you stored optionsets in codebase, ignore if you don't:
1. Re-export them as there are a few changes to database fields. Step #4 above
   just did automatic conversions, not manual re-export, of course.
   See `slick_example.slick_optionset.inc` 3.x branch for samples.
2. Once re-exported, revert back all database optionsets into codebase at:

   **/admin/config/media/slick**

BC layer is still provided till 3.x full release. If we missed, please report.
The more successful reports, the faster this new branch reaches full release.
Otherwise we may have to do more homeworks for a smoother migration.


## SLICK 3x CHANGES
1. **Database**
   * Added two new columns `collection` + `optimized` (previously in options).

2. **Theme/ template files**
   * Added **theme_slick_thumbnail()** and **theme_slick_vanilla()** to reduce
     complexity at **theme_slick_slide()**, and more fine grained theming.
     No .tpl files, just theme functions.
   * Added **slick-wrapper.tpl.php** for Views template suggestions.
   * Removed **theme_slick_image()** for **theme_blazy()**.
   * Removed **slick-grid.tpl.php** for **theme_slick_grid()**.
   * Changed **slick.tpl.php** for **theme_slick()** by default.
   * Changed and removed **slick-item.tpl.php** for **slick-slide.tpl.php**
     which is not actually used by default.
   * Changed **theme_slick_item()** into **theme_slick_slide()** for clarity
     like 8.x.

   Slick 3.x has only 3 template files:
   `slick-slide.tpl.php`, `slick.tpl.php`, `slick-wrapper.tpl.php` which are not
   used by default till you copy to your theme, only if you need to. If not,
   ignore. Slick now uses theme functions instead.

   Since they are both PHP, not Twig, it makes no big difference as they are
   both equally themeable, except probably some performance gain.

   Only if you modified any, update them accordingly. If not, ignore.
   * The `slick-wrapper.tpl.php` is provided to support the template suggestions
     as provided by Views UI when using Slick Views.
     See `slick_views_preprocess_slick_wrapper()`.
   * The `slick-slide.tpl.php` file has different render array.
   * Update the removed `slick-grid.tpl.php` for `theme_slick_grid()`.

     Previously has `item` and `caption` as separate variables. Now merged into
     `item` to be `item.slide` and `item.caption`.

   It is simply to make generic and consistent variables for new slick themes:
   `theme_slick_vanilla()`, and `theme_slick_thumbnail()` where both may or may
   not have captions.

3. **Skins**
   * Like D8, extra skins are moved into slick_extras.module, not slick_example.
     Enable slick_extras if using stock skins. This ensures you can uninstall
     slick_example, but left slick_extras enabled just for skins.
   * Skins are no longer stored at **MY_MODULE.slick.inc**. Now moved into a
     class file says, src/SlickMyModuleSkin.php which implements
     SlickSkinInterface.

     Registering skins in the .module file is still the same, except its
     content is now moved into a class file SlickMyModuleSkin.php, and replaced
     by a fully qualified class name here:

    ````
      function MY_MODULE_slick_skins_info() {
        // Previously this contains skin definitions. Now moved into a class
        // file with similar structure, only stored within a method `skins()`.
        return '\Drupal\MY_MODULE\SlickMyModuleSkin';
      }
    ````

     **See samples for details:**
     * src/SlickSkin.php
     * slick_extras/src/SlickExtrasSkin.php
     * slick_example/src/SlickExampleSkin.php

     Check out slick.info file for sample to hook into one of autoloader
     modules. And clear cache as usual as these skins are cached.

3. **Formatter IDs**

   The formatter ID is no longer `slick`. It is now suffixed with the field
   type: `slick_image`, `slick_file`, etc. Thus, formatter displays need
   changing from the `Slick carousel (deprecated)` into `Slick carousel`.

   The update at slick_fields.install has been provided to migrate to the new
   Slick formatter. Verify Slick formatters updated. Once the update works
   smoothly, you can disable the old formatter at:

   [/admin/config/media/slick/ui](/admin/config/media/slick/ui)

   Otherwise you may have to change it manually.

   The reason for the change is now Slick uses classes, previously used
   `module_load_include()`. So it needs a unique plugin ID to begin with.
   The `module_load_include()` is awesome to make a module modular without
   bloating the runtime functions or module file, nor hard dependencies thanks
   to Drupal hooks which simply mean only a portion of codes are executed at
   runtime, not all at once. Classes are just more awesome with autoload still
   no hard dependencies.

4. **Libraries**

   Most Slick libraries, like D8, are now moved into Blazy to be re-usable for
   non-carousel formatters or Views styles. If you use any manually in code,
   please update it to reference Blazy libraries instead.
   Slick 3.x has now only slick.colorbox.js and slick.load.js.

5. **Optionset/ API**. If you don't store optionsets in codebase, skip below.
  + Options under `general` are removed. Some merged into the main options.
    If you added a custom wrapper class under `General` option, please use
    preprocess now, or override `theme_slick()` accordingly.
  + Renamed ctools plugin api from `slick_default_preset` to `slick_optionset`.
    Update your `hook_ctools_plugin_api()` to use `slick_optionset`.

    With this, also changed `MY_MODULE.slick_default_preset.inc` into
    `MY_MODULE.slick_optionset.inc`.

    If using bulk exporter, this is already taken care of by ctools exporter,
    except for file renaming.
  + Renamed `hook_slick_default_presets()` into `hook_slick_optionsets()`.
    If using bulk exporter, this is already taken care of by ctools exporter.

6. **Optionset Exports**    
   Optionset now uses a new `Drupal\slick\Entity\Slick class`, not `stdClass`.
   Note the change on the exporter function name:
    + **Old hook**:
    ````
        function MY_MODULE_slick_default_presets() {
          $preset = new stdClass();
          // ...
        }
    ````

    + **New hook**:
    ````
        use Drupal\slick\Entity\Slick;
        function MY_MODULE_slick_optionsets() {
          $optionset = new Slick();
          // ...
        }
    ````

    + **Or if using bulk exporter**:
    ````
        function MY_MODULE_slick_optionsets() {
          $optionset = new Drupal\slick\Entity\Slick();
          // ...
        }
    ````

    **Important!** Add relevant autoload directives into your MODULE.info file.
    See below for details.
    If you modify **Slick Views** template, please review the breaking changes:
    https://cgit.drupalcode.org/slick_views/tree/README.md?h=7.x-3.x#n36

7. If you were using Picture in Slick carousel, enable Picture integration at:

   **/admin/config/media/blazy**


## MIGRATING SLICK (CUSTOM) MODULES FROM 2.x TO 3.x
You don't need to extend classes unless it makes your life easier. Please stick
to 2.x. Or gradually update and replace deprecated functions for the new ones.

See `slick.deprecated.inc`, and `slick.module` files for details and replacement
functions/ methods as those marked **@deprecated** shall be removed before or
after full release, depending on the needs.

Below is only needed if you import classes or optionsets in your module.
Otherwise ignore. In your MODULE.info file, include one of the supported
autoloader modules along with their required directives, e.g.:
```
; This is for autoload.module
autoload = TRUE

; This is for registry_autoload.module
registry_autoload[] = PSR-4
```

**See samples:**

* blazy.info
* slick.info
* slick_fields.info
...

No need to put hard dependencies on an autoloader as Slick depends on Blazy
which will lock an autoloader as its dependencies once found.

## KNOWN ISSUES WITH AUTOLOADER
* **autoload**: must **ALWAYS** run `drush aur` and `drush cc` on Blazy, or
  Slick activation, or fatal. The same procedure applies whenever blazy-related
  modules are activated, or adding new classes. Especially during DEV, Alpha,
  Beta.
* **xAutoload**: was reported missing SlickManager class here:
  https://www.drupal.org/project/slick/issues/3045487

If not using drush, consider the other two:

**registry_autoload**, **xautoload**.

Nobody reports any issue with **registry_autoload** so far, so this is the only
no-issue module for now, and so recommended.

This 3.x branch is meant to take advantage of Blazy features, and DRY.

## KNOWN ISSUES WITH UPGRADING
1. The provided Slick fields formatter update is only for those stored via Field
UI. If you use Slick formatters within Views UI, such as seen at Slick example,
the update is performed at Slick Views. Run Slick Views update, too.

2. The provided updates are not designed for large sets of slicks such as for
Slick Views with tons of carousels. We should use batch to avoid potential time
out issues. That is why it was strongly recommended to test it out at DEV
environment. Patches are welcome. Thanks!

## REPORTS
Please review for any potential breakage or glitches, and report to help iron
out issues so that others can benefit from your reports as well.
Successful or failed update reports will surely help, and help speed up this
module progress into a more solid slideshow, or carousel solution.
Thanks!
