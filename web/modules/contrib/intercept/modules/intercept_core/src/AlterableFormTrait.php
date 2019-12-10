<?php

namespace Drupal\intercept_core;

use Drupal\Core\Form\FormStateInterface;

/**
 * Allows forms to be altered.
 */
trait AlterableFormTrait {

  /**
   * A list of callable function names.
   *
   * @var array
   */
  protected $alters = [];

  /**
   * Alters a form given a list of functions.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function alterForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->alters as $callback) {
      call_user_func_array($form_state->prepareCallback($callback), [&$form, &$form_state]);
    }
  }

  /**
   * Add an function name to the list of alter callbacks.
   *
   * @param string $callback
   *   The function name.
   */
  public function addAlter($callback) {
    $this->alters[] = $callback;
    return $this;
  }

}
