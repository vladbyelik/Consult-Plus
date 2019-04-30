<?php

namespace Drupal\slick_ui\Form;

use Drupal\slick\SlickDefault;
use Drupal\slick\SlickManagerInterface;

/**
 * Defines the Slick admin settings form.
 */
class SlickSettingsForm {

  /**
   * The slick manager service.
   *
   * @var Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * Class constructor.
   */
  public function __construct(SlickManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'slick_settings_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm() {

    $form['module_css'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Enable Slick module slick.theme.css'),
      '#description'   => t('Uncheck to permanently disable the module slick.theme.css, normally included along with skins.'),
      '#default_value' => $this->manager->config('module_css', TRUE),
      '#prefix'        => t("Note! Slick doesn't need Slick UI to run. It is always safe to uninstall Slick UI once done with optionsets."),
    ];

    $form['slick_css'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Enable Slick library slick-theme.css'),
      '#description'   => t('Uncheck to permanently disable the optional slick-theme.css, normally included along with skins.'),
      '#default_value' => $this->manager->config('slick_css', TRUE),
    ];

    $form['deprecated'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Include deprecated functions (BC)'),
      '#description'   => t('Only uncheck once Slick views or Slick entityreference is updated from 2.x to 3.x, _only if using any. Until then keep it checked for backward compatibility (BC).'),
      '#default_value' => $this->manager->config('deprecated', TRUE),
    ];

    $form['deprecated_formatter'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Include deprecated formatters (BC)'),
      '#description'   => t('You can safely uncheck, once the provided update is successful. Verify that Slick carousel (deprecated) has been changed into just Slick carousel at Field UI. Otherwise keep it enabled till you change them. Be sure to clear cache!'),
      '#default_value' => $this->manager->config('deprecated_formatter', TRUE),
    ];

    $form['#submit'][] = 'slick_ui_submit_form';

    return system_settings_form($form);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm($form, &$form_state) {
    $defaults = SlickDefault::formSettings();
    $data = [];

    // Always run typecasting on submit.
    $this->manager->typecast($form_state['values'], 'slick.settings', TRUE);
    foreach ($defaults as $key => $value) {
      if (isset($form_state['values'][$key])) {
        $data[$key] = $form_state['values'][$key];
      }
    }

    // Merge all flat variables into blazy.settings.
    variable_set('slick.settings', $data);

    // Safe to remove old array since already merged above.
    foreach ($defaults as $key => $value) {
      if (isset($form_state['values'][$key])) {
        unset($form_state['values'][$key]);
      }
    }
  }

}
