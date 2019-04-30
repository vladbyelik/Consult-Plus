<?php

namespace Drupal\blazy\Form;

/**
 * Provides admin form specific to Blazy admin formatter.
 */
class BlazyAdminFormatter extends BlazyAdminFormatterBase {

  /**
   * Defines re-usable form elements.
   */
  public function buildSettingsForm(array &$form, $definition = []) {
    $definition['namespace'] = 'blazy';
    $definition['responsive_image'] = isset($definition['responsive_image']) ? $definition['responsive_image'] : TRUE;
    $forms = isset($definition['forms']) ? $definition['forms'] : [];

    $this->openingForm($form, $definition);

    // This allows Blazy to display texts as a grid, without images.
    if (!empty($forms['image_style']) && !isset($form['image_style'])) {
      $this->imageStyleForm($form, $definition);
    }

    if (!empty($forms['media_switch']) && !isset($form['media_switch'])) {
      $this->mediaSwitchForm($form, $definition);
    }

    if (!empty($forms['grid']) && !isset($form['grid'])) {
      $this->gridForm($form, $definition);

      // Blazy doesn't need complex grid with multiple groups.
      unset($form['preserve_keys'], $form['visible_items']);

      $form['grid']['#description'] = t('The amount of block grid columns for large monitors 64.063em+. <br /><strong>Requires</strong>:<ol><li>Display style.</li><li>A reasonable amount of contents.</li></ol>Leave empty to DIY, or to not build grids.');
    }

    if (!empty($definition['breakpoints']) && !$this->manager()->config('unbreakpoints', FALSE, 'blazy.settings')) {
      $this->breakpointsForm($form, $definition);
    }

    $this->closingForm($form, $definition);
  }

}
