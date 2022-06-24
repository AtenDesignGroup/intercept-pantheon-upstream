<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_core\AlterableFormTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EventSettingsForm.
 *
 * @ingroup intercept_event
 */
class EventSettingsForm extends ConfigFormBase {

  use AlterableFormTrait;

  protected const CONFIG_NAME = 'intercept_event.list';

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'intercept_event_list_settings';
  }

  /**
   * Defines the settings form for event self check-in.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config(self::CONFIG_NAME);

    $form['toggle_filter'] = [
      '#title' => $this->t('Events listing page filter toggle'),
      '#description' => $this->t('When customers view the listing page of events, how should the toggle appear to them? This toggle allows them to show/hide the list of filters at the top of the page.'),
      '#type' => 'radios',
      '#default_value' => $config->get('toggle_filter') ?? 'expanded',
      '#options' => [
        'collapsed' => 'Collapsed by default',
        'expanded' => 'Expanded by default',
        'hidden' => 'Hidden'
      ]
    ];

    $this->alterForm($form, $form_state);
    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $values = $form_state->cleanValues()->getValues();

    $config->set('toggle_filter', $values['toggle_filter']);

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
