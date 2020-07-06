<?php

namespace Drupal\Tests\office_hours\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Class OfficeHoursFieldTest.
 *
 * @package Drupal\Tests\office_hours\Kernel
 *
 * @group office_hours
 */
class OfficeHoursFieldTest extends FieldKernelTestBase {

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['office_hours'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_office_hours',
      'type' => 'office_hours',
      'entity_type' => 'entity_test',
      'settings' => ['element_type' => 'office_hours_datelist'],
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'settings' => [],
      'default_value' => [
        [
          'day' => 0,
          'starthours' => 700,
          'endhours' => 1800,
          'comment' => 'Test comment',
        ],
        [
          'day' => 1,
          'starthours' => 700,
          'endhours' => 1800,
          'comment' => 'Test comment',
        ],
      ],
    ]);
    $this->field->save();

    /** @var @ $entity_display */
    $entity_display = EntityViewDisplay::create([
      'targetEntityType' => $this->field->getTargetEntityTypeId(),
      'bundle' => $this->field->getTargetBundle(),
      'mode' => 'default',
    ]);
    // Save the office hours field to check if the config schema is valid.
    // @todo D9 test
    // Table formatter.
    $entity_display->setComponent('field_office_hours', ['type' => 'office_hours_table']);
    $entity_display->save();
    // Default formatter.
    $entity_display->setComponent('field_office_hours', ['type' => 'office_hours']);
    $entity_display->save();
  }

  /**
   * Tests the Office Hours field can be added to an entity type.
   */
  public function testOfficeHoursField() {
    $this->fieldStorage->setSetting('element_type', 'office_hours_datelist');
    $this->fieldStorage->save();

    // Verify entity creation.
    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = EntityTest::create();
    $office_hours = [
      'day' => 1,
      'starthours' => 630,
      'endhours' => 2200,
    ];
    $entity->set('field_office_hours', $office_hours);
    $entity->setName($this->randomMachineName());
    $this->entityValidateAndSave($entity);

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->get('field_office_hours'));
    $this->assertInstanceOf(FieldItemInterface::class, $entity->get('field_office_hours')->first());
  }

}
