<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi_resources\Kernel;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\jsonapi\CacheableResourceResponse;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRelationship;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_resources\Resource\ResourceObjectRelationship;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Symfony\Component\HttpFoundation\Request;

// Workaround to support tests against Drupal 9, 10 and 11.
// @todo Remove once we end support for Drupal 10.1.x and below.
if (!trait_exists(EntityReferenceFieldCreationTrait::class)) {
  class_alias('\Drupal\Tests\field\Traits\EntityReferenceTestTrait', EntityReferenceFieldCreationTrait::class);
}

/**
 * Tests ResourceResponseFactory.
 *
 * @coversDefaultClass \Drupal\jsonapi_resources\Unstable\ResourceResponseFactory
 * @group jsonapi_resources
 */
final class ResourceResponseFactoryTest extends KernelTestBase {

  use EntityReferenceFieldCreationTrait;
  use UserCreationTrait;

  private const NODE_TYPE_ARTICLE_UUID = 'e5da5021-d7a0-4606-a21c-9586a8cf79a4';

  private const NODE_TYPE_PAGE_UUID = '8378b97d-36fd-4515-b2eb-22e90dfdc8dc';

  private const NODE_TYPE_EVENT_UUID = '12cce39f-fa9c-4c64-b7f6-a0ec511ba1e7';

  private const NODE_ARTICLE_1_UUID = '7bf77016-93d2-4098-84e4-c2634c4d8ecf';

  private const NODE_ARTICLE_2_UUID = '36405873-6b42-44ec-9f47-b771d83149b1';

