<?php

namespace Drupal\Tests\intercept_equipment\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Create content types during config create method invocation.
 *
 * @group intercept_equipment
 */
class EquipmentConfigInstallTest extends BrowserTestBase {

  use ContentTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests creating a content type during config import.
   */
  public function testExistingContentType() {
    $this->createContentType(['type' => 'equipment']);
    $this->assertTrue($this->container->get('module_installer')->install(['intercept_equipment'], TRUE), 'Installed modules.');
  }

}
