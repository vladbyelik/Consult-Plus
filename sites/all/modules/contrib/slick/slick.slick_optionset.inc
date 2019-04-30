<?php

/**
 * @file
 * Slick default optionset API include file.
 */

use Drupal\slick\Entity\Slick;

/**
 * Implements hook_slick_optionsets().
 */
function slick_slick_optionsets() {
  $optionsets = [];

  $values = Slick::defaultProperties();
  $optionset = Slick::create($values);
  $optionset->disabled = TRUE;
  $optionset->api_version = 3;
  $optionsets['default'] = $optionset;

  return $optionsets;
}
