<?php

namespace Drupal\intercept_room_reservation\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_core\AlterableFormTrait;
use Drupal\intercept_core\Form\UserPermissionsForm;
use Drupal\intercept_core\ReservationManager;
use Drupal\intercept_room_reservation\Entity\RoomReservation;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RoomReservationSettingsForm.
 *
 * @ingroup intercept_room_reservation
 */
class RoomReservationSettingsForm extends ConfigFormBase {

  use AlterableFormTrait;

  protected const CONFIG_NAME = 'intercept_room_reservation.settings';

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The user role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * The room reservation storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $roomReservationStorage;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->roleStorage = $this->entityTypeManager->getStorage('user_role');
    $this->roomReservationStorage = $this->entityTypeManager->getStorage('room_reservation');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager')
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
    return 'room_reservation_settings';
  }

  /**
   * Defines the settings form for Room reservation entities.
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
    $config = $this->config('intercept_room_reservation.settings');

    $form['agreement_text'] = [
      '#title' => $this->t('Terms and conditions agreement'),
      '#type' => 'text_format',
      '#default_value' => $config->get('agreement_text.value'),
      '#format' => $config->get('agreement_text.format'),
    ];

    $form['reservation_limit'] = [
      '#title' => $this->t('Room reservation limit'),
      '#type' => 'number',
      '#default_value' => $config->get('reservation_limit'),
    ];

    $form['reservation_limit_text'] = [
      '#title' => $this->t('Room reservation limit user message'),
      '#type' => 'text_format',
      '#default_value' => $config->get('reservation_limit_text.value'),
      '#format' => $config->get('reservation_limit_text.format'),
    ];

    $form['advanced_reservation_limit'] = [
      '#title' => $this->t('Advanced room reservation limit'),
      '#type' => 'number',
      '#field_suffix' => $this->t('days'),
      '#default_value' => $config->get('advanced_reservation_limit'),
      '#description' => $this->t('Set the number of days in advance in which customers may submit room reservations. Example: entering "30" will allow customers to reserve rooms up to 30 days ahead of time. Enter "0" for no limit.'),
      '#attributes' => [
        'step' => 1,
        'min' => 0,
      ],
    ];

    $form['advanced_reservation_limit_text'] = [
      '#title' => $this->t('Room reservation advanced limit user message'),
      '#type' => 'text_format',
      '#default_value' => $config->get('advanced_reservation_limit_text.value'),
      '#format' => $config->get('advanced_reservation_limit_text.format'),
    ];

    $form['last_reservation_before_closing'] = [
      '#title' => $this->t('Last Reservation Before Closing'),
      '#type' => 'number',
      '#field_suffix' => $this->t('minutes'),
      '#default_value' => $config->get('last_reservation_before_closing'),
      '#description' => $this->t('Set the number of minutes before location closing when room reservations are no longer allowed.'),
      '#attributes' => [
        'step' => 5,
        'min' => 0,
        'max' => 55,
      ],
    ];

    $form['reservation_barred_text'] = [
      '#title' => $this->t('Room reservation barred user message'),
      '#type' => 'text_format',
      '#default_value' => $config->get('reservation_barred_text.value'),
      '#format' => $config->get('reservation_barred_text.format'),
    ];

    $view_modes = $this->entityDisplayRepository->getViewModeOptions('room_reservation');
    $form_modes = $this->entityDisplayRepository->getFormModeOptions('room_reservation');

    $form['off_canvas'] = [
      '#type' => 'details',
      '#title' => $this->t('Off-canvas dialog settings'),
      '#open' => TRUE,
      '#collapsible' => TRUE,
      '#tree' => FALSE,
    ];

    $form['off_canvas']['off_canvas_view_mode'] = [
      '#type' => 'select',
      '#title' => t('Off-canvas dialog view mode'),
      '#options' => $view_modes,
      '#default_value' => $config->get('off_canvas_view_mode'),
    ];

    $form['off_canvas']['off_canvas_form_mode'] = [
      '#type' => 'select',
      '#title' => t('Off-canvas dialog form mode'),
      '#options' => $form_modes,
      '#default_value' => $config->get('off_canvas_form_mode'),
    ];

    $form['email'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Emails'),
      '#description' => $this->t('These emails are triggered when the status of a reservation is changed.'),
      '#tree' => TRUE,
    ];

    $emails = ReservationManager::emails();

    $room_reservation = RoomReservation::create([]);
    $status_options = \Drupal::service('entity_field.manager')
      ->getFieldStorageDefinitions('room_reservation')['field_status']
      ->getOptionsProvider('value', $room_reservation)
      ->getSettableOptions($this->currentUser());

    $options = ['any' => $this->t('Any'), 'empty' => $this->t('Empty (new reservation)')] + $status_options;

    foreach ($emails as $key => $title) {
      $form[$key] = [
        '#type' => 'details',
        '#title' => $title,
        '#group' => 'email',
        '#tree' => TRUE,
      ];
      $form[$key]['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $this->getConfigValue($key, 'subject'),
        '#maxlength' => 180,
      ];
      $form[$key]['body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
        '#default_value' => $this->getConfigValue($key, 'body'),
        '#rows' => 15,
      ];
      $form[$key]['status_original'] = [
        '#title' => $this->t('Original status'),
        '#type' => 'select',
        '#multiple' => TRUE,
        '#options' => $options,
        '#description' => $this->t('The previous status of the reservation. If no value is selected, the email will be inactive.'),
        '#default_value' => $this->getConfigValue($key, 'status_original'),
      ];
      $form[$key]['status_new'] = [
        '#title' => $this->t('New status'),
        '#type' => 'select',
        '#multiple' => TRUE,
        '#options' => $options,
        '#description' => $this->t('The new status of the reservation. If no value is selected, the email will be inactive.'),
        '#default_value' => $this->getConfigValue($key, 'status_new'),
      ];

      $form[$key]['user'] = [
        '#title' => $this->t('User'),
        '#type' => 'select',
        '#options' => [
          'reservation_user' => $this->t('User the reservation is for'),
          'reservation_author' => $this->t('Reservation author user'),
          'user_role' => $this->t('User with a specified role'),
        ],
        '#empty_option' => $this->t('- Any user -'),
        '#description' => $this->t('Send email for a specific logged in user.'),
        '#default_value' => $this->getConfigValue($key, 'user'),
      ];

      $form[$key]['user_role'] = [
        '#title' => $this->t('User role'),
        '#type' => 'select',
        '#options' => $this->userRoleOptions(),
        '#multiple' => TRUE,
        '#default_value' => $this->getConfigValue($key, 'user_role'),
        '#states' => [
          'visible' => [
            ':input[name="' . $key . '[user]"]' => ['value' => 'user_role'],
          ],
        ],
      ];
    }

    $this->alterForm($form, $form_state);
    return $form;
  }

  /**
   * Gets the current User roles.
   *
   * @return array
   *   The user_role options.
   */
  private function userRoleOptions() {
    $intercept_roles = UserPermissionsForm::roles();

    $options = array_map(function (RoleInterface $role) {
      return $role->label();
    }, $this->roleStorage->loadMultiple($intercept_roles));
    return $options;
  }

  /**
   * Gets the text format for a config field.
   */
  private function getTextFormat($config_name, $subfield) {
    $config = $this->config(self::CONFIG_NAME)->get($config_name);
    $default_value = $subfield == 'value' ? '' : 'basic_html';
    return !empty($config) && !empty($config[$subfield]) ? $config[$subfield] : $default_value;
  }

  /**
   * Helper function to traverse into the email config values.
   *
   * @return mixed
   *   The config value.
   */
  private function getConfigValue($key, $key1 = NULL, $key2 = NULL, $default = '') {
    $value = $default;
    if (!$config = $this->config(self::CONFIG_NAME)->get("email.$key")) {
      return $value;
    }
    if ($key1 && isset($config[$key1])) {
      $value = $config[$key1];
    }
    if ($key2 && isset($value[$key2])) {
      $value = $value[$key2];
    }
    return $value;
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
    foreach ($values as $key => $info) {
      if (!empty($info["{$key}__active_tab"])) {
        continue;
      }
      $key = !empty($form[$key]['#group']) ? $form[$key]['#group'] . ".{$key}" : $key;
      $config->set($key, $info);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
