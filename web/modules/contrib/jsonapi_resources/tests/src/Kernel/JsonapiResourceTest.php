<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi_resources\Kernel;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\MetadataBag;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiSpec;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;
use Drupal\Tests\jsonapi_resources\Kernel\Traits\RequestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests JSON:API Resource processors via the HTTP kernel.
 *
 * @group jsonapi_resources
 */
final class JsonapiResourceTest extends JsonapiKernelTestBase {

  use CommentTestTrait;
  use RequestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'serialization',
    'node',
    'comment',
    'jsonapi',
    'jsonapi_resources',
    'jsonapi_resources_test',
  ];

  /**
   * The account requests are made as.
   */
  protected User $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['system', 'user', 'filter', 'node', 'comment', 'jsonapi']);

    // The ReadOnlyModeMethodFilter reads jsonapi.settings:read_only in its
    // constructor and caches it, so the setting must be FALSE before any
    // request is routed. Disable read-only mode for the whole test class —
    // GET-only tests are unaffected.
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Ensure neither role grants any permission by default — tests grant
    // exactly what they need.
    foreach ([RoleInterface::ANONYMOUS_ID, RoleInterface::AUTHENTICATED_ID] as $role_id) {
      $role = Role::load($role_id);
      foreach ($role->getPermissions() as $permission) {
        $role->revokePermission($permission);
      }
      $role->save();
    }

    NodeType::create(['name' => 'article', 'type' => 'article'])->save();
    NodeType::create(['name' => 'reminder', 'type' => 'reminder'])->save();

    $this->createEntityReferenceField(
      'user',
      'user',
      'field_reminders',
      'Reminders',
      'node',
      'default',
      ['target_bundles' => ['reminder' => 'reminder']],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    );

    $this->addDefaultCommentField('node', 'article', 'comment');

    // Reserve UID 1 — kernel tests boot with an empty user table.
    User::create(['uid' => 1, 'name' => 'admin'])->save();

    $this->account = User::create([
      'name' => $this->randomMachineName(),
      'mail' => 'test@example.com',
      'status' => 1,
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ]);
    $this->account->save();
    $this->container->get('current_user')->setAccount($this->account);

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Tests the custom Add Reminder resource.
   */
  public function testAddReminderResource(): void {
    $this->grantPermissionsToTestedRole(['access content', 'create reminder content']);

    $body = json_encode([
      'data' => [
        'type' => 'node--reminder',
        'attributes' => [
          'title' => "Don't panic.",
        ],
      ],
    ]);
    $request = Request::create(
      sprintf('/jsonapi/user/%s/reminders', $this->account->id()),
      'POST',
      [],
      [],
      [],
      [],
      $body,
    );
    $request->headers->set('Accept', 'application/vnd.api+json');
    $request->headers->set('Content-Type', 'application/vnd.api+json');

    $response = $this->request($request);
    $this->assertSame(201, $response->getStatusCode(), (string) $response->getContent());
    $this->assertTrue($response->headers->has('Location'));

    $created_url = $response->headers->get('Location');
    $get_request = Request::create($created_url, 'GET');
    $get_request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($get_request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $exists = FALSE;
    $owner_id = NestedArray::getValue($document, explode('/', 'data/relationships/uid/data/id'), $exists);
    $this->assertTrue($exists);
    $this->assertSame(User::load($this->account->id())->uuid(), $owner_id);

    // The same route accepts the user's UUID as the {user} placeholder
    // thanks to AutoEntityConverter.
    $uuid_request = Request::create(
      sprintf('/jsonapi/user/%s/reminders', $this->account->uuid()),
      'POST',
      [],
      [],
      [],
      [],
      $body,
    );
    $uuid_request->headers->set('Accept', 'application/vnd.api+json');
    $uuid_request->headers->set('Content-Type', 'application/vnd.api+json');
    $uuid_response = $this->request($uuid_request);
    $this->assertSame(201, $uuid_response->getStatusCode(), (string) $uuid_response->getContent());
  }

  /**
   * Tests the custom Featured Nodes resource.
   */
  public function testFeaturedNodesResource(): void {
    $promoted_nodes = [];
    for ($i = 0; $i < 8; $i++) {
      $promoted = ($i % 2 === 0);
      $node = Node::create([
        'type' => 'article',
        'title' => $this->randomString(),
        'status' => 1,
        'promote' => $promoted ? 1 : 0,
      ]);
      $node->save();
      if ($promoted) {
        $promoted_nodes[$node->uuid()] = $node;
      }
    }
    $this->grantPermissionsToTestedRole(['access content', 'access user profiles']);

    $request = Request::create('/jsonapi/featured-content', 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertCount(4, $document['data']);
    $this->assertSame(
      array_keys($promoted_nodes),
      array_map(static fn (array $data) => $data['id'], $document['data']),
    );
    // FeaturedNodes opts in by calling buildCountMeta(), so the total is
    // exposed even when the response is not paginated.
    $this->assertSame(4, $document['meta']['count'] ?? NULL);
  }

  /**
   * Tests that meta.count reflects total matches, independent of page size.
   */
  public function testFeaturedNodesResourceMetaCountWithPaging(): void {
    for ($i = 0; $i < 10; $i++) {
      Node::create([
        'type' => 'article',
        'title' => $this->randomString(),
        'status' => 1,
        'promote' => 1,
      ])->save();
    }
    // A non-promoted node that must not be counted.
    Node::create([
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
      'promote' => 0,
    ])->save();

    $this->grantPermissionsToTestedRole(['access content', 'access user profiles']);

    $request = Request::create('/jsonapi/featured-content', 'GET', [
      'page' => ['offset' => 0, 'limit' => 3],
    ]);
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertCount(3, $document['data']);
    // meta.count is the total, not the page size.
    $this->assertSame(10, $document['meta']['count'] ?? NULL);
  }

  /**
   * Tests a resource backed by a configuration object rather than an entity.
   */
  public function testSiteInfoResource(): void {
    $this->config('system.site')
      ->set('name', 'JSON:API Resources Demo')
      ->set('slogan', 'Resources, not just entities')
      ->set('mail', 'admin@example.com')
      ->save();

    $request = Request::create('/jsonapi/site-info', 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertSame('site_info--site_info', $document['data']['type']);
    $this->assertSame('system.site', $document['data']['id']);
    $this->assertSame([
      'name' => 'JSON:API Resources Demo',
      'slogan' => 'Resources, not just entities',
      'mail' => 'admin@example.com',
    ], $document['data']['attributes']);

    // The config object's cache tag rides along, so editing site information
    // invalidates the response.
    $this->assertInstanceOf(CacheableResponseInterface::class, $response);
    $this->assertContains('config:system.site', $response->getCacheableMetadata()->getCacheTags());
  }

  /**
   * Tests filter support on EntityQueryResourceBase.
   */
  public function testFilterOnFeaturedNodes(): void {
    $titles = ['Alpha', 'Bravo', 'Charlie', 'Delta'];
    $nodes = [];
    foreach ($titles as $title) {
      $node = Node::create([
        'type' => 'article',
        'title' => $title,
        'status' => 1,
        'promote' => 1,
      ]);
      $node->save();
      $nodes[$title] = $node;
    }
    // A non-promoted node that the resource's hard-coded condition excludes,
    // and which therefore must not appear even when its title is filtered for.
    $hidden = Node::create([
      'type' => 'article',
      'title' => 'Hidden',
      'status' => 1,
      'promote' => 0,
    ]);
    $hidden->save();

    $this->grantPermissionsToTestedRole(['access content', 'access user profiles']);

    // Filtering narrows the response to the one matching node.
    $request = Request::create('/jsonapi/featured-content', 'GET', [
      'filter' => ['title' => ['value' => 'Bravo']],
    ]);
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertCount(1, $document['data']);
    $this->assertSame($nodes['Bravo']->uuid(), $document['data'][0]['id']);

    // The filter cannot override the resource's hard-coded promote=1 condition,
    // so the non-promoted node is never returned.
    $request = Request::create('/jsonapi/featured-content', 'GET', [
      'filter' => ['title' => ['value' => 'Hidden']],
    ]);
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertCount(0, $document['data']);
  }

  /**
   * Tests sort support on EntityQueryResourceBase.
   */
  public function testSortOnFeaturedNodes(): void {
    // Create promoted nodes out of title order, so a sort actually reorders
    // them rather than coincidentally matching creation order.
    foreach (['Charlie', 'Alpha', 'Delta', 'Bravo'] as $title) {
      Node::create([
        'type' => 'article',
        'title' => $title,
        'status' => 1,
        'promote' => 1,
      ])->save();
    }

    $this->grantPermissionsToTestedRole(['access content', 'access user profiles']);

    // Ascending sort by title.
    $request = Request::create('/jsonapi/featured-content', 'GET', ['sort' => 'title']);
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertSame(
      ['Alpha', 'Bravo', 'Charlie', 'Delta'],
      array_map(static fn (array $data) => $data['attributes']['title'], $document['data']),
    );

    // Descending sort by title.
    $request = Request::create('/jsonapi/featured-content', 'GET', ['sort' => '-title']);
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertSame(
      ['Delta', 'Charlie', 'Bravo', 'Alpha'],
      array_map(static fn (array $data) => $data['attributes']['title'], $document['data']),
    );
  }

  /**
   * Tests sorting by a path that resolves through a relationship.
   */
  public function testSortByRelationshipPath(): void {
    // Authors whose names sort opposite to the node titles, so a sort on the
    // author relationship cannot accidentally match title or creation order.
    $authors = ['zara' => 'Article one', 'molly' => 'Article two', 'amir' => 'Article three'];
    foreach ($authors as $name => $title) {
      $author = User::create([
        'name' => $name,
        'status' => 1,
        'roles' => [RoleInterface::AUTHENTICATED_ID],
      ]);
      $author->save();
      Node::create([
        'type' => 'article',
        'title' => $title,
        'status' => 1,
        'promote' => 1,
        'uid' => $author->id(),
      ])->save();
    }

    $this->grantPermissionsToTestedRole(['access content', 'access user profiles']);

    // Sorting by `uid.name` traverses the author relationship; this only works
    // because the sort path is run through the JSON:API field resolver.
    $request = Request::create('/jsonapi/featured-content', 'GET', ['sort' => 'uid.name']);
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertSame(
      ['Article three', 'Article two', 'Article one'],
      array_map(static fn (array $data) => $data['attributes']['title'], $document['data']),
    );
  }

  /**
   * Tests the Author Content resource.
   */
  public function testAuthorContentResource(): void {
    $author_user = $this->account;
    $node1 = Node::create([
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
      'uid' => $author_user->id(),
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
      'uid' => $author_user->id(),
    ]);
    $node2->save();

    $this->grantPermissionsToTestedRole(['access content']);

    $request = Request::create(sprintf('/jsonapi/user/%s/content', $author_user->id()), 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertCount(2, $document['data']);
    $this->assertArrayHasKey('included', $document);
    $this->assertNotEmpty($document['included']);

    $request = Request::create(
      sprintf('/jsonapi/user/%s/content?page[limit]=1', $author_user->id()),
      'GET',
    );
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertCount(1, $document['data']);
    $this->assertArrayHasKey('included', $document);
    $this->assertNotEmpty($document['included']);
    $this->assertArrayHasKey('next', $document['links']);
    $this->assertArrayHasKey('last', $document['links']);
    $this->assertSame($node1->uuid(), $document['data'][0]['id']);

    $request = Request::create($document['links']['next']['href'], 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertCount(1, $document['data']);
    $this->assertSame($node2->uuid(), $document['data'][0]['id']);

    // The same route accepts the user's UUID as the {user} placeholder
    // thanks to AutoEntityConverter.
    $uuid_request = Request::create(sprintf('/jsonapi/user/%s/content', $author_user->uuid()), 'GET');
    $uuid_request->headers->set('Accept', 'application/vnd.api+json');
    $uuid_response = $this->request($uuid_request);
    $this->assertSame(200, $uuid_response->getStatusCode(), (string) $uuid_response->getContent());
    $this->assertCount(2, self::decodeResponse($uuid_response)['data']);
  }

  /**
   * Tests $check_access = FALSE on loadResourceObjectDataFromEntityQuery().
   *
   * The legacy throw is gone: passing FALSE now executes the unchecked code
   * path in createCollectionDataFromEntities() and returns serialized
   * resource objects. The resource is responsible for restricting its query
   * to the rows the current user is allowed to see.
   */
  public function testAuthorContentResourceWithoutAccessCheck(): void {
    $author_user = $this->account;
    $node = Node::create([
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
      'uid' => $author_user->id(),
    ]);
    $node->save();

    $this->grantPermissionsToTestedRole(['access content']);

    $request = Request::create(sprintf('/jsonapi/user/%s/content-unchecked', $author_user->id()), 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertCount(1, $document['data']);
    $this->assertSame($node->uuid(), $document['data'][0]['id']);

    // The same route accepts the user's UUID as the {user} placeholder
    // thanks to AutoEntityConverter.
    $uuid_request = Request::create(sprintf('/jsonapi/user/%s/content-unchecked', $author_user->uuid()), 'GET');
    $uuid_request->headers->set('Accept', 'application/vnd.api+json');
    $uuid_response = $this->request($uuid_request);
    $this->assertSame(200, $uuid_response->getStatusCode(), (string) $uuid_response->getContent());
    $this->assertCount(1, self::decodeResponse($uuid_response)['data']);
  }

  /**
   * Tests that AutoEntityConverter is not auto-assigned to foreign routes.
   *
   * The converter is only wired onto JSON:API resource routes via an explicit
   * `converter:` option. It must never become the default converter for entity
   * parameters on routes belonging to other modules, which would happen if its
   * applies() returned TRUE at its registered priority.
   */
  public function testAutoEntityConverterDoesNotHijackForeignRoutes(): void {
    $route = $this->container->get('router.route_provider')
      ->getRouteByName('entity.node.canonical');
    $parameters = $route->getOption('parameters');
    $this->assertArrayHasKey('node', $parameters);
    // Core's entity converter still owns the parameter; ours did not steal it.
    $this->assertSame('paramconverter.entity', $parameters['node']['converter'] ?? NULL);
  }

  /**
   * Tests that an unknown UUID on a resource route returns 404.
   */
  public function testAutoEntityConverterUnknownUuidIsNotFound(): void {
    $this->grantPermissionsToTestedRole(['access content']);

    $request = Request::create('/jsonapi/user/00112233-4455-6677-8899-aabbccddeeff/content', 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request, TRUE);
    $this->assertSame(404, $response->getStatusCode(), (string) $response->getContent());
  }

  /**
   * Tests that the `bundle` parameter flag applies to both ID and UUID values.
   */
  public function testAutoEntityConverterHonorsBundleForUuid(): void {
    $article = Node::create([
      'type' => 'article',
      'title' => $this->randomString(),
      'uid' => $this->account->id(),
    ]);
    $article->save();
    $reminder = Node::create([
      'type' => 'reminder',
      'title' => $this->randomString(),
      'uid' => $this->account->id(),
    ]);
    $reminder->save();

    $converter = $this->container->get('paramconverter.jsonapi_resources.entity_auto');
    $definition = ['type' => 'entity:node', 'bundle' => ['article']];

    $this->assertSame($article->id(), $converter->convert($article->id(), $definition, 'node', [])->id());
    $this->assertSame($article->id(), $converter->convert($article->uuid(), $definition, 'node', [])->id());
    $this->assertNull($converter->convert($reminder->id(), $definition, 'node', []));
    $this->assertNull($converter->convert($reminder->uuid(), $definition, 'node', []));
  }

  /**
   * Tests the Current User Info resource without access to the roles field.
   *
   * The roles relationship is backed by the user entity's `roles` field. A
   * user who lacks `administer users` cannot view that field, so the whole
   * relationship is filtered out of the response.
   */
  public function testCurrentUserInfoResourceWithNoRoleAccess(): void {
    $role = Role::create(['id' => 'empty', 'label' => 'Empty']);
    $role->save();
    $this->account->addRole($role->id());
    $this->account->save();

    $request = Request::create('/jsonapi/me', 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $data = $document['data'];
    $this->assertSame($this->account->uuid(), $data['id']);
    $this->assertArrayNotHasKey('relationships', $data);

    $attributes = $data['attributes'];
    $this->assertSame($this->account->getDisplayName(), $attributes['displayName']);
    $this->assertNotEmpty($attributes['token']);
    $this->assertArrayNotHasKey('roles', $attributes);
  }

  /**
   * Tests the Current User Info resource with access to the roles field.
   *
   * A user with `administer users` can view the `roles` field, so it is
   * serialized as a JSON:API relationship with related and self links that
   * point at the two custom routes.
   */
  public function testCurrentUserInfoResourceWithRoleAccess(): void {
    $role = Role::create(['id' => 'roles_admin', 'label' => 'Roles admin']);
    $role->grantPermission('administer users');
    $role->grantPermission('administer permissions');
    $role->save();
    $this->account->addRole($role->id());
    $this->account->save();

    $request = Request::create('/jsonapi/me', 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $data = $document['data'];
    $this->assertSame($this->account->uuid(), $data['id']);

    $attributes = $data['attributes'];
    $this->assertSame($this->account->getDisplayName(), $attributes['displayName']);
    $this->assertNotEmpty($attributes['token']);
    $this->assertArrayNotHasKey('roles', $attributes);

    $account_uuid = $this->account->uuid();
    $this->assertEquals(
      [
        'roles' => [
          'data' => [
            [
              'type' => 'user_role--user_role',
              'id' => $role->uuid(),
              'meta' => ['drupal_internal__target_id' => $role->id()],
            ],
          ],
          'links' => [
            'related' => [
              'href' => Url::fromUri('internal:/jsonapi/me/roles?entity=' . $account_uuid)
                ->setAbsolute()
                ->toString(),
            ],
            'self' => [
              'href' => Url::fromUri('internal:/jsonapi/me/relationships/roles?entity=' . $account_uuid)
                ->setAbsolute()
                ->toString(),
            ],
          ],
        ],
      ],
      $data['relationships'],
    );
  }

  /**
   * Tests `?include=roles` when the user cannot view the roles field.
   *
   * The include cannot be resolved, so the document carries no `included`
   * member and instead reports the inaccessible relationship under
   * `meta.omitted`.
   */
  public function testCurrentUserInfoResourceIncludeRolesWithNoAccess(): void {
    $role = Role::create(['id' => 'empty', 'label' => 'Empty']);
    $role->save();
    $this->account->addRole($role->id());
    $this->account->save();

    $request = Request::create('/jsonapi/me', 'GET', ['include' => 'roles']);
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertArrayNotHasKey('included', $document);
    $this->assertArrayNotHasKey('relationships', $document['data']);

    $this->assertArrayHasKey('meta', $document);
    $this->assertArrayHasKey('omitted', $document['meta']);
    $omitted = $document['meta']['omitted'];
    $this->assertSame(
      'Some resources have been omitted because of insufficient authorization.',
      $omitted['detail'],
    );

    $item_links = array_filter(
      $omitted['links'],
      static fn (string $key): bool => str_starts_with($key, 'item--'),
      ARRAY_FILTER_USE_KEY,
    );
    $this->assertNotEmpty($item_links);
    $item_link = reset($item_links);
    $this->assertSame(
      'The current user is not allowed to view this relationship.',
      $item_link['meta']['detail'],
    );
  }

  /**
   * Tests `?include=roles` when the user can view the roles field.
   *
   * The referenced user_role entity is resolved into the `included` member.
   */
  public function testCurrentUserInfoResourceIncludeRolesWithAccess(): void {
    $role = Role::create(['id' => 'roles_admin', 'label' => 'Roles admin']);
    $role->grantPermission('administer users');
    $role->grantPermission('administer permissions');
    $role->save();
    $this->account->addRole($role->id());
    $this->account->save();

    $request = Request::create('/jsonapi/me', 'GET', ['include' => 'roles']);
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertArrayNotHasKey('meta', $document);
    $this->assertArrayHasKey('included', $document);
    $this->assertCount(1, $document['included']);

    $included = $document['included'][0];
    $this->assertSame('user_role--user_role', $included['type']);
    $this->assertSame($role->uuid(), $included['id']);
    $this->assertSame($role->id(), $included['attributes']['drupal_internal__id']);
    $this->assertSame($role->label(), $included['attributes']['label']);
  }

  /**
   * Tests that the resource varies the CSRF token by session.
   *
   * CurrentUserInfo::process() adds the `session` cache context so the CSRF
   * token is never shared between sessions; stamping a new session seed must
   * produce a different token.
   */
  public function testCurrentUserInfoResourceTokenCaching(): void {
    $role = Role::create(['id' => 'empty', 'label' => 'Empty']);
    $role->save();
    $this->account->addRole($role->id());
    $this->account->save();

    $request = Request::create('/jsonapi/me', 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    // The response varies by session, so a cache never serves one session's
    // CSRF token to another.
    $this->assertInstanceOf(CacheableResponseInterface::class, $response);
    $cache_contexts = $response->getCacheableMetadata()->getCacheContexts();
    $this->assertContains('user', $cache_contexts);
    $this->assertContains('session', $cache_contexts);

    $token1 = self::decodeResponse($response)['data']['attributes']['token'];
    $this->assertNotEmpty($token1);

    // Stamping a new session metadata seed is the kernel-test equivalent of
    // logging out and back in: the resource must hand back a fresh token.
    $metadata_bag = $this->container->get('session_manager.metadata_bag');
    $this->assertInstanceOf(MetadataBag::class, $metadata_bag);
    $metadata_bag->stampNew();

    $request = Request::create('/jsonapi/me', 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $token2 = self::decodeResponse($response)['data']['attributes']['token'];
    $this->assertNotEmpty($token2);
    $this->assertNotSame($token1, $token2);
  }

  /**
   * Tests the custom Add Comment resource.
   */
  public function testAddCommentResource(): void {
    $this->grantPermissionsToTestedRole([
      'access content',
      'access comments',
      'post comments',
    ]);

    $node = Node::create([
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
      'uid' => $this->account->id(),
    ]);
    $node->save();

    $body = json_encode([
      'data' => [
        'type' => 'comment--comment',
        'attributes' => [
          'entity_type' => 'node',
          'field_name' => 'comment',
          'subject' => 'Drama llama',
          'status' => 1,
          'comment_body' => [
            'value' => 'Llamas are awesome.',
            'format' => 'plain_text',
          ],
        ],
        'relationships' => [
          'entity_id' => [
            'data' => [
              'type' => 'node--article',
              'id' => $node->uuid(),
            ],
          ],
        ],
      ],
    ]);
    $request = Request::create('/jsonapi/comment/add', 'POST', [], [], [], [], $body);
    $request->headers->set('Accept', 'application/vnd.api+json');
    $request->headers->set('Content-Type', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(201, $response->getStatusCode(), (string) $response->getContent());
    $this->assertTrue($response->headers->has('Location'));

    $created_url = $response->headers->get('Location');
    $get_request = Request::create($created_url, 'GET');
    $get_request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($get_request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $exists = FALSE;
    $article_uuid = NestedArray::getValue($document, explode('/', 'data/relationships/entity_id/data/id'), $exists);
    $this->assertTrue($exists);
    $this->assertSame($node->uuid(), $article_uuid);
  }

  /**
   * Tests the Color Scheme resource with no includes.
   */
  public function testColorSchemeResourceWithNoIncludes(): void {
    // Blue Lagoon (f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf)
    $url = Url::fromUri('internal:/jsonapi/color_schemes/f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf');
    $response = $this->doColorSchemeRequest($url);
    $this->assertSame(
      200,
      $response->getStatusCode(),
      (string) $response->getContent()
    );

    $response_document = self::decodeResponse($response);
    $this->assertThat(
      $response_document,
      $this->logicalAnd(
        self::arrayHasKey('data'),
        self::logicalNot(
          $this->logicalAnd(
            self::arrayHasKey('meta'),
            self::arrayHasKey('included')
          )
        )
      )
    );

    $response_data = $response_document['data'];
    $this->assertThat(
      $response_data,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf', $response_data['id']);

    $links = $response_data['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => $url->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $response_data['attributes'];
    $this->assertEquals(['name' => 'Blue Lagoon'], $attributes);

    $relationships = $response_data['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => NULL,
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000003b3b3b',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000000071b3',
          ],
        ],
        'accent_colors'    => [
          'data' => [
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000055a8e',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000001d84c3',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000f6f6f2',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000f9f9f9',
            ],
          ],
        ],
      ],
      $relationships
    );

    // Plum (b9dfcef1-583f-403f-b0f6-c083bbdb9287)
    $url = Url::fromUri('internal:/jsonapi/color_schemes/b9dfcef1-583f-403f-b0f6-c083bbdb9287');
    $response = $this->doColorSchemeRequest($url);
    $this->assertSame(
      200,
      $response->getStatusCode(),
      (string) $response->getContent()
    );

    $response_document = self::decodeResponse($response);
    $this->assertThat(
      $response_document,
      $this->logicalAnd(
        self::arrayHasKey('data'),
        self::logicalNot(
          $this->logicalAnd(
            self::arrayHasKey('meta'),
            self::arrayHasKey('included')
          )
        )
      )
    );

    $response_data = $response_document['data'];
    $this->assertThat(
      $response_data,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('b9dfcef1-583f-403f-b0f6-c083bbdb9287', $response_data['id']);

    $links = $response_data['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => $url->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $response_data['attributes'];
    $this->assertEquals(['name' => 'Plum'], $attributes);

    $relationships = $response_data['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000fffdf7',
          ],
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000301313',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000009d408d',
          ],
        ],
        'accent_colors'    => [
          'data' => [
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000004c1c58',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000593662',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000edede7',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000e7e7e7',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000002c2c28',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000fffdf7',
            ],
          ],
        ],
      ],
      $relationships
    );

    // Black and White (5ccdea06-7f5a-4636-94e9-b0eae097121a)
    $url = Url::fromUri('internal:/jsonapi/color_schemes/5ccdea06-7f5a-4636-94e9-b0eae097121a');
    $response = $this->doColorSchemeRequest($url);
    $this->assertSame(
      200,
      $response->getStatusCode(),
      (string) $response->getContent()
    );

    $response_document = self::decodeResponse($response);
    $this->assertThat(
      $response_document,
      $this->logicalAnd(
        self::arrayHasKey('data'),
        self::logicalNot(
          $this->logicalAnd(
            self::arrayHasKey('meta'),
            self::arrayHasKey('included')
          )
        )
      )
    );

    $response_data = $response_document['data'];
    $this->assertThat(
      $response_data,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('5ccdea06-7f5a-4636-94e9-b0eae097121a', $response_data['id']);

    $links = $response_data['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => $url->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $response_data['attributes'];
    $this->assertEquals(['name' => 'Black and White'], $attributes);

    $relationships = $response_data['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000ffffff',
          ],
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000000000',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000000071b3',
          ],
        ],
        'accent_colors'    => [
          'data' => [],
        ],
      ],
      $relationships
    );
  }

  /**
   * Tests the Color Scheme resource when a bad relationship is provided.
   */
  public function testColorSchemeResourceWithBadIncludes(): void {
    // Blue Lagoon (f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf)
    $url = Url::fromUri('internal:/jsonapi/color_schemes/f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf?include=bad_relationship');
    $response = $this->doColorSchemeRequest($url);
    $this->assertSame(
      400,
      $response->getStatusCode(),
      (string) $response->getContent()
    );

    $response_document = self::decodeResponse($response);

    self::assertEquals(
      [
        'jsonapi' => [
          'version' => '1.1',
          'meta' => [
            'links' => [
              'self' => [
                'href' => JsonApiSpec::SUPPORTED_SPECIFICATION_PERMALINK,
              ],
            ],
          ],
        ],
        'errors' => [
          [
            'title' => 'Bad Request',
            'status' => '400',
            'detail' => '`bad_relationship` are not valid relationship names. Possible values: background_color, text_color, link_color, accent_colors',
            'links' => [
              'via' => [
                'href' => Url::fromUri(
                  'internal:/jsonapi/color_schemes/f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf?include=bad_relationship'
                )->setAbsolute()->toString(),
              ],
              'info' => [
                'href' => 'https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.4.1',
              ],
            ],
          ],
        ],
      ],
      $response_document
    );
  }

  /**
   * Tests the Color Scheme resource with includes.
   */
  public function testColorSchemeResourceWithIncludes(): void {
    // Blue Lagoon (f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf)
    $url = Url::fromUri('internal:/jsonapi/color_schemes/f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf?include=background_color,text_color,link_color,accent_colors');
    $response = $this->doColorSchemeRequest($url);
    $this->assertSame(
      200,
      $response->getStatusCode(),
      (string) $response->getContent()
    );

    $response_document = self::decodeResponse($response);
    $this->assertThat(
      $response_document,
      $this->logicalAnd(
        self::arrayHasKey('data'),
        self::arrayHasKey('included'),
        self::logicalNot(
          self::arrayHasKey('meta')
        )
      )
    );

    $response_data = $response_document['data'];
    $this->assertThat(
      $response_data,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf', $response_data['id']);

    $links = $response_data['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes/f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf'
          )->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $response_data['attributes'];
    $this->assertEquals(['name' => 'Blue Lagoon'], $attributes);

    $relationships = $response_data['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => NULL,
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000003b3b3b',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000000071b3',
          ],
        ],
        'accent_colors'    => [
          'data' => [
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000055a8e',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000001d84c3',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000f6f6f2',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000f9f9f9',
            ],
          ],
        ],
      ],
      $relationships
    );

    $response_included = $response_document['included'];
    $this->assertEquals(
      [
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-0000003b3b3b',
          'attributes' => [
            'name'      => 'Mine Shaft',
            'hex_value' => '3b3b3b',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-0000000071b3',
          'attributes' => [
            'name'      => 'Deep Cerulean',
            'hex_value' => '0071b3',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-000000055a8e',
          'attributes' => [
            'name'      => 'Venice Blue',
            'hex_value' => '055a8e',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-0000001d84c3',
          'attributes' => [
            'name'      => 'Curious Blue',
            'hex_value' => '1d84c3',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-000000f6f6f2',
          'attributes' => [
            'name'      => 'Cararra',
            'hex_value' => 'f6f6f2',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-000000f9f9f9',
          'attributes' => [
            'name'      => 'Alabaster',
            'hex_value' => 'f9f9f9',
          ],
        ],
      ],
      $response_included
    );

    // Plum (b9dfcef1-583f-403f-b0f6-c083bbdb9287)
    $url = Url::fromUri('internal:/jsonapi/color_schemes/b9dfcef1-583f-403f-b0f6-c083bbdb9287?include=background_color,text_color,link_color,accent_colors');
    $response = $this->doColorSchemeRequest($url);
    $this->assertSame(
      200,
      $response->getStatusCode(),
      (string) $response->getContent()
    );

    $response_document = self::decodeResponse($response);
    $this->assertThat(
      $response_document,
      $this->logicalAnd(
        self::arrayHasKey('data'),
        self::arrayHasKey('included'),
        self::logicalNot(
          self::arrayHasKey('meta')
        )
      )
    );

    $response_data = $response_document['data'];
    $this->assertThat(
      $response_data,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('b9dfcef1-583f-403f-b0f6-c083bbdb9287', $response_data['id']);

    $links = $response_data['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes/b9dfcef1-583f-403f-b0f6-c083bbdb9287'
          )->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $response_data['attributes'];
    $this->assertEquals(['name' => 'Plum'], $attributes);

    $relationships = $response_data['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000fffdf7',
          ],
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000301313',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000009d408d',
          ],
        ],
        'accent_colors'    => [
          'data' => [
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000004c1c58',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000593662',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000edede7',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000e7e7e7',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000002c2c28',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000fffdf7',
            ],
          ],
        ],
      ],
      $relationships
    );

    $response_included = $response_document['included'];
    $this->assertEquals(
      [
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-000000fffdf7',
          'attributes' => [
            'name'      => 'Quarter Pearl Lusta',
            'hex_value' => 'fffdf7',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-000000301313',
          'attributes' => [
            'name'      => 'Tamarind',
            'hex_value' => '301313',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-0000009d408d',
          'attributes' => [
            'name'      => 'Rouge',
            'hex_value' => '9d408d',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-0000004c1c58',
          'attributes' => [
            'name'      => 'Grape',
            'hex_value' => '4c1c58',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-000000593662',
          'attributes' => [
            'name'      => 'Voodoo',
            'hex_value' => '593662',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-000000edede7',
          'attributes' => [
            'name'      => 'Cararra',
            'hex_value' => 'edede7',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-000000e7e7e7',
          'attributes' => [
            'name'      => 'Mercury',
            'hex_value' => 'e7e7e7',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-0000002c2c28',
          'attributes' => [
            'name'      => 'Tuatara',
            'hex_value' => '2c2c28',
          ],
        ],
      ],
      $response_included
    );

    // Black and White (5ccdea06-7f5a-4636-94e9-b0eae097121a)
    $url = Url::fromUri('internal:/jsonapi/color_schemes/5ccdea06-7f5a-4636-94e9-b0eae097121a?include=background_color,text_color,link_color,accent_colors');
    $response = $this->doColorSchemeRequest($url);
    $this->assertSame(
      200,
      $response->getStatusCode(),
      (string) $response->getContent()
    );

    $response_document = self::decodeResponse($response);
    $this->assertThat(
      $response_document,
      $this->logicalAnd(
        self::arrayHasKey('data'),
        self::arrayHasKey('included'),
        self::logicalNot(
          self::arrayHasKey('meta')
        )
      )
    );

    $response_data = $response_document['data'];
    $this->assertThat(
      $response_data,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('5ccdea06-7f5a-4636-94e9-b0eae097121a', $response_data['id']);

    $links = $response_data['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes/5ccdea06-7f5a-4636-94e9-b0eae097121a'
          )->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $response_data['attributes'];
    $this->assertEquals(['name' => 'Black and White'], $attributes);

    $relationships = $response_data['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000ffffff',
          ],
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000000000',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000000071b3',
          ],
        ],
        'accent_colors'    => [
          'data' => [],
        ],
      ],
      $relationships
    );

    $response_included = $response_document['included'];
    $this->assertEquals(
      [
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-000000ffffff',
          'attributes' => [
            'name'      => 'White',
            'hex_value' => 'ffffff',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-000000000000',
          'attributes' => [
            'name'      => 'Black',
            'hex_value' => '000000',
          ],
        ],
        [
          'type'  => 'color--color',
          'id'    => '2c9189c4-48a9-4f64-8e91-0000000071b3',
          'attributes' => [
            'name'      => 'Deep Cerulean',
            'hex_value' => '0071b3',
          ],
        ],
      ],
      $response_included
    );
  }

  /**
   * Tests the Color Scheme list resource with no includes.
   */
  public function testColorSchemeListResourceWithNoIncludes(): void {
    $url = Url::fromUri('internal:/jsonapi/color_schemes');
    $response = $this->doColorSchemeRequest($url);
    $this->assertSame(
      200,
      $response->getStatusCode(),
      (string) $response->getContent()
    );

    $response_document = self::decodeResponse($response);
    $this->assertThat(
      $response_document,
      $this->logicalAnd(
        self::arrayHasKey('data'),
        self::logicalNot(
          $this->logicalAnd(
            self::arrayHasKey('meta'),
            self::arrayHasKey('included')
          )
        )
      )
    );

    $response_data = $response_document['data'];
    $this->assertCount(3, $response_data);

    // Blue Lagoon (f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf)
    $color_scheme = $response_data[0];
    $this->assertThat(
      $color_scheme,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf', $color_scheme['id']);

    $links = $color_scheme['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes/f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf'
          )->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $color_scheme['attributes'];
    $this->assertEquals(['name' => 'Blue Lagoon'], $attributes);

    $relationships = $color_scheme['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => NULL,
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000003b3b3b',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000000071b3',
          ],
        ],
        'accent_colors'    => [
          'data' => [
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000055a8e',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000001d84c3',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000f6f6f2',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000f9f9f9',
            ],
          ],
        ],
      ],
      $relationships
    );

    // Plum (b9dfcef1-583f-403f-b0f6-c083bbdb9287)
    $color_scheme = $response_data[1];
    $this->assertThat(
      $color_scheme,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('b9dfcef1-583f-403f-b0f6-c083bbdb9287', $color_scheme['id']);

    $links = $color_scheme['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes/b9dfcef1-583f-403f-b0f6-c083bbdb9287'
          )->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $color_scheme['attributes'];
    $this->assertEquals(['name' => 'Plum'], $attributes);

    $relationships = $color_scheme['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000fffdf7',
          ],
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000301313',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000009d408d',
          ],
        ],
        'accent_colors'    => [
          'data' => [
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000004c1c58',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000593662',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000edede7',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000e7e7e7',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000002c2c28',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000fffdf7',
            ],
          ],
        ],
      ],
      $relationships
    );

    // Black and White (5ccdea06-7f5a-4636-94e9-b0eae097121a)
    $color_scheme = $response_data[2];
    $this->assertThat(
      $color_scheme,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('5ccdea06-7f5a-4636-94e9-b0eae097121a', $color_scheme['id']);

    $links = $color_scheme['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes/5ccdea06-7f5a-4636-94e9-b0eae097121a'
          )->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $color_scheme['attributes'];
    $this->assertEquals(['name' => 'Black and White'], $attributes);

    $relationships = $color_scheme['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000ffffff',
          ],
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000000000',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000000071b3',
          ],
        ],
        'accent_colors'    => [
          'data' => [],
        ],
      ],
      $relationships
    );
  }

  /**
   * Tests the Color Scheme list resource when a bad relationship is provided.
   */
  public function testColorSchemeListResourceWithBadIncludes(): void {
    $url = Url::fromUri('internal:/jsonapi/color_schemes?include=bad_relationship');
    $response = $this->doColorSchemeRequest($url);
    $this->assertSame(
      400,
      $response->getStatusCode(),
      (string) $response->getContent()
    );

    $response_document = self::decodeResponse($response);

    self::assertEquals(
      [
        'jsonapi' => [
          'version' => '1.1',
          'meta' => [
            'links' => [
              'self' => [
                'href' => JsonApiSpec::SUPPORTED_SPECIFICATION_PERMALINK,
              ],
            ],
          ],
        ],
        'errors' => [
          [
            'title' => 'Bad Request',
            'status' => '400',
            'detail' => '`bad_relationship` are not valid relationship names. Possible values: background_color, text_color, link_color, accent_colors',
            'links' => [
              'via' => [
                'href' => Url::fromUri(
                  'internal:/jsonapi/color_schemes?include=bad_relationship'
                )->setAbsolute()->toString(),
              ],
              'info' => [
                'href' => 'https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.4.1',
              ],
            ],
          ],
        ],
      ],
      $response_document
    );
  }

  /**
   * Tests the Color Scheme list resource with includes.
   */
  public function testColorSchemeListResourceWithIncludes(): void {
    $url = Url::fromUri('internal:/jsonapi/color_schemes?include=background_color,text_color,link_color,accent_colors');
    $response = $this->doColorSchemeRequest($url);
    $this->assertSame(
      200,
      $response->getStatusCode(),
      (string) $response->getContent()
    );

    $response_document = self::decodeResponse($response);
    $this->assertThat(
      $response_document,
      $this->logicalAnd(
        self::arrayHasKey('data'),
        self::arrayHasKey('included'),
        self::logicalNot(
          self::arrayHasKey('meta')
        )
      )
    );

    $response_data = $response_document['data'];
    $this->assertCount(3, $response_data);

    // Blue Lagoon (f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf)
    $color_scheme = $response_data[0];
    $this->assertThat(
      $color_scheme,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf', $color_scheme['id']);

    $links = $color_scheme['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes/f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf'
          )->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $color_scheme['attributes'];
    $this->assertEquals(['name' => 'Blue Lagoon'], $attributes);

    $relationships = $color_scheme['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => NULL,
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000003b3b3b',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000000071b3',
          ],
        ],
        'accent_colors'    => [
          'data' => [
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000055a8e',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000001d84c3',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000f6f6f2',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000f9f9f9',
            ],
          ],
        ],
      ],
      $relationships
    );

    // Plum (b9dfcef1-583f-403f-b0f6-c083bbdb9287)
    $color_scheme = $response_data[1];
    $this->assertThat(
      $color_scheme,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('b9dfcef1-583f-403f-b0f6-c083bbdb9287', $color_scheme['id']);

    $links = $color_scheme['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes/b9dfcef1-583f-403f-b0f6-c083bbdb9287'
          )->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $color_scheme['attributes'];
    $this->assertEquals(['name' => 'Plum'], $attributes);

    $relationships = $color_scheme['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000fffdf7',
          ],
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000301313',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000009d408d',
          ],
        ],
        'accent_colors'    => [
          'data' => [
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000004c1c58',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000593662',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000edede7',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000e7e7e7',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000002c2c28',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000fffdf7',
            ],
          ],
        ],
      ],
      $relationships
    );

    // Black and White (5ccdea06-7f5a-4636-94e9-b0eae097121a)
    $color_scheme = $response_data[2];
    $this->assertThat(
      $color_scheme,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('5ccdea06-7f5a-4636-94e9-b0eae097121a', $color_scheme['id']);

    $links = $color_scheme['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes/5ccdea06-7f5a-4636-94e9-b0eae097121a'
          )->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $color_scheme['attributes'];
    $this->assertEquals(['name' => 'Black and White'], $attributes);

    $relationships = $color_scheme['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000ffffff',
          ],
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000000000',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000000071b3',
          ],
        ],
        'accent_colors'    => [
          'data' => [],
        ],
      ],
      $relationships
    );

    // Common Includes.
    $response_included = $response_document['included'];
    $this->assertCount(16, $response_included);

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-0000003b3b3b',
        'attributes' => [
          'name'      => 'Mine Shaft',
          'hex_value' => '3b3b3b',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-0000000071b3',
        'attributes' => [
          'name'      => 'Deep Cerulean',
          'hex_value' => '0071b3',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000055a8e',
        'attributes' => [
          'name'      => 'Venice Blue',
          'hex_value' => '055a8e',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-0000001d84c3',
        'attributes' => [
          'name'      => 'Curious Blue',
          'hex_value' => '1d84c3',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000f6f6f2',
        'attributes' => [
          'name'      => 'Cararra',
          'hex_value' => 'f6f6f2',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000f9f9f9',
        'attributes' => [
          'name'      => 'Alabaster',
          'hex_value' => 'f9f9f9',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000fffdf7',
        'attributes' => [
          'name'      => 'Quarter Pearl Lusta',
          'hex_value' => 'fffdf7',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000301313',
        'attributes' => [
          'name'      => 'Tamarind',
          'hex_value' => '301313',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-0000009d408d',
        'attributes' => [
          'name'      => 'Rouge',
          'hex_value' => '9d408d',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-0000004c1c58',
        'attributes' => [
          'name'      => 'Grape',
          'hex_value' => '4c1c58',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000593662',
        'attributes' => [
          'name'      => 'Voodoo',
          'hex_value' => '593662',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000edede7',
        'attributes' => [
          'name'      => 'Cararra',
          'hex_value' => 'edede7',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000e7e7e7',
        'attributes' => [
          'name'      => 'Mercury',
          'hex_value' => 'e7e7e7',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-0000002c2c28',
        'attributes' => [
          'name'      => 'Tuatara',
          'hex_value' => '2c2c28',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000ffffff',
        'attributes' => [
          'name'      => 'White',
          'hex_value' => 'ffffff',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000000000',
        'attributes' => [
          'name'      => 'Black',
          'hex_value' => '000000',
        ],
      ],
      $response_included
    );
  }

  /**
   * Tests the Color Scheme list resource with pagination.
   */
  public function testColorSchemeListResourceWithPagination(): void {
    // Page 1.
    $url = Url::fromUri('internal:/jsonapi/color_schemes?include=background_color,text_color,link_color,accent_colors&page[limit]=2');
    $response = $this->doColorSchemeRequest($url);
    $this->assertSame(
      200,
      $response->getStatusCode(),
      (string) $response->getContent()
    );

    $response_document = self::decodeResponse($response);
    $this->assertThat(
      $response_document,
      $this->logicalAnd(
        self::arrayHasKey('data'),
        self::arrayHasKey('included'),
        self::arrayHasKey('links'),
        self::logicalNot(
          self::arrayHasKey('meta')
        )
      )
    );

    $response_links = $response_document['links'];
    $this->assertEquals(
      [
        'next' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes?include=background_color,text_color,link_color,accent_colors&page[offset]=2&page[limit]=2'
          )->setAbsolute()->toString(),
        ],
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes?include=background_color,text_color,link_color,accent_colors&page[limit]=2'
          )->setAbsolute()->toString(),
        ],
      ],
      $response_links
    );

    $response_data = $response_document['data'];
    $this->assertCount(2, $response_data);

    // Blue Lagoon (f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf)
    $color_scheme = $response_data[0];
    $this->assertThat(
      $color_scheme,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf', $color_scheme['id']);

    $links = $color_scheme['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes/f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf'
          )->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $color_scheme['attributes'];
    $this->assertEquals(['name' => 'Blue Lagoon'], $attributes);

    $relationships = $color_scheme['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => NULL,
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000003b3b3b',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000000071b3',
          ],
        ],
        'accent_colors'    => [
          'data' => [
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000055a8e',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000001d84c3',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000f6f6f2',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000f9f9f9',
            ],
          ],
        ],
      ],
      $relationships
    );

    // Plum (b9dfcef1-583f-403f-b0f6-c083bbdb9287)
    $color_scheme = $response_data[1];
    $this->assertThat(
      $color_scheme,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('b9dfcef1-583f-403f-b0f6-c083bbdb9287', $color_scheme['id']);

    $links = $color_scheme['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes/b9dfcef1-583f-403f-b0f6-c083bbdb9287'
          )->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $color_scheme['attributes'];
    $this->assertEquals(['name' => 'Plum'], $attributes);

    $relationships = $color_scheme['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000fffdf7',
          ],
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000301313',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000009d408d',
          ],
        ],
        'accent_colors'    => [
          'data' => [
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000004c1c58',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000593662',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000edede7',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000e7e7e7',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-0000002c2c28',
            ],
            [
              'type' => 'color--color',
              'id'   => '2c9189c4-48a9-4f64-8e91-000000fffdf7',
            ],
          ],
        ],
      ],
      $relationships
    );

    // Common Includes.
    $response_included = $response_document['included'];
    $this->assertCount(14, $response_included);

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-0000003b3b3b',
        'attributes' => [
          'name'      => 'Mine Shaft',
          'hex_value' => '3b3b3b',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-0000000071b3',
        'attributes' => [
          'name'      => 'Deep Cerulean',
          'hex_value' => '0071b3',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000055a8e',
        'attributes' => [
          'name'      => 'Venice Blue',
          'hex_value' => '055a8e',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-0000001d84c3',
        'attributes' => [
          'name'      => 'Curious Blue',
          'hex_value' => '1d84c3',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000f6f6f2',
        'attributes' => [
          'name'      => 'Cararra',
          'hex_value' => 'f6f6f2',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000f9f9f9',
        'attributes' => [
          'name'      => 'Alabaster',
          'hex_value' => 'f9f9f9',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000fffdf7',
        'attributes' => [
          'name'      => 'Quarter Pearl Lusta',
          'hex_value' => 'fffdf7',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000301313',
        'attributes' => [
          'name'      => 'Tamarind',
          'hex_value' => '301313',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-0000009d408d',
        'attributes' => [
          'name'      => 'Rouge',
          'hex_value' => '9d408d',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-0000004c1c58',
        'attributes' => [
          'name'      => 'Grape',
          'hex_value' => '4c1c58',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000593662',
        'attributes' => [
          'name'      => 'Voodoo',
          'hex_value' => '593662',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000edede7',
        'attributes' => [
          'name'      => 'Cararra',
          'hex_value' => 'edede7',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000e7e7e7',
        'attributes' => [
          'name'      => 'Mercury',
          'hex_value' => 'e7e7e7',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-0000002c2c28',
        'attributes' => [
          'name'      => 'Tuatara',
          'hex_value' => '2c2c28',
        ],
      ],
      $response_included
    );

    // Page 2.
    $url = Url::fromUri('internal:/jsonapi/color_schemes?include=background_color,text_color,link_color,accent_colors&page[limit]=2&page[offset]=2');
    $response = $this->doColorSchemeRequest($url);
    $this->assertSame(
      200,
      $response->getStatusCode(),
      (string) $response->getContent()
    );

    $response_document = self::decodeResponse($response);
    $this->assertThat(
      $response_document,
      $this->logicalAnd(
        self::arrayHasKey('data'),
        self::arrayHasKey('included'),
        self::arrayHasKey('links'),
        self::logicalNot(
          self::arrayHasKey('meta')
        )
      )
    );

    $response_links = $response_document['links'];
    $this->assertEquals(
      [
        'first' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes?include=background_color,text_color,link_color,accent_colors&page[offset]=0&page[limit]=2'
          )->setAbsolute()->toString(),
        ],
        'prev' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes?include=background_color,text_color,link_color,accent_colors&page[offset]=0&page[limit]=2'
          )->setAbsolute()->toString(),
        ],
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes?include=background_color,text_color,link_color,accent_colors&page[limit]=2&page[offset]=2'
          )->setAbsolute()->toString(),
        ],
      ],
      $response_links
    );

    $response_data = $response_document['data'];
    $this->assertCount(1, $response_data);

    // Black and White (5ccdea06-7f5a-4636-94e9-b0eae097121a)
    $color_scheme = $response_data[0];
    $this->assertThat(
      $color_scheme,
      $this->logicalAnd(
        self::arrayHasKey('id'),
        self::arrayHasKey('links'),
        self::arrayHasKey('attributes'),
        self::arrayHasKey('relationships')
      )
    );
    $this->assertEquals('5ccdea06-7f5a-4636-94e9-b0eae097121a', $color_scheme['id']);

    $links = $color_scheme['links'];
    $this->assertEquals(
      [
        'self' => [
          'href' => Url::fromUri(
            'internal:/jsonapi/color_schemes/5ccdea06-7f5a-4636-94e9-b0eae097121a'
          )->setAbsolute()->toString(),
        ],
      ],
      $links
    );

    $attributes = $color_scheme['attributes'];
    $this->assertEquals(['name' => 'Black and White'], $attributes);

    $relationships = $color_scheme['relationships'];
    $this->assertEquals(
      [
        'background_color' => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000ffffff',
          ],
        ],
        'text_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-000000000000',
          ],
        ],
        'link_color'       => [
          'data' => [
            'type' => 'color--color',
            'id'   => '2c9189c4-48a9-4f64-8e91-0000000071b3',
          ],
        ],
        'accent_colors'    => [
          'data' => [],
        ],
      ],
      $relationships
    );

    // Common Includes.
    $response_included = $response_document['included'];
    $this->assertCount(3, $response_included);

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-0000000071b3',
        'attributes' => [
          'name'      => 'Deep Cerulean',
          'hex_value' => '0071b3',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000ffffff',
        'attributes' => [
          'name'      => 'White',
          'hex_value' => 'ffffff',
        ],
      ],
      $response_included
    );

    $this->assertContains(
      [
        'type'  => 'color--color',
        'id'    => '2c9189c4-48a9-4f64-8e91-000000000000',
        'attributes' => [
          'name'      => 'Black',
          'hex_value' => '000000',
        ],
      ],
      $response_included
    );
  }

  /**
   * Tests a resource backed by a SelectInterface with a JOIN.
   *
   * Exercises the SelectInterface path on EntityQueryResourceBase: the
   * resource joins node_field_data to comment_entity_statistics, orders by
   * comment_count, and loads the resulting node IDs as JSON:API resource
   * objects. The entity query API cannot express the JOIN; this is the
   * #3132728 use case.
   */
  public function testCommentedArticlesSelectQueryResource(): void {
    // comment_install() sets this state; kernel tests don't run install hooks,
    // so the comment_entity_statistics table is never updated without it.
    $this->container->get('state')->set('comment.maintain_entity_statistics', TRUE);

    // Five articles; the first three get comments, the last two stay empty
    // so the JOIN-driven filter is visible in the response shape.
    $articles = [];
    for ($i = 0; $i < 5; $i++) {
      $node = Node::create([
        'type' => 'article',
        'title' => sprintf('Article %d', $i),
        'status' => 1,
        'uid' => $this->account->id(),
      ]);
      $node->save();
      $articles[] = $node;
    }

    // Comment counts: article[0] = 3, article[1] = 1, article[2] = 2.
    // Expected order (DESC by comment_count): [0], [2], [1].
    $comment_counts = [0 => 3, 1 => 1, 2 => 2];
    foreach ($comment_counts as $article_index => $count) {
      for ($c = 0; $c < $count; $c++) {
        Comment::create([
          'entity_type' => 'node',
          'entity_id' => $articles[$article_index]->id(),
          'field_name' => 'comment',
          'comment_type' => 'comment',
          'subject' => sprintf('Comment %d on article %d', $c, $article_index),
          'comment_body' => ['value' => 'Body', 'format' => 'plain_text'],
          'uid' => $this->account->id(),
          'status' => 1,
        ])->save();
      }
    }

    $this->grantPermissionsToTestedRole(['access content']);

    // First page: limit 2 → expect article[0] (3 comments) and article[2] (2).
    $request = Request::create('/jsonapi/commented-articles?page[limit]=2', 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertCount(2, $document['data']);
    $this->assertSame($articles[0]->uuid(), $document['data'][0]['id']);
    $this->assertSame($articles[2]->uuid(), $document['data'][1]['id']);
    $this->assertArrayHasKey('next', $document['links']);
    $this->assertArrayHasKey('last', $document['links']);
    $this->assertArrayNotHasKey('first', $document['links']);
    $this->assertArrayNotHasKey('prev', $document['links']);
    // meta.count is the total number of commented articles (3), not the page
    // size — the select count query must ignore the paginator's range.
    $this->assertSame(3, $document['meta']['count'] ?? NULL);
    // The `last` link points at the final page (offset 2 for a page size of 2).
    // cspell:disable-next-line
    $this->assertStringContainsString('page%5Boffset%5D=2', $document['links']['last']['href']);

    // Follow the next link: should yield article[1] only (1 comment), no
    // further pages.
    $request = Request::create($document['links']['next']['href'], 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertCount(1, $document['data']);
    $this->assertSame($articles[1]->uuid(), $document['data'][0]['id']);
    $this->assertArrayNotHasKey('next', $document['links']);
    $this->assertArrayHasKey('first', $document['links']);
    $this->assertArrayHasKey('prev', $document['links']);
    // The total is stable across pages.
    $this->assertSame(3, $document['meta']['count'] ?? NULL);
  }

  /**
   * Sends a JSON:API GET request for a color scheme URL.
   *
   * @param \Drupal\Core\Url $url
   *   The URL to request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  private function doColorSchemeRequest(Url $url): Response {
    $request = Request::create($url->toString());
    $request->headers->set('Accept', 'application/vnd.api+json');
    // Catch exceptions so the bad-include tests can assert on the 400 error
    // response the resource raises as a CacheableBadRequestHttpException.
    return $this->request($request, TRUE);
  }

  /**
   * Grants permissions to the authenticated role.
   *
   * @param string[] $permissions
   *   Permissions to grant.
   */
  protected function grantPermissionsToTestedRole(array $permissions): void {
    $this->grantPermissions(Role::load(RoleInterface::AUTHENTICATED_ID), $permissions);
  }

}