  /**
   * The test user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'serialization',
    'jsonapi',
    'jsonapi_resources',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->account = $this->createUser();
    $this->container->get('current_user')->setAccount($this->account);

    NodeType::create([
      'uuid' => self::NODE_TYPE_ARTICLE_UUID,
      'name' => 'article',
      'type' => 'article',
    ])->save();
    NodeType::create([
      'uuid' => self::NODE_TYPE_PAGE_UUID,
      'name' => 'page',
      'type' => 'page',
    ])->save();
    NodeType::create([
      'uuid' => self::NODE_TYPE_EVENT_UUID,
      'name' => 'event',
      'type' => 'event',
    ])->save();
    $this->createEntityReferenceField(
      'node',
      'page',
      'field_related_articles',
      'Related articles',
      'node',
      'default',
      [
        'target_bundles' => [
          'reminder' => 'article',
        ],
      ],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

    $this->container->get('router.builder')->rebuildIfNeeded();
  }

  /**
   * @covers ::create
   * @dataProvider createData
   */
  public function testCreate(
    array $query,
    array $expected_includes,
    string $expected_error = '',
  ): void {
    if ($expected_error !== '') {
      $this->expectExceptionMessage($expected_error);
    }

    $article1 = Node::create([
      'uuid' => self::NODE_ARTICLE_1_UUID,
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $article1->save();
    $article2 = Node::create([
      'uuid' => self::NODE_ARTICLE_2_UUID,
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $article2->save();
    $page = Node::create([
      'type' => 'page',
      'title' => $this->randomString(),
      'status' => 1,
      'field_related_articles' => [$article1->id(), $article2->id()],
    ]);
    $page->save();
    $event = Node::create([
      'type' => 'event',
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $event->save();

    $resource_type_repository = $this->container->get('jsonapi.resource_type.repository');
    self::assertInstanceOf(ResourceTypeRepositoryInterface::class, $resource_type_repository);

    $entities = [$article1, $page, $article2, $event];
    $resource_types = [];
    $resource_objects = [];
    foreach ($entities as $entity) {
      $resource_type = $resource_type_repository->get($entity->getEntityTypeId(), $entity->bundle());
      $resource_types[$resource_type->getTypeName()] = $resource_type;
      $resource_objects[] = ResourceObject::createFromEntity($resource_type, $entity);
    }

    $request = Request::create('/foo?' . http_build_query($query));
    $request->attributes->set('resource_types', array_values($resource_types));

    $sut = $this->container->get('jsonapi_resources.resource_response_factory');
    $response = $sut->create(
      new ResourceObjectData($resource_objects),
      $request
    );
    self::assertInstanceOf(CacheableResourceResponse::class, $response);
    $document_top_level = $response->getResponseData();
    self::assertInstanceOf(JsonApiDocumentTopLevel::class, $document_top_level);
    /** @var \Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface[] $includes_data */
    $includes_data = $document_top_level->getIncludes()->toArray();
    $includes_data = array_map(
      static fn (ResourceIdentifierInterface $identifier) => [
        'id' => $identifier->getId(),
        'type' => $identifier->getTypeName(),
      ],
      $includes_data
    );
    self::assertEquals($expected_includes, $includes_data);
  }

  /**
   * Tests that ResourceObjectRelationship fields are resolved as includes.
   *
   * @covers ::create
   */
  public function testCreateWithResourceObjectRelationship(): void {
    $article1 = Node::create([
      'uuid' => self::NODE_ARTICLE_1_UUID,
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $article1->save();
    $article2 = Node::create([
      'uuid' => self::NODE_ARTICLE_2_UUID,
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $article2->save();

    $resource_type_repository = $this->container->get('jsonapi.resource_type.repository');
    self::assertInstanceOf(ResourceTypeRepositoryInterface::class, $resource_type_repository);

    $article_resource_type = $resource_type_repository->get('node', 'article');
    $article_ro1 = ResourceObject::createFromEntity($article_resource_type, $article1);
    $article_ro2 = ResourceObject::createFromEntity($article_resource_type, $article2);

    // Build a virtual resource type (no backing entity) with a custom
    // relationship field pointing to article nodes.
    $virtual_resource_type = new ResourceType(
      'virtual', 'virtual', NULL, FALSE, TRUE, TRUE, FALSE,
      ['related' => new ResourceTypeRelationship('related')]
    );
    $virtual_resource_type->setRelatableResourceTypes([
      'related' => [$article_resource_type],
    ]);

    // The relationship needs a context resource object for link generation.
    // Create a temporary context first, build the relationship, then create
    // the final resource object with the relationship as a field value.
    $cacheability = new CacheableMetadata();
    $context = new ResourceObject(
      $cacheability,
      $virtual_resource_type,
      'virtual-1',
      NULL,
      [],
      new LinkCollection([])
    );
    $relationship = ResourceObjectRelationship::createFromResourceObjects(
      $context,
      'related',
      [$article_ro1, $article_ro2]
    );
    $virtual_ro = new ResourceObject(
      $cacheability,
      $virtual_resource_type,
      'virtual-1',
      NULL,
      ['related' => $relationship],
      new LinkCollection([])
    );

    $request = Request::create('/foo?include=related');
    $request->attributes->set('resource_types', [$virtual_resource_type]);

    $sut = $this->container->get('jsonapi_resources.resource_response_factory');
    $response = $sut->create(
      new ResourceObjectData([$virtual_ro], 1),
      $request
    );

    self::assertInstanceOf(CacheableResourceResponse::class, $response);
    $document_top_level = $response->getResponseData();
    self::assertInstanceOf(JsonApiDocumentTopLevel::class, $document_top_level);

    /** @var \Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface[] $includes_data */
    $includes_data = $document_top_level->getIncludes()->toArray();
    $includes_data = array_map(
      static fn (ResourceIdentifierInterface $identifier) => [
        'id' => $identifier->getId(),
        'type' => $identifier->getTypeName(),
      ],
      $includes_data
    );
    self::assertEquals(
      [
        ['id' => self::NODE_ARTICLE_1_UUID, 'type' => 'node--article'],
        ['id' => self::NODE_ARTICLE_2_UUID, 'type' => 'node--article'],
      ],
      $includes_data
    );
  }

  /**
   * Include path valid for a route resource type absent from the data set.
   *
   * Regression test for #3172884: when the route declares a resource type that
   * has a relationship field, but the actual data contains no resource objects
   * of that type, the include must not raise a 400 — it should just produce
   * empty includes for that path.
   *
   * @covers ::create
   */
  public function testCreateIncludeValidForRouteTypeAbsentFromData(): void {
    $article1 = Node::create([
      'uuid' => self::NODE_ARTICLE_1_UUID,
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $article1->save();
    $event = Node::create([
      'type' => 'event',
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $event->save();

    $resource_type_repository = $this->container->get('jsonapi.resource_type.repository');
    self::assertInstanceOf(ResourceTypeRepositoryInterface::class, $resource_type_repository);

    // Route declares article, page, and event. Page owns
    // field_related_articles. Data contains only article + event — no page.
    $route_resource_types = [
      $resource_type_repository->get('node', 'article'),
      $resource_type_repository->get('node', 'page'),
      $resource_type_repository->get('node', 'event'),
    ];
    $resource_objects = [
      ResourceObject::createFromEntity($route_resource_types[0], $article1),
      ResourceObject::createFromEntity($route_resource_types[2], $event),
    ];

    $request = Request::create('/foo?include=field_related_articles');
    $request->attributes->set('resource_types', $route_resource_types);

    $sut = $this->container->get('jsonapi_resources.resource_response_factory');
    $response = $sut->create(
      new ResourceObjectData($resource_objects),
      $request
    );

    self::assertInstanceOf(CacheableResourceResponse::class, $response);
    $document_top_level = $response->getResponseData();
    self::assertInstanceOf(JsonApiDocumentTopLevel::class, $document_top_level);
    self::assertSame([], $document_top_level->getIncludes()->toArray());
  }

  /**
   * All include paths are resolved in one resolver call per data group.
   *
   * Resolving each include path with its own resolver call re-walks the data
   * for every path, re-creating every intermediate resource object each time.
   * On deep include fan-outs this multiplies memory use by the number of
   * paths and can exhaust PHP memory. The resolver already accepts a
   * comma-separated list and merges it into a single include tree, so each
   * group of resource objects must be resolved exactly once.
   *
   * @covers ::create
   */
  public function testIncludePathsResolvedOncePerGroup(): void {
    $article1 = Node::create([
      'uuid' => self::NODE_ARTICLE_1_UUID,
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $article1->save();
    $article2 = Node::create([
      'uuid' => self::NODE_ARTICLE_2_UUID,
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $article2->save();
    $page = Node::create([
      'type' => 'page',
      'title' => $this->randomString(),
      'status' => 1,
      'field_related_articles' => [$article1->id(), $article2->id()],
    ]);
    $page->save();
    $event = Node::create([
      'type' => 'event',
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $event->save();

    $counting_resolver = new CountingIncludeResolver($this->container->get('jsonapi.include_resolver'));
    // Decoration makes the concrete decorator id the target of compiled
    // references, so override both ids and drop the already-instantiated
    // factory so it is rebuilt with the counting resolver.
    $this->container->set('jsonapi.include_resolver', $counting_resolver);
    $this->container->set('jsonapi_resources.include_resolver', $counting_resolver);
    $this->container->set('jsonapi_resources.resource_response_factory', NULL);

    $resource_type_repository = $this->container->get('jsonapi.resource_type.repository');
    self::assertInstanceOf(ResourceTypeRepositoryInterface::class, $resource_type_repository);

    $entities = [$article1, $page, $article2, $event];
    $resource_types = [];
    $resource_objects = [];
    foreach ($entities as $entity) {
      $resource_type = $resource_type_repository->get($entity->getEntityTypeId(), $entity->bundle());
      $resource_types[$resource_type->getTypeName()] = $resource_type;
      $resource_objects[] = ResourceObject::createFromEntity($resource_type, $entity);
    }

    // "node_type" applies to all three groups, "field_related_articles" only
    // to the page group.
    $request = Request::create('/foo?include=field_related_articles,node_type');
    $request->attributes->set('resource_types', array_values($resource_types));

    $sut = $this->container->get('jsonapi_resources.resource_response_factory');
    $injected = (new \ReflectionProperty($sut, 'includeResolver'))->getValue($sut);
    self::assertInstanceOf(CountingIncludeResolver::class, $injected);
    $response = $sut->create(
      new ResourceObjectData($resource_objects),
      $request
    );
    self::assertInstanceOf(CacheableResourceResponse::class, $response);

    // One call per data group (article, page, event) — not one per group and
    // include path combination.
    self::assertSame(3, $counting_resolver->resolveCalls);

    $document_top_level = $response->getResponseData();
    self::assertInstanceOf(JsonApiDocumentTopLevel::class, $document_top_level);
    /** @var \Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface[] $includes_data */
    $includes_data = $document_top_level->getIncludes()->toArray();
    $includes_data = array_map(
      static fn (ResourceIdentifierInterface $identifier) => [
        'id' => $identifier->getId(),
        'type' => $identifier->getTypeName(),
      ],
      $includes_data
    );
    self::assertEqualsCanonicalizing(
      [
        ['id' => self::NODE_TYPE_ARTICLE_UUID, 'type' => 'node_type--node_type'],
        ['id' => self::NODE_TYPE_PAGE_UUID, 'type' => 'node_type--node_type'],
        ['id' => self::NODE_TYPE_EVENT_UUID, 'type' => 'node_type--node_type'],
        ['id' => self::NODE_ARTICLE_1_UUID, 'type' => 'node--article'],
        ['id' => self::NODE_ARTICLE_2_UUID, 'type' => 'node--article'],
      ],
      $includes_data
    );
  }

  /**
   * Test data for testCreate.
   *
   * @return array[]
   *   The test data.
   */
  public static function createData(): array {
    return [
      'mixed resource objects with same include' => [
        ['include' => 'node_type'],
        [
          [
            'id' => self::NODE_TYPE_ARTICLE_UUID,
            'type' => 'node_type--node_type',
          ],
          [
            'id' => self::NODE_TYPE_PAGE_UUID,
            'type' => 'node_type--node_type',
          ],
          [
            'id' => self::NODE_TYPE_EVENT_UUID,
            'type' => 'node_type--node_type',
          ],
        ],
      ],
      'mixed resource objects with mismatched includes' => [
        ['include' => 'field_related_articles'],
        [
          [
            'id' => self::NODE_ARTICLE_1_UUID,
            'type' => 'node--article',
          ],
          [
            'id' => self::NODE_ARTICLE_2_UUID,
            'type' => 'node--article',
          ],
        ],
      ],
      'missing relationship in includes' => [
        ['include' => 'field_foobar'],
        [],
        'field_foobar` are not valid relationship names. Possible values: node_type, revision_uid, uid, field_related_articles',
      ],
    ];
  }

}
