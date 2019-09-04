<?php

namespace Drupal\intercept_core;

use Drupal\Core\Form\FormStateInterface;

trait AlterableFormTrait {

  protected $alters = [];

  protected function alterForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->alters as $callback) {
      call_user_func_array($form_state->prepareCallback($callback), [&$form, &$form_state]);
    }
  }

  public function addAlter($callback) {
    $this->alters[] = $callback;
    return $this;
  }

}
