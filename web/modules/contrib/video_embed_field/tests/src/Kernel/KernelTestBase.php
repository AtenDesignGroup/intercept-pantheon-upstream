<?php

namespace Drupal\Tests\video_embed_field\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\KernelTests\KernelTestBase as CoreKernelTestBase;
use Drupal\colorbox\ColorboxAttachment;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * A kernel test base.
 */
abstract class KernelTestBase extends CoreKernelTestBase {

  /**
   * The test field name.
   *
   * @var string
   */
  protected $fieldName = 'field_test';

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId = 'entity_test';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'colorbox',
    'user',
    'system',
    'field',
    'text',
    'entity_test',
    'field_test',
    'video_embed_field',
    'image',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema($this->entityTypeId);

    // Install image styles.
    $this->installConfig(['image']);

    EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ])->save();
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityTypeId,
      'type' => 'video_embed_field',
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityTypeId,
      'field_name' => $this->fieldName,
      'bundle' => $this->entityTypeId,
    ])->save();

    // Use a HTTP mock which won't attempt to download anything.
    $this->container->set('http_client', new MockHttpClient());

    // Shim in a service required from the colorbox module.
    $colorbox_mock = $this->createMock(ColorboxAttachment::class);
    $this->container->set('colorbox.attachment', $colorbox_mock);
  }

}
