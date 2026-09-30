<?php

namespace Drupal\Tests\focal_point\Kernel;

use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\SchemaCheckTestTrait;

/**
 * Tests that focal_point_crop_by_width has a valid config schema.
 *
 * @group focal_point
 *
 * @see https://www.drupal.org/project/focal_point/issues/3530779
 */
class FocalPointCropByWidthSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'image',
    'file',
    'crop',
    'focal_point',
  ];

  /**
   * Tests that the effect's configuration validates against its schema.
   */
  public function testCropByWidthConfigSchema() {
    $style = ImageStyle::create([
      'name' => 'test_focal_point_crop_by_width',
      'label' => 'Test Focal Point Crop by Width',
    ]);
    $style->addImageEffect([
      'id' => 'focal_point_crop_by_width',
      'data' => [
        'width' => 300,
        'height' => 200,
        'crop_type' => 'focal_point',
      ],
    ]);
    $style->save();

    $this->assertConfigSchemaByName($style->getConfigDependencyName());
  }

}
