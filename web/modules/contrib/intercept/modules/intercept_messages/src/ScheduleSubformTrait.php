<?php

namespace Drupal\intercept_messages;

use Drupal\Core\Form\FormStateInterface;

/**
 * Subform trait for scheduled messages.
 */
trait ScheduleSubformTrait {

  /**
   * Returns the interval description.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The interval description.
   */
  abstract public function getIntervalDescription();

  /**
   * Returns the form array for schedules.
   *
   * @return array
   *   The schedule subform.
   */
  public function scheduleSubform() {
    return [
      'schedule' => [
        'interval' => [
          '#type' => 'duration',
          '#title' => $this->t('Schedule'),
          '#default_value' => $this->configuration['schedule']['interval'],
          '#date_increment' => 900,
          '#granularity' => 'd:h',
          '#description' => $this->getIntervalDescription(),
        ],
      ],
    ];
  }

  /**
   * Sets configuration on submit.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitScheduleSubform(array &$form, FormStateInterface $form_state) {
    if ($interval = $form_state->getValue(['schedule', 'interval'])) {
      $duration_service = \Drupal::service('duration_field.service');
      $this->configuration['schedule']['interval'] = $duration_service->getDurationStringFromDateInterval($interval);
    }
  }

}
