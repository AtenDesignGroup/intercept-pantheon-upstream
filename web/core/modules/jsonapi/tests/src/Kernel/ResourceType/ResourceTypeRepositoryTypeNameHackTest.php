<?php

namespace Drupal\Tests\jsonapi\Kernel\ResourceType;

use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;
use PHPUnit\Framework\Error\Warning;

/**
 * @coversDefaultClass \Drupal\jsonapi\ResourceType\ResourceTypeRepository
 * @group jsonapi
 *
 * @internal
 */
class ResourceTypeRepositoryTypeNameHackTest extends JsonapiKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'user',
    'field',
    'system',
    'serialization',
    'jsonapi_test_resource_typename_hack',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container
      ->get('entity_type.manager')
      ->getStorage('node_type')
      ->create(['type' => 'page'])
      ->save();
  }

  /**
   * Ensures resource repository forms the listing using internal names.
   *
   * JSON:API Extras is used widely and it's hard to imagine how big its
   * coverage at existing projects. The project allows renaming resource
   * types (e.g. "user---user" to "user") and this negatively affects the
   * operability of JSON:API itself.
   *
   * @covers ::get
   * @covers ::all
   * @covers ::getByTypeName
   *
   * @link https://www.drupal.org/project/drupal/issues/2996114
   */
  public function test() {
    $repository = $this->container->get('jsonapi.resource_type.repository');

    static::assertInstanceOf(ResourceType::class, $repository->get('user', 'user'));
    static::assertNull($repository->getByTypeName('user--user'));
    static::assertInstanceOf(ResourceType::class, $repository->getByTypeName('user==user'));

    static::assertInstanceOf(ResourceType::class, $repository->get('node', 'page'));
    static::assertNull($repository->getByTypeName('node--page'));
    static::assertInstanceOf(ResourceType::class, $repository->getByTypeName('node==page'));

    foreach ($repository->all() as $id => $resource_type) {
      static::assertSame(
        $resource_type->getTypeName(),
        $id,
        'The key is always equal to the type name.'
      );

      static::assertNotSame(
        sprintf('%s--%s', $resource_type->getEntityTypeId(), $resource_type->getBundle()),
        $id,
        'The type name can be renamed so it differs from the internal.'
      );
    }
  }

  /**
   * Ensures resource repository avoids using missing references from fields.
   *
   * @covers ::all
   * @covers ::calculateRelatableResourceTypes
   * @covers ::getRelatableResourceTypesFromFieldDefinition
   *
   * @link https://www.drupal.org/project/drupal/issues/2996114
   */
  public function testGetRelatableResourceTypesFromFieldDefinition() {
    $field_config_storage = $this->container->get('entity_type.manager')->getStorage('field_config');
    $repository = $this->container->get('jsonapi.resource_type.repository');

    static::assertCount(0, $repository->get('node', 'page')->getRelatableResourceTypesByField('field_relationship'));
    $this->createEntityReferenceField('node', 'page', 'field_relationship', 'Related entity', 'node', 'default', [
      'target_bundles' => ['missing_bundle'],
    ]);
    $fields = $field_config_storage->loadByProperties(['field_name' => 'field_relationship']);
    static::assertSame(['missing_bundle'], $fields['node.page.field_relationship']->getItemDefinition()->getSetting('handler_settings')['target_bundles']);

    try {
      $repository->get('node', 'page')->getRelatableResourceTypesByField('field_relationship');
      static::fail('The above code must produce a warning since the "missing_bundle" does not exist.');
    }
    catch (Warning $e) {
      static::assertSame(
        'The "field_relationship" at "node:page" references the "node:missing_bundle" entity type that does not exist. Please take action.',
        $e->getMessage()
      );
    }
  }

}
