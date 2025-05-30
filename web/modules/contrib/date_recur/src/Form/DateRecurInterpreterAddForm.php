<?php

declare(strict_types=1);

namespace Drupal\date_recur\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\date_recur\Entity\DateRecurInterpreter;

/**
 * Add form for date recur interpreter entities.
 */
class DateRecurInterpreterAddForm extends DateRecurInterpreterEditForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $dateRecurInterpreter = $this->getEntity();

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $dateRecurInterpreter->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $dateRecurInterpreter->id(),
      '#machine_name' => [
        'exists' => [DateRecurInterpreter::class, 'load'],
      ],
    ];

    $options = \array_map(
      static fn (array $definition): string => (string) $definition['label'],
      $this->dateRecurInterpreterPluginManager->getDefinitions(),
    );
    $form['plugin_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Plugin'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    /** @var string|null $pluginType */
    $pluginType = $form_state->getValue('plugin_type');
    if ($pluginType !== NULL && $pluginType !== '') {
      $dateRecurInterpreter->setPlugin($pluginType);
      $form = parent::form($form, $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (!isset($form['configure'])) {
      $form_state->setRebuild();
    }
    else {
      $pluginType = $form_state->getValue('plugin_type');
      $this->getEntity()->setPlugin($pluginType);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);

    /** @var string|null $pluginType */
    $pluginType = $form_state->getValue('plugin_type');
    if ($pluginType === NULL || $pluginType === '') {
      $actions['submit']['#value'] = $this->t('Next');
    }

    return $actions;
  }

}
