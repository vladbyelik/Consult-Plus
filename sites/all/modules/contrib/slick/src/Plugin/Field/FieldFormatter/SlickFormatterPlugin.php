<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\blazy\Plugin\Field\FieldFormatter\FormatterPlugin;
use Drupal\slick\SlickDefault;
use Drupal\slick\SlickFormatterInterface;
use Drupal\slick\SlickManagerInterface;

/**
 * Slick formatter initializer.
 */
class SlickFormatterPlugin extends FormatterPlugin {

  /**
   * Checks if the view is prepared.
   *
   * @var bool
   */
  protected $isViewPrepared;

  /**
   * Constructs a SlickPlugin instance.
   */
  public function __construct(SlickFormatterInterface $formatter, SlickManagerInterface $manager) {
    parent::__construct($formatter, $manager);
    $this->formatter = $formatter;
    $this->manager = $manager;
  }

  /**
   * Implements hook_field_formatter_info().
   */
  public function formatterInfo() {
    if (!isset($this->formatterInfo)) {
      $formatters = [];
      $fields = SlickDefault::FIELDS;
      // The new formatter is now suffixed with field type to have a class each.
      foreach ($fields as $type) {
        $name = $type == 'text' ? 'Text' : ($type == 'field_collection' ? 'FieldCollection' : ucwords($type));
        $class = 'Drupal\slick\Plugin\Field\FieldFormatter\Slick' . $name . 'Formatter';

        $formatters['slick_' . $type] = [
          'label' => t('Slick Carousel'),
          'class' => $class,
          'field types' => $type == 'text' ? SlickDefault::TEXTS : [$type],
          'settings' => $class::defaultSettings(),
        ];
      }

      // @todo remove deprecated formatter post release on succesful update.
      if ($this->manager->config('deprecated_formatter', TRUE, 'slick.settings')) {
        array_pop($fields);
        $formatters['slick'] = [
          'label' => t('Slick Carousel (deprecated)'),
          'field types' => $fields,
          'settings' => SlickDefault::extendedSettings(),
        ];
      }

      $this->formatterInfo = $formatters;
    }
    return $this->formatterInfo;
  }

  /**
   * Return the requested formatter based on the field type.
   */
  public function getFormatter($type, $field, $instance, $namespace = 'slick') {
    if (!isset($this->formatterType[$type])) {
      $this->formatterType[$type] = FALSE;
      if ($formatter = $this->getActiveFormatter($type, $field, $instance, $namespace)) {
        $this->formatterType[$type] = $formatter;
      }
      // @todo remove for Slick carousel (deprecated) on succesful update.
      elseif ($this->manager->config('deprecated_formatter', TRUE)) {
        foreach (SlickDefault::FIELDS as $entity_type) {
          if ($type == $entity_type) {
            $plugin_id = $namespace . '_' . $type;
            $class = $entity_type == 'field_collection' ? 'FieldCollection' : ucwords($entity_type);
            $class = 'Drupal\slick\Plugin\Field\FieldFormatter\Slick' . $class . 'Formatter';
            $this->formatterType[$type] = new $class($plugin_id, $field, $instance, $this->formatter, $this->manager);
            break;
          }
        }
      }
    }
    return $this->formatterType[$type];
  }

  /**
   * Implements hook_field_formatter_prepare_view().
   */
  public function prepareView($entity_type, $entities, $field, $instances, &$items, $displays) {
    if (!isset($this->isViewPrepared[$field['type']])) {
      // Stolen from entityreference which stole from field.api.php to work with
      // the supported entity types. We do this to have one base class for
      // entities, BlazyEntityBase.
      // At D8, this is taken care of by core entity reference, but not at D7.
      if (in_array($field['type'], ['field_collection', 'paragraphs'])) {
        $is_slick = FALSE;
        foreach ($displays as $id => $display) {
          if ($display['type'] == 'slick' || $display['type'] == 'slick_' . $field['type']) {
            $is_slick = TRUE;
            break;
          }
        }

        if ($is_slick) {
          // @todo avoid hard-coded here, okay for now as we limit field types.
          $target_type = $field['type'] . '_item';
          $column = 'value';
          $target_ids = [];

          // Collect every possible entity attached to any of the entities.
          foreach ($entities as $id => $entity) {
            foreach ($items[$id] as $delta => $item) {
              if (isset($item[$column])) {
                $target_ids[] = $item[$column];
              }
            }
          }

          $target_entities = [];
          if ($target_ids) {
            $target_entities = entity_load($target_type, $target_ids);
          }

          // Iterate through the fieldable entities again to attach the data.
          foreach ($entities as $id => $entity) {
            $rekey = FALSE;

            foreach ($items[$id] as $delta => $item) {
              // Check whether the referenced entity could be loaded.
              if (is_array($target_entities) && isset($target_entities[$item[$column]]) && isset($target_entities[$item[$column]])) {
                // Replace the instance value with the entity data.
                $items[$id][$delta]['entity'] = $target_entities[$item[$column]];
                // Check whether the user has access to the referenced entity.
                $has_view_access = (entity_access('view', $target_type, $target_entities[$item[$column]]) !== FALSE);
                $has_update_access = (entity_access('update', $target_type, $target_entities[$item[$column]]) !== FALSE);
                $items[$id][$delta]['access'] = ($has_view_access || $has_update_access);
              }
              // Else, unset the instance value, as the entity does not exist.
              else {
                unset($items[$id][$delta]);
                $rekey = TRUE;
              }
            }

            if ($rekey) {
              // Rekey the items array.
              $items[$id] = array_values($items[$id]);
            }
          }
        }
      }

      $this->isViewPrepared[$field['type']] = TRUE;
    }
    return $this->isViewPrepared[$field['type']];
  }

}
