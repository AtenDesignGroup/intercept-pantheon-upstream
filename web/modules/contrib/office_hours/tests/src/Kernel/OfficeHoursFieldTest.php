<?php

namespace Drupal\Tests\office_hours\Kernel;

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
    ]);
    $this->field->save();
  }

  /**
   * Tests the Office Hours field can be added to an entity type.
   */
  public function testOfficeHoursField() {
    $this->fieldStorage->setSetting('element_type', 'office_hours_datelist');
    $this->fieldStorage->save();

    // Verify entity creation.
    $entity = EntityTest::create();
    $office_hours = [
      'day' => 1,
      'starthours' => 630,
      'endhours' => 2200,
    ];
    /** @var $entity EntityTest*/
    $entity->set('field_office_hours', $office_hours);
    $entity->setName($this->randomMachineName());
    $this->entityValidateAndSave($entity);

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertTrue($entity->field_office_hours instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_office_hours[0] instanceof FieldItemInterface, 'Field item implements interface.');
  }

}
