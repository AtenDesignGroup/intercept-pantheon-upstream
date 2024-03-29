<?php

/**
 * @file
 * Hooks specific to the Clientside Validation module.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the information provided in CvValidator Annotation.
 *
 * @param array $widget_selectors
 *   The array of widget selector plugins, keyed on the machine-readable name.
 */
function hook_clientside_validation_validator_info_alter(array &$widget_selectors) {

}

/**
 * Check whether or not an element should be validated.
 *
 * @param mixed $element
 *   Element array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form State object.
 * @param string $form_id
 *   Form ID.
 */
function hook_clientside_validation_should_validate($element, FormStateInterface &$form_state, $form_id) {

}

/**
 * @} End of "addtogroup hooks".
 */
