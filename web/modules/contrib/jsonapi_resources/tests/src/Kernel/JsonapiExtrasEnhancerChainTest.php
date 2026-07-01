<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi_resources\Kernel;

use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;
use Drupal\Tests\jsonapi_resources\Kernel\Traits\RequestTrait;
use Drupal\jsonapi_extras\Entity\JsonapiResourceConfig;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests that jsonapi_resources does not shadow jsonapi_extras field enhancers.
 *
 * Both jsonapi_resources and jsonapi_extras decorate
 * serializer.normalizer.resource_object.jsonapi. jsonapi_extras applies
 * config-entity field enhancers in that decorator (enhanceConfigFields).
 * Before #3599417 the jsonapi_resources decorator extended core and replaced
 * the chain instead of delegating, so whichever decorator loaded outermost
 * silently dropped the other. When jsonapi_resources won, jsonapi_extras'
 * enhancer never ran and the field was returned raw.
 *
 * This is the real-module regression guard for #3599418: the jsonapi_resources
 * decorator is deterministically outermost (decoration_priority -100) and must
 * delegate to jsonapi_extras for any resource object it does not handle itself.
 *
 * Note: only config-entity enhancers flow through the resource object
 * normalizer. Content-entity enhancers run through the field item normalizer,
 * which jsonapi_resources does not decorate, so a content-entity resource would
 * pass even with the bug present.
 *
 * @group jsonapi_resources
 *
 * @see https://www.drupal.org/project/jsonapi_resources/issues/3599418
 * @see https://www.drupal.org/project/jsonapi_resources/issues/3599417
 */
final class JsonapiExtrasEnhancerChainTest extends JsonapiKernelTestBase {

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

    // The description is a JSON string so the "json" enhancer has something to
    // decode. node_type is a config entity, so its normalization passes through
    // jsonapi_extras' enhanceConfigFields() — the path jsonapi_resources could
    // shadow.
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'description' => '{"answer":42}',
    ])->save();

    // Run requests as the superuser (UID 1) so config-entity view access does
    // not get in the way of asserting the normalizer chain.
    $admin = User::create(['uid' => 1, 'name' => 'admin']);
    $admin->save();
    $this->container->get('current_user')->setAccount($admin);

    JsonapiResourceConfig::create([
      'id' => 'node_type--node_type',
      'disabled' => FALSE,
      'path' => 'node_type/node_type',
      'resourceType' => 'node_type--node_type',
      'resourceFields' => [
        'description' => [
          'fieldName' => 'description',
          'publicName' => 'description',
          'enhancer' => ['id' => 'json'],
          'disabled' => FALSE,
        ],
      ],
    ])->save();

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * A jsonapi_extras enhancer still runs when jsonapi_resources is installed.
   */
  public function testConfigEntityEnhancerSurvivesDecoratorChain(): void {
    $uuid = NodeType::load('article')->uuid();
    $request = Request::create('/jsonapi/node_type/node_type/' . $uuid, 'GET');
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->request($request);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

    $document = self::decodeResponse($response);

    // If jsonapi_resources shadowed jsonapi_extras, the enhancer would not run
    // and "description" would still be the raw JSON string.
    $this->assertSame(
      ['answer' => 42],
      $document['data']['attributes']['description'] ?? NULL,
    );
  }

}
