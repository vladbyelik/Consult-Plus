<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\blazy\BlazyDefault;
use Drupal\blazy\BlazyFormatter;
use Drupal\blazy\BlazyManagerInterface;

/**
 * Blazy formatter plugin initializer.
 */
class FormatterPlugin {

  use BlazyFormatterTrait;

  /**
   * The formatter info.
   *
   * @var array
   */
  protected $formatterInfo;

  /**
   * The formatter type instance.
   *
   * @var object
   */
  protected $formatterType;

  /**
   * Constructs a BlazyFormatter instance.
   */
  public function __construct(BlazyFormatter $formatter, BlazyManagerInterface $manager) {
    $this->formatter = $formatter;
    $this->manager = $manager;
  }

  /**
   * Implements hook_field_formatter_info().
   */
  public function formatterInfo() {
    if (!isset($this->formatterInfo)) {
      $formatters = [];
      foreach (['file', 'image', 'text'] as $type) {
        $class = 'Drupal\blazy\Plugin\Field\FieldFormatter\Blazy' . ucwords($type) . 'Formatter';
        $formatters['blazy_' . $type] = [
          'label' => $type == 'text' ? t('Blazy grid') : t('Blazy'),
          'class' => $class,
          'field types' => $type == 'text' ? BlazyDefault::TEXTS : [$type],
          'settings' => $class::defaultSettings(),
        ];
      }

      $this->formatterInfo = $formatters;
    }
    return $this->formatterInfo;
  }

  /**
   * Return the cached formatter based on the field type.
   */
  public function getFormatter($type, $field, $instance, $namespace = 'blazy') {
    if (!isset($this->formatterType[$type])) {
      $this->formatterType[$type] = $this->getActiveFormatter($type, $field, $instance, $namespace);
    }
    return $this->formatterType[$type];
  }

  /**
   * Return the uncached formatter based on the field type.
   */
  public function getActiveFormatter($type, $field, $instance, $namespace = 'blazy') {
    $plugin_id = $namespace . '_' . $type;
    foreach ($this->formatterInfo() as $key => $info) {
      if ($key == $plugin_id && !empty($info['class'])) {
        $class = $info['class'];
        return new $class($plugin_id, $field, $instance, $this->formatter, $this->manager);
      }
    }
    return FALSE;
  }

}
