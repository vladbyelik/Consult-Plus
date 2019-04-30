<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyVideoTrait;

/**
 * Plugin implementation of the 'Slick File' formatter for Media integration.
 */
class SlickFieldCollectionFormatter extends SlickEntityReferenceFormatterBase {

  use BlazyVideoTrait;

}
