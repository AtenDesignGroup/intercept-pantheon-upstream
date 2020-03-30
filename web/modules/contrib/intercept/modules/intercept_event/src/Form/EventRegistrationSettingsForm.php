<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\intercept_event\EventManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EventRegistrationSettingsForm.
 *
 * @ingroup intercept_event
 */
class EventRegistrationSettingsForm extends ConfigFormBase {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a EventRegistrationSettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, ModuleHandlerInterface $module_handler, RendererInterface $renderer) {
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('module_handler'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'intercept_event.settings',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'event_registration_settings';
  }

  /**
   * Defines the settings form for Event Registration entities.
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

    $emails = EventManager::emails();
    $options = $this->getRegistrationStatusOptions();

    $form['email_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Emails'),
      '#description' => $this->t('These emails are triggered when the status of a registration is changed.'),
    ];

    $form['email'] = [
      '#tree' => TRUE,
    ];

    foreach ($emails as $key => $title) {
      $config = $this->config('intercept_event.settings')->get('email.' . $key);
      $form['email'][$key] = [
        '#type' => 'details',
        '#title' => $title,
        '#group' => 'email_tabs',
        '#tree' => TRUE,
      ];

      $form['email'][$key]['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $config ? $config['subject'] : '',
        '#description' => $this->getTokenDescription() ?: '',
        '#maxlength' => 180,
      ];

      $form['email'][$key]['body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
        '#default_value' => $config ? $config['body'] : '',
        '#description' => $this->getTokenDescription() ?: '',
        '#rows' => 15,
      ];

      $form['email'][$key]['status_original'] = [
        '#title' => $this->t('Original status'),
        '#type' => 'checkboxes',
        '#multiple' => TRUE,
        '#options' => $options,
        '#description' => $this->t('The previous status of the registration. If no value is selected, the email will be inactive.'),
        '#default_value' => $config ? $config['status_original'] : '',
      ];

      $form['email'][$key]['status_new'] = [
        '#title' => $this->t('New status'),
        '#type' => 'checkboxes',
        '#options' => $options,
        '#description' => $this->t('The new status of the registration. If no value is selected, the email will be inactive.'),
        '#default_value' => $config ? $config['status_new'] : '',
      ];

      $form['email'][$key]['user'] = [
        '#title' => $this->t('User(s) to notify'),
        '#type' => 'checkboxes',
        '#options' => [
          'registration_user' => $this->t('User the registration is for'),
          'registration_author' => $this->t('User that created the registration'),
          'other' => $this->t('Custom email address'),
        ],
        '#description' => $this->t('Send email to specific users or custom addresses. Duplicates will be removed.'),
        '#default_value' => $config ? $config['user'] : '',
      ];

      $form['email'][$key]['user_email_other'] = [
        '#title' => $this->t('Custom email address'),
        '#type' => 'textfield',
        '#default_value' => $config ? $config['user_email_other'] : '',
        '#description' => $this->t('Multiple email addresses may be separated by commas. @token', ['@token' => $this->getTokenDescription()]),
        '#states' => [
          'visible' => [
            ':input[name="email[' . $key . '][user][other]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
    return $form;
  }

  /**
   * Gets the event registration status options.
   *
   * @return array
   *   An array of status option strings.
   */
  protected function getRegistrationStatusOptions() {
    $status_options = [];
    $registration_base_fields = $this->entityFieldManager
      ->getBaseFieldDefinitions('event_registration');
    if (isset($registration_base_fields['status'])) {
      $status_options = [
        'any' => $this->t('Any'),
        'empty' => $this->t('Empty (new registration)'),
      ] + $registration_base_fields['status']->getSetting('allowed_values');
    }
    return $status_options;
  }

  /**
   * Gets the description if tokens are supported.
   *
   * @return string|null
   *   The description, or null if token is not enabled.
   */
  protected function getTokenDescription() {
    $token_description = NULL;
    if ($this->moduleHandler->moduleExists('token')) {
      $token_tree = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['event_registration'],
      ];
      $rendered_token_tree = $this->renderer->render($token_tree);
      $token_description = $this->t('This field supports tokens. @browse_tokens_link', [
        '@browse_tokens_link' => $rendered_token_tree,
      ]);
    }
    return $token_description;
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
    $email_settings = $form_state->getValue('email');
    foreach ($email_settings as $key => $settings) {
      foreach ($settings as $setting_id => $setting) {
        if (is_array($setting)) {
          $email_settings[$key][$setting_id] = array_filter($setting);
        }
      }
    }
    $this->config('intercept_event.settings')
      ->set('email', $email_settings)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
