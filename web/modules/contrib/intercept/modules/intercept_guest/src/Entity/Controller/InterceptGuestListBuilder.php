<?php

namespace Drupal\intercept_guest\Entity\Controller;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for intercept_guest entity.
 *
 * @ingroup intercept_guest
 */
class InterceptGuestListBuilder extends EntityListBuilder {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('url_generator'),
      $container->get('entity_field.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * Constructs a new InterceptGuestListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   * @param Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, UrlGeneratorInterface $url_generator, EntityFieldManager $entity_field_manager, DateFormatter $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->urlGenerator = $url_generator;
    $this->entityFieldManager = $entity_field_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('Intercept Guests are fieldable entities. You can manage the fields on the <a href="@adminlink">Intercept Guest admin page</a>.', [
        '@adminlink' => $this->urlGenerator->generateFromRoute('intercept_guest.settings'),
      ]),
    ];
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the Intercept Guest list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $entityTypeId = $bundle = 'intercept_guest';
    $fields = $this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundle);
    $header['id'] = $this->t('Intercept Guest ID');
    foreach ($fields as $fieldName => $fieldDefinition) {
      if (!str_starts_with($fieldName, 'field')) {
        continue;
      }
      $header[$fieldName] = $this->t($fieldDefinition->label());
    }
    $header['changed'] = $this->t('Changed');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\intercept_guest\Entity\InterceptGuest $entity */
    $entityTypeId = $bundle = 'intercept_guest';
    $fields = $this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundle);
    $row['id'] = $entity->id();
    foreach ($fields as $fieldName => $fieldDefinition) {
      if (!str_starts_with($fieldName, 'field')) {
        continue;
      }
      $row[$fieldName] = $entity->$fieldName->value;
    }

    $dateOriginal = new DrupalDateTime('@' . $entity->getChangedTime(), 'UTC');
    $result = $this->dateFormatter->format($dateOriginal->getTimestamp(), 'custom', 'Y-m-d H:i:s');

    $row['changed'] = $result;
    return $row + parent::buildRow($entity);
  }

}
