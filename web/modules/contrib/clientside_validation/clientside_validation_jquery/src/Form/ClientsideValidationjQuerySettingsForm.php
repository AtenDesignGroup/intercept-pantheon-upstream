<?php

namespace Drupal\clientside_validation_jquery\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implement Class to Validate Clientside jQuerySettingsForm.
 */
class ClientsideValidationjQuerySettingsForm extends ConfigFormBase {

  /**
   * An http client.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClient;

  /**
   * A settings object.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var ClientsideValidationjQuerySettingsForm $instance */
    $instance = parent::create($container);

    $instance->httpClient = $container->get('http_client_factory');
    $instance->settings = $container->get('settings');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'clientside_validation_jquery_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['clientside_validation_jquery.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('clientside_validation_jquery.settings');

    $form['use_cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use JS from CDN'),
      '#description' => $this->t('CDN is used by default if JS not added into libraries.'),
      '#default_value' => $config->get('use_cdn'),
    ];

    $form['cdn_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CDN Base URL'),
      '#description' => $this->t(
          'CDN to use (along with version in URL). E.g. @url', [
            '@url' => '//cdn.jsdelivr.net/npm/jquery-validation@1.21.0/dist/',
          ]
      ),
      '#required' => TRUE,
      '#default_value' => $config->get('cdn_base_url'),
    ];

    $form['validate_all_ajax_forms'] = [
      '#type' => 'select',
      '#options' => [
        1 => $this->t('Yes'),
        2 => $this->t('No'),
      ],
      '#title' => $this->t('Validate all forms before AJAX submit'),
      '#description' => $this->t('All forms with class "cv-validate-before-ajax" will be validated by default'),
      '#required' => TRUE,
      '#default_value' => $config->get('validate_all_ajax_forms'),
    ];

    $form['force_html5_validation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force HTML5 validation'),
      '#description' => $this->t('Force HTML5 validation on form first before other validation'),
      '#default_value' => $config->get('force_html5_validation'),
    ];

    $form['force_validate_on_blur'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Validate on Blur/focusout'),
      '#default_value' => $config->get('force_validate_on_blur'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('clientside_validation_jquery.settings');

    $config->set('use_cdn', $form_state->getValue('use_cdn'));
    $config->set('cdn_base_url', $form_state->getValue('cdn_base_url'));
    $config->set('validate_all_ajax_forms', $form_state->getValue('validate_all_ajax_forms'));
    $config->set('force_html5_validation', $form_state->getValue('force_html5_validation'));
    $config->set('force_validate_on_blur', $form_state->getValue('force_validate_on_blur'));

    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->getErrors()) {
      return;
    }

    $values = $form_state->getValues();

    // Validate if library exists if use CDN is set to false.
    if (empty($values['use_cdn'])) {
      $library_exists = file_exists('libraries/jquery-validation/dist/jquery.validate.min.js');

      if (empty($library_exists)) {
        $form_state->setErrorByName('use_cdn', $this->t('Please make sure JS is available in Drupal Libraries. Check README in module folder for more details.'));
      }
    }

    // Validate if the CDN url is proper.
    $cdn_url = $values['cdn_base_url'] . 'jquery.validate.min.js';

    $client = $this->httpClient->fromOptions($this->settings->get('http_client_config') ?? []);

    try {
      $request = $client->request('GET', $cdn_url);
      if ($request->getStatusCode() != 200) {
        $form_state->setErrorByName(
          'cdn_base_url', $this->t(
            'CDN URL seems invalid. @file not accessible on @url. Use the URL in this format @format.', [
              '@file' => 'jquery.validate.min.js',
              '@url' => $cdn_url,
              '@format' => '//cdn.jsdelivr.net/npm/jquery-validation@1.21.0/dist/',
            ]
          )
        );
      }
    }
    catch (\Exception $e) {
      $form_state->setErrorByName(
        'cdn_base_url', $this->t(
          'CDN URL seems invalid. @file not accessible on @url. Use the URL in this format @format.', [
            '@file' => 'jquery.validate.min.js',
            '@url' => $cdn_url,
            '@format' => '//cdn.jsdelivr.net/npm/jquery-validation@1.21.0/dist/',
          ]
        )
      );
    }
  }

}
