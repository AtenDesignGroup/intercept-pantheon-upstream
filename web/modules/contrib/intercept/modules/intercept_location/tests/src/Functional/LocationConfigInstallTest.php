<?php

namespace Drupal\Tests\intercept_location\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Create content types during config create method invocation.
 *
 * @group intercept_location
 */
class LocationConfigInstallTest extends BrowserTestBase {

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
    $this->createContentType(['type' => 'location']);
    $this->createContentType(['type' => 'room']);
    $this->assertTrue($this->container->get('module_installer')->install(['intercept_location'], TRUE), 'Installed modules.');
  }

}
