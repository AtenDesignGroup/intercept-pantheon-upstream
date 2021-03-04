<?php

namespace Drupal\intercept_messages_sms;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\intercept_messages\StatusSubformTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides message template base for event registrations.
 */
abstract class EventRegistrationSmsMessageTemplateBase extends InterceptMessageSmsTemplateBase {

  use StatusSubformTrait;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType = 'event_registration';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, RendererInterface $renderer, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $module_handler, $renderer);
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Returns the status field options.
   *
   * @return array
   *   The status options.
   */
  public function getStatusOptions() {
    $status_options = [];
    $registration_base_fields = $this->entityFieldManager
      ->getBaseFieldDefinitions($this->entityType);
    if (isset($registration_base_fields['status'])) {
      $status_options = [
        'any' => $this->t('Any'),
        'empty' => $this->t('Empty (new registration)'),
      ] + $registration_base_fields['status']->getSetting('allowed_values');
    }
    return $status_options;
  }

}
