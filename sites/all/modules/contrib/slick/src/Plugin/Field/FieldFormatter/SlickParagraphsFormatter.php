<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyVideoTrait;

/**
 * Plugin implementation of the 'Slick File' formatter for Media integration.
 */
class SlickParagraphsFormatter extends SlickEntityReferenceFormatterBase {

  use BlazyVideoTrait;

}
