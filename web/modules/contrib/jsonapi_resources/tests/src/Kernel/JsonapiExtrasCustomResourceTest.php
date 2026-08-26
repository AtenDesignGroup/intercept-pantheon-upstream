<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi_resources\Kernel;

use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;
use Drupal\Tests\jsonapi_resources\Kernel\Traits\RequestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\jsonapi_extras\Entity\JsonapiResourceConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests jsonapi_extras overrides apply to a custom jsonapi_resources route.
 *
 * The jsonapi_extras module configures field aliases and enhancers on the
 * resource type, which the resource type repository returns to every consumer.
 * A custom jsonapi_resources resource builds its resource objects from that
 * same repository and normalizes them through the same decorated normalizer,
 * so the overrides must show up in a custom resource's response — not just on
 * core JSON:API routes.
 *
 * @group jsonapi_resources
 *
 * @see https://www.drupal.org/project/jsonapi_resources/issues/3167729
 */
final class JsonapiExtrasCustomResourceTest extends JsonapiKernelTestBase {

  use RequestTrait;

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
    'jsonapi_extras',
    'jsonapi_resources',
    'jsonapi_resources_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'user', 'filter', 'node', 'jsonapi']);

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    // A content field whose value is a JSON string, so the "json" enhancer has
    // something to decode. This exercises the enhancer on a content entity,
    // which runs through the field item normalizer.
    FieldStorageConfig::create([
      'field_name' => 'field_payload',
      'entity_type' => 'node',
      'type' => 'string_long',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_payload',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    // Run as the superuser so view access never masks the normalizer output.
    $admin = User::create(['uid' => 1, 'name' => 'admin']);
    $admin->save();
    $this->container->get('current_user')->setAccount($admin);

    // Alias title -> headline and decode field_payload with the json enhancer.
    JsonapiResourceConfig::create([
      'id' => 'node--article',
      'disabled' => FALSE,
      'path' => 'node/article',
      'resourceType' => 'node--article',
      'resourceFields' => [
        'title' => [
          'fieldName' => 'title',
          'publicName' => 'headline',
          'disabled' => FALSE,
        ],
        'field_payload' => [
          'fieldName' => 'field_payload',
          'publicName' => 'field_payload',
          'enhancer' => ['id' => 'json'],
          'disabled' => FALSE,
        ],
      ],
    ])->save();

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * A custom resource reflects jsonapi_extras field aliases and enhancers.
   */
  public function testExtrasOverridesApplyToCustomResource(): void {
    Node::create([
      'type' => 'article',
      'title' => 'Hello world',
      'field_payload' => '{"answer":42}',
      'status' => 1,
      'promote' => 1,
    ])->save();

    $request = Request::create('/jsonapi/featured-content', 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);
    $this->assertCount(1, $document['data']);
    $attributes = $document['data'][0]['attributes'];

    // The field alias applies: "title" is exposed as "headline".
    $this->assertArrayHasKey('headline', $attributes);
    $this->assertArrayNotHasKey('title', $attributes);
    $this->assertSame('Hello world', $attributes['headline']);

    // The enhancer applies: the JSON string is decoded to a structure.
    $this->assertSame(['answer' => 42], $attributes['field_payload']);
  }

}
