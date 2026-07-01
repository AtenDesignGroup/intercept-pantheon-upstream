<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi_resources\Kernel;

use Drupal\Core\Url;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;
use Drupal\Tests\jsonapi_resources\Kernel\Traits\RequestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests that jsonapi_resources does not shadow other normalizer decorators.
 *
 * The commerce_api module (a major consumer) decorates
 * serializer.normalizer.resource_object.jsonapi to add top-level resource meta.
 * jsonapi_resources decorates the same service for its custom-relationship
 * feature. Before #3599417 the jsonapi_resources decorator replaced rather
 * than delegated, so it shadowed commerce_api's decorator and the meta key
 * disappeared from every resource. The jsonapi_resources_meta_test module
 * stands in for commerce_api here.
 *
 * @group jsonapi_resources
 *
 * @see https://www.drupal.org/project/jsonapi_resources/issues/3599417
 */
final class MetaDecoratorChainTest extends JsonapiKernelTestBase {

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
    'jsonapi',
    'jsonapi_resources',
    'jsonapi_resources_test',
    'jsonapi_resources_meta_test',
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
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'user', 'filter', 'node', 'jsonapi']);

    NodeType::create(['name' => 'article', 'type' => 'article'])->save();

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
   * Custom Relationship resources still render when a second decorator exists.
   *
   * Resource objects with a custom Relationship field cannot be delegated to
   * the decorated normalizer (it would fatal serializing the Relationship), so
   * jsonapi_resources normalizes them itself. This guards that the
   * custom-relationship feature keeps working while another module also
   * decorates the resource object normalizer.
   */
  public function testCustomRelationshipResourceRendersWithSecondDecorator(): void {
    // Blue Lagoon, defined by jsonapi_resources_test's ColorSchemeQuery.
    $url = Url::fromUri('internal:/jsonapi/color_schemes/f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf');
    $request = Request::create($url->toString());
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $data = $document['data'];

    // The custom relationships still render through the chaining decorator.
    $this->assertArrayHasKey('relationships', $data);
    $this->assertSame(
      ['type' => 'color--color', 'id' => '2c9189c4-48a9-4f64-8e91-0000003b3b3b'],
      $data['relationships']['text_color']['data'] ?? NULL,
    );
    $this->assertSame(['name' => 'Blue Lagoon'], $data['attributes']);
  }

  /**
   * The meta a second decorator adds survives on entity-backed resources.
   */
  public function testEntityResourceMetaSurvivesSecondDecorator(): void {
    for ($i = 0; $i < 3; $i++) {
      Node::create([
        'type' => 'article',
        'title' => $this->randomMachineName(),
        'status' => 1,
        'promote' => 1,
      ])->save();
    }
    $this->grantPermissions(Role::load(RoleInterface::AUTHENTICATED_ID), ['access content', 'access user profiles']);

    $request = Request::create('/jsonapi/featured-content', 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertNotEmpty($document['data']);
    foreach ($document['data'] as $resource) {
      $this->assertSame(['shim' => TRUE], $resource['meta'] ?? NULL);
    }
  }

}
