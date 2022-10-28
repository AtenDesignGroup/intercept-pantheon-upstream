<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_core\AlterableFormTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\duration_field\Service\DurationServiceInterface;
use Drupal\intercept_event\CheckinPeriodInvalidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EventCheckinSettingsForm.
 *
 * @ingroup intercept_event
 */
class EventCheckinSettingsForm extends ConfigFormBase {

  use AlterableFormTrait;


  protected const CONFIG_NAME = 'intercept_event.checkin';

  /**
   * @var \Drupal\duration_field\Service\DurationServiceInterface
   */
  protected $durationService;

  /**
   * The checkin period invalidator service.
   *
   * @var \Drupal\intercept_event\CheckinPeriodInvalidatorInterface
   */
  protected $checkinPeriodInvalidator;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\duration_field\Service\DurationServiceInterface $durationService
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DurationServiceInterface $durationService, CheckinPeriodInvalidatorInterface $checkin_period_invalidator) {
    $this->durationService = $durationService;
    $this->checkinPeriodInvalidator = $checkin_period_invalidator;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('duration_field.service'),
      $container->get('intercept_event.checkin_period_invalidator'),
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
    return 'intercept_event_checkin_settings';
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

    $form['enable'] = [
      '#title' => $this->t('Enable customer self check-in for events'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('enable'),
    ];

    $form['checkin_start'] = [
      '#type' => 'duration',
      '#title' => $this->t('Check-in period opens'),
      '#description' => $this->t(' before the event starts'),
      '#default_value' => $this->durationService->getDateIntervalFromDurationString($config->get('checkin_start')),
      // Only 5 minute increments.
      '#date_increment' => 300,
      '#granularity' => 'h:i',
      '#states' => [
        'visible' => [
          ':input[name="enable"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['checkin_end'] = [
      '#title' => $this->t('Check-in period closes'),
      '#description' => $this->t('after event ends'),
      '#default_value' => $this->durationService->getDateIntervalFromDurationString($config->get('checkin_end')),
      '#type' => 'duration',
      // Only 5 minute increments.
      '#date_increment' => 300,
      '#granularity' => 'h:i',
      '#states' => [
        'visible' => [
          ':input[name="enable"]' => ['checked' => TRUE],
        ],
      ],
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

    $this->checkinPeriodInvalidator->updateCheckinPeriods($values);
    $this->checkinPeriodInvalidator->resetInvalidationPeriod();

    $config->set('enable', $values['enable']);
    $config->set('checkin_start', $this->durationService->getDurationStringFromDateInterval($values['checkin_start']));
    $config->set('checkin_end', $this->durationService->getDurationStringFromDateInterval($values['checkin_end']));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
