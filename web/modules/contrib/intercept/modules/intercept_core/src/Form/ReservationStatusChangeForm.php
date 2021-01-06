<?php

namespace Drupal\intercept_core\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * A form to update the status of a reservation.
 */
class ReservationStatusChangeForm extends FormBase {

  use AjaxHelperTrait;

  /**
   * The status field name.
   *
   * @var string
   */
  protected $fieldName = 'field_status';

  /**
   * The options array.
   *
   * @var array
   */
  protected $options;

  /**
   * Sets the URL options.
   *
   * @param array $options
   *   The array of options. See \Drupal\Core\Url::fromUri() for details on what
   *   it contains.
   *
   * @return $this
   */
  public function setOptions(array $options) {
    $this->options = $options;
    return $this;
  }

  /**
   * Gets the form entity.
   *
   * The form entity which has been used for populating form element defaults.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The current form entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Sets the form entity for populating form element defaults.
   *
   * Usually, the form entity gets updated by EntityFormInterface::submit(),
   * however this may be used to completely exchange the form entity, e.g. when
   * preparing the rebuild of a multi-step form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   *
   * @return $this
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'reservation_status_change_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $entity = $this->getEntity();
    if (!$entity) {
      throw new \RuntimeException('No entity provided to ReservationStatusChangeForm.');
    }
    $form_id = $this->getBaseFormId();
    $form_id .= '_' . $entity->getEntityTypeId() . '_' . $this->fieldName;
    $form_id .= '_' . $entity->id();

    return $form_id;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\intercept_core\Entity\ReservationInterface $entity */
    $entity = $this->entity;
    $options = $this->options;
    $current_value = $entity->get($this->fieldName)->value;

    $form['current_status'] = [
      '#markup' => $options[$current_value],
    ];
    $form['status'] = [
      '#type' => 'radios',
      '#default_value' => $current_value,
      '#options' => $this->options,
    ];
    // Retrieve and add the form actions array.
    $actions = $this->actionsElement($form, $form_state);
    if (!empty($actions)) {
      $form['actions'] = $actions;
    }
    if ($this->isAjax()) {
      // @todo Remove when https://www.drupal.org/node/2897377 lands.
      $form['#id'] = Html::getId($form_state->getBuildInfo()['form_id']);
    }
    return $form;
  }

  /**
   * Returns an array of supported actions for the current entity form.
   *
   * This function generates a list of Form API elements which represent
   * actions supported by the current entity form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An array of supported Form API action elements keyed by name.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // @todo Consider renaming the action key from submit to save. The impacts
    //   are hard to predict. For example, see
    //   \Drupal\language\Element\LanguageConfiguration::processLanguageConfiguration().
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm'],
    ];

    if (!$this->entity->isNew() && $this->entity->hasLinkTemplate('delete-form')) {
      $route_info = $this->entity->toUrl('delete-form');
      if ($this->getRequest()->query->has('destination')) {
        $query = $route_info->getOption('query');
        $query['destination'] = $this->getRequest()->query->get('destination');
        $route_info->setOption('query', $query);
      }
      $actions['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#access' => $this->entity->access('delete'),
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
      ];
      $actions['delete']['#url'] = $route_info;
    }

    return $actions;
  }

  /**
   * Returns the action form element for the current entity form.
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $element = $this->actions($form, $form_state);

    if (isset($element['delete'])) {
      // Move the delete action as last one, unless weights are explicitly
      // provided.
      $delete = $element['delete'];
      unset($element['delete']);
      $element['delete'] = $delete;
      $element['delete']['#button_type'] = 'danger';
    }

    if (isset($element['submit'])) {
      // Give the primary submit button a #button_type of primary.
      $element['submit']['#button_type'] = 'primary';
    }

    $count = 0;
    foreach (Element::children($element) as $action) {
      $element[$action] += [
        '#weight' => ++$count * 5,
      ];
    }

    if (!empty($element)) {
      $element['#type'] = 'actions';
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->isAjax() && $form_state->hasAnyErrors()) {
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ];
      $form['#sorted'] = FALSE;
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $form['#attributes']['data-drupal-selector'] . '"]', $form));
      return $response;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->isAjax()) {
      $form_state->disableRedirect();
    }
    /** @var \Drupal\intercept_core\Entity\ReservationInterface $entity */
    $entity = $this->entity;
    $entity->set($this->fieldName, $form_state->getValue('status'));
    $entity->save();

    if ($this->isAjax()) {
      $this->setEntity($entity);
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $form['#attributes']['data-drupal-selector'] . '"]', $form));
      return $response;
    }
  }

}
