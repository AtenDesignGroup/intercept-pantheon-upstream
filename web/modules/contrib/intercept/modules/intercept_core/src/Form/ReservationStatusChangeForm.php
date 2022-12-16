<?php

namespace Drupal\intercept_core\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A form to update the status of a reservation.
 */
class ReservationStatusChangeForm extends ContentEntityForm {

  use AjaxHelperTrait;

  /**
   * @var AccountProxy
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The options array.
   *
   * @var array
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxyInterface $currentUser, EntityTypeManagerInterface $entity_manager) {
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('current_user'),
      $container->get('entity_type.manager'),
    );
  }

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
    $form_id .= '_' . $entity->getEntityTypeId() . '_field_status';
    $form_id .= '_' . $entity->id();

    return $form_id;
  }

  /**
   * Gets the form submit url.
   *
   * @return URL
   */
  private function getActionUrl() {
    return Url::fromRoute('intercept_room_reservation.reservation.change_status', [
      'room_reservation' => $this->entity->id(),
    ]);
  }

  /**
   * Determines if the current user can change statuses.
   *
   * @return URL
   */
  private function userCanChangeStatus() {
    $entity = $this->entity;
    $operations = ['approve', 'deny', 'archive'];
    $canChangeStatus = FALSE;
    $accessControlHandler = $this->entityTypeManager->getAccessControlHandler($entity->getEntityTypeId());
    $user = $this->currentUser->getAccount();

    foreach ($operations as $operation) {
      if ($accessControlHandler->access($entity, $operation, $user)) {
        $canChangeStatus = TRUE;
      }
    }

    return $canChangeStatus;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\intercept_core\Entity\ReservationInterface $entity */
    $entity = $this->entity;
    $userCanChangeStatus = $this->userCanChangeStatus();

    $statusFieldStorage = $entity->field_status->getFieldDefinition()->getFieldStorageDefinition();
    $property_names = $statusFieldStorage->getPropertyNames();
    $options = $entity->field_status->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getOptionsProvider($property_names[0], $entity)
      ->getSettableOptions($this->currentUser);

    $this->setOptions($options);

    // @todo: Find a more reliable way to get the current value of the entity
    // since this build may run before the entity is saved.
    $current_value = isset($form_state->getUserInput()['status'])
      ? $form_state->getUserInput()['status']
      : $entity->get('field_status')->value;

    $form['current_status'] = [
      '#theme' => 'intercept_reservation_status',
      '#status' => $this->options[$current_value],
      '#prefix' => '<h4>' . t('Status:') . ' ',
      '#suffix' => '</h4>',
    ];

    $form['status'] = [
      '#type' => 'radios',
      '#default_value' => $current_value,
      '#options' => $this->options,
      '#access' => $userCanChangeStatus
    ];

    // Just show the updated "view" (status change) form.
    $form['#attached'] = [
      'library' => [
        'intercept_room_reservation/roomReservationMediator',
      ],
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
    // Ensure the form action remains consistent.
    $form['#action'] = $this->getActionUrl()->toString();
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
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm'],
      '#access' => $this->userCanChangeStatus(),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'url' => $this->getActionUrl(),
      ],
    ];

    if (!$this->entity->isNew() && $this->entity->hasLinkTemplate('delete-form')) {
      // Add copy link if user has permission to copy room reservations.
      $user = $this->currentUser;
      if ($user->hasPermission('copy room reservations')) {
        $copyLink = Link::createFromRoute(t('Copy'), 'intercept_room_reservation.reservation.copy', [
          'room_reservation' => $this->entity->id(),
          'destination' => Url::fromRoute('<current>')->toString(),
        ]);

        $actions['copy'] = [
          '#type' => 'link',
          '#title' => $this->t('Copy'),
          '#weight' => 10,
          '#access' => $this->entity->access('copy'),
          '#attributes' => [
            'class' => ['button', 'use-ajax'],
            'data-dialog-type' => 'dialog',
            'data-dialog-options' => '{"width": "400"}',
            'data-dialog-renderer' => 'off_canvas',
          ],
        ];
        $actions['copy']['#url'] = $copyLink->toRenderable()['#url'];
      }

      if ($user->hasPermission('delete room reservation entities')) {
        // Add a delete button.
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
            'class' => ['button'],
          ],
        ];
        $actions['delete']['#url'] = $route_info;
      }
    }

    return $actions;
  }

  /**
   * Returns the action form element for the current entity form.
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $element = $this->actions($form, $form_state);

    // Order the 'delete' and 'copy' buttons to be last, and in that order.
    $buttons = ['copy', 'delete'];

    foreach ($buttons as $button) {
      if (isset($element[$button])) {
        // Move the delete action as last one, unless weights are explicitly
        // provided.
        $tmp = $element[$button];
        unset($element[$button]);
        $element[$button] = $tmp;
        switch ($button) {
          case 'delete':
            // $element[$button]['#button_type'] = 'danger';
            break;

          case 'copy':
            $element[$button]['#attributes'] = [
              'class' => [
                'use-ajax',
                'button',
              ],
              'data-dialog-type' => 'dialog',
              'data-dialog-options' => '{"width": "400"}',
              'data-dialog-renderer' => 'off_canvas',
            ];
            $user = $this->currentUser;
            $element[$button]['#access'] = $user->hasPermission('copy room reservations');
        }
      }
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
    /** @var \Drupal\intercept_core\Entity\ReservationInterface $entity */
    $entity = $this->entity;
    $entity->set('field_status', $form_state->getValue('status'));
    $entity->save();

    if ($this->isAjax()) {
      return $this->ajaxSubmit($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   *
   * Repeats the ajaxSubmit function code from core, but adds messages wrapper.
   */
  public function ajaxSubmit(array &$form, FormStateInterface &$form_state) {
    $form_state->disableRedirect();
    $entity = $this->entity;

    // @todo: Find a more reliable way to get the current value of the entity
    // since this build may run before the entity is saved.
    $current_value = $form_state->getUserInput()['status']
      ? $form_state->getUserInput()['status']
      : $entity->get($this->fieldName)->value;

    if ($form_state->hasAnyErrors()) {
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ];
      $form['#sorted'] = FALSE;
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $form['#attributes']['data-drupal-selector'] . '"]', $form));
      // Wrap all messages in a container div in order to help with making
      // them sticky.
      $selector = '.messages';
      $response->addCommand(new InvokeCommand($selector, 'wrapAll', ["<div class='messages--wrapper'></div>"]));
    }
    else {
      $response = $this->successfulAjaxSubmit($form, $form_state);
    }
    $form_state->setResponse($response);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array &$form, FormStateInterface &$form_state) {
    $response = new AjaxResponse();
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -1000,
    ];
    $form['#sorted'] = FALSE;
    // Replace the form with an updated version.
    $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $form['#attributes']['data-drupal-selector'] . '"]', $form));
    // Trigger the Save success event.
    $response->addCommand(new InvokeCommand('html', 'trigger', [
      'intercept:saveRoomReservationSuccess',
      ['id' => $this->entity->id()]
    ]));
    return $response;
  }

}
