<?php

/**
 * @file
 * This file is not used by Slick 3.x.
 *
 * @deprecated to be removed once Slick Views, etc. migrated to 3.x.
 */

use Drupal\slick\SlickDefault;

/**
 * To be removed.
 */
function slick_get_global_default_settings() {
  return SlickDefault::extendedSettings();
}

/**
 * To be removed.
 */
function slick_build_grids(array $build, &$settings) {
  // No longer relevant, SlickManager::slick() takes care of it now, but kept
  // for direct call till removed.
  return slick()->buildGrid($build, $settings);
}
