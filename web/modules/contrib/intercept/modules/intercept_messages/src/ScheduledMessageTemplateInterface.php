<?php

namespace Drupal\intercept_messages;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Intercept message template plugins.
 */
interface ScheduledMessageTemplateInterface extends InterceptMessageTemplateInterface {

  /**
   * Returns the interval description.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The interval description.
   */
  public function getIntervalDescription();

  /**
   * Returns the form array for schedules.
   *
   * @return array
   *   The schedule subform.
   */
  public function scheduleSubform();

  /**
   * Sets configuration on submit.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitScheduleSubform(array &$form, FormStateInterface $form_state);

}
