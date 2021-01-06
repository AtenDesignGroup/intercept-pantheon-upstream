<?php

namespace Drupal\intercept_core\Plugin\Field\FieldFormatter;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\intercept_core\Form\ReservationStatusChangeForm;
use Drupal\options\Plugin\Field\FieldFormatter\OptionsDefaultFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'reservation_status_change' formatter.
 *
 * @FieldFormatter(
 *   id = "reservation_status_change",
 *   label = @Translation("Reservation status form"),
 *   field_types = {
 *     "list_string"
 *   }
 * )
 */
class ReservationStatusChangeFormatter extends OptionsDefaultFormatter implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new StatusChangeFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type Manager.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, ClassResolverInterface $class_resolver, FormBuilderInterface $form_builder, ModuleHandlerInterface $module_handler) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->classResolver = $class_resolver;
    $this->formBuilder = $form_builder;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('class_resolver'),
      $container->get('form_builder'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    $elements = [];
    if (!$entity->access('update')) {
      return $elements;
    }

    $property_names = $this->fieldDefinition->getFieldStorageDefinition()->getPropertyNames();

    // Limit the settable options for the current user account.
    $options = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getOptionsProvider($property_names[0], $entity)
      ->getSettableOptions(\Drupal::currentUser());

    /** @var \Drupal\intercept_core\Form\ReservationStatusChangeForm $form_object */
    $form_object = $this->classResolver->getInstanceFromDefinition(ReservationStatusChangeForm::class);
    $form_object->setEntity($items->getEntity());
    $form_object->setOptions($options);
    $form_state = new FormState();

    $elements[] = $this->formBuilder->buildForm($form_object, $form_state);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $applicable_types = [
      'room_reservation',
      'equipment_reservation',
    ];
    return $field_definition->getName() === 'field_status' && in_array($field_definition->getTargetEntityTypeId(), $applicable_types);
  }

}
