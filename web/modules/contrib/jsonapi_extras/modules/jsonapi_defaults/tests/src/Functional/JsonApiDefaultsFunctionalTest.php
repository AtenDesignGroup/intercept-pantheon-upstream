<?php

namespace Drupal\Tests\jsonapi_defaults\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\jsonapi_extras\Entity\JsonapiResourceConfig;
use Drupal\Tests\jsonapi_extras\Functional\JsonApiExtrasFunctionalTestBase;

/**
 * The test class for the JSON API Defaults functionality.
 *
 * @group jsonapi_extras
 */
class JsonApiDefaultsFunctionalTest extends JsonApiExtrasFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'jsonapi_defaults',
  ];

  /**
   * Test the GET method.
   */
  public function testRead() {
    $this->createDefaultContent(4, 5, TRUE, TRUE, static::IS_NOT_MULTILINGUAL);
    // 1. Apply default filters and includes on a resource and a related
    // resource.
    $response = $this->drupalGet('/api/articles');
    $parsed_response = Json::decode($response);
    $this->assertArrayHasKey('data', $parsed_response);
    $this->assertCount(1, $parsed_response['data']);
    $this->assertEquals(3, $parsed_response['data'][0]['attributes']['internalId']);
    $this->assertArrayHasKey('included', $parsed_response);
    $this->assertGreaterThan(0, count($parsed_response['included']));
    // Make sure related resources don't fail.
    $response = $this->drupalGet('/api/articles/' . $this->nodes[0]->uuid() . '/owner');
    $parsed_response = Json::decode($response);
    $this->assertArrayHasKey('data', $parsed_response);
    $this->assertEquals('user--user', $parsed_response['data']['type']);

    // 2. Merge default filters with explicit filters.
    $response = $this->drupalGet('/api/articles', [
      'query' => [
        'filter' => [
          'i' => [
            'condition' => [
              'path' => 'internalId',
              'value' => '2',
            ],
          ],
        ],
      ],
    ]);
    $parsed_response = Json::decode($response);
    $this->assertArrayHasKey('data', $parsed_response);
    // internalId cannot be 2 and 3 at the same time.
    $this->assertCount(0, $parsed_response['data']);

    // 3. Override the default includes.
    $response = $this->drupalGet('/api/articles', [
      'query' => ['include' => ''],
    ]);
    $parsed_response = Json::decode($response);
    $this->assertArrayNotHasKey('included', $parsed_response);
  }

  /**
   * Creates the JSON API Resource Config entities to override the resources.
   */
  protected static function overrideResources() {
    // Override paths and fields in the articles resource.
    JsonapiResourceConfig::create([
      'id' => 'node--article',
      'third_party_settings' => [
        'jsonapi_defaults' => [
          'default_filter' => [
            'filter:nidFilter#condition#path' => 'internalId',
            'filter:nidFilter#condition#value' => 3,
          ],
          // TODO: Change this to 'tags.vid'.
          'default_include' => ['tags'],
        ],
      ],
      'disabled' => FALSE,
      'path' => 'articles',
      'resourceType' => 'articles',
      'resourceFields' => [
        'nid' => [
          'fieldName' => 'nid',
          'publicName' => 'internalId',
          'enhancer' => ['id' => ''],
          'disabled' => FALSE,
        ],
        'uuid' => [
          'fieldName' => 'uuid',
          'publicName' => 'uuid',
          'enhancer' => ['id' => ''],
          'disabled' => TRUE,
        ],
        'vid' => [
          'fieldName' => 'vid',
          'publicName' => 'vid',
          'enhancer' => ['id' => ''],
          'disabled' => TRUE,
        ],
        'langcode' => [
          'fieldName' => 'langcode',
          'publicName' => 'langcode',
          'enhancer' => ['id' => ''],
          'disabled' => TRUE,
        ],
        'type' => [
          'fieldName' => 'type',
          'publicName' => 'contentType',
          'enhancer' => ['id' => ''],
          'disabled' => FALSE,
        ],
        'status' => [
          'fieldName' => 'status',
          'publicName' => 'isPublished',
          'enhancer' => ['id' => ''],
          'disabled' => FALSE,
        ],
        'title' => [
          'fieldName' => 'title',
          'publicName' => 'title',
          'enhancer' => ['id' => ''],
          'disabled' => FALSE,
        ],
        'uid' => [
          'fieldName' => 'uid',
          'publicName' => 'owner',
          'enhancer' => ['id' => ''],
          'disabled' => FALSE,
        ],
        'created' => [
          'fieldName' => 'created',
          'publicName' => 'createdAt',
          'enhancer' => [
            'id' => 'date_time',
            'settings' => ['dateTimeFormat' => 'Y-m-d\TH:i:sO'],
          ],
          'disabled' => FALSE,
        ],
        'changed' => [
          'fieldName' => 'changed',
          'publicName' => 'updatedAt',
          'enhancer' => [
            'id' => 'date_time',
            'settings' => ['dateTimeFormat' => 'Y-m-d\TH:i:sO'],
          ],
          'disabled' => FALSE,
        ],
        'promote' => [
          'fieldName' => 'promote',
          'publicName' => 'isPromoted',
          'enhancer' => ['id' => ''],
          'disabled' => FALSE,
        ],
        'sticky' => [
          'fieldName' => 'sticky',
          'publicName' => 'sticky',
          'enhancer' => ['id' => ''],
          'disabled' => TRUE,
        ],
        'revision_timestamp' => [
          'fieldName' => 'revision_timestamp',
          'publicName' => 'revision_timestamp',
          'enhancer' => ['id' => ''],
          'disabled' => TRUE,
        ],
        'revision_uid' => [
          'fieldName' => 'revision_uid',
          'publicName' => 'revision_uid',
          'enhancer' => ['id' => ''],
          'disabled' => TRUE,
        ],
        'revision_log' => [
          'fieldName' => 'revision_log',
          'publicName' => 'revision_log',
          'enhancer' => ['id' => ''],
          'disabled' => TRUE,
        ],
        'revision_translation_affected' => [
          'fieldName' => 'revision_translation_affected',
          'publicName' => 'revision_translation_affected',
          'enhancer' => ['id' => ''],
          'disabled' => TRUE,
        ],
        'default_langcode' => [
          'fieldName' => 'default_langcode',
          'publicName' => 'default_langcode',
          'enhancer' => ['id' => ''],
          'disabled' => TRUE,
        ],
        'path' => [
          'fieldName' => 'path',
          'publicName' => 'path',
          'enhancer' => ['id' => ''],
          'disabled' => FALSE,
        ],
        'body' => [
          'fieldName' => 'body',
          'publicName' => 'body',
          'enhancer' => ['id' => 'nested', 'settings' => ['path' => 'value']],
          'disabled' => FALSE,
        ],
        'field_link' => [
          'fieldName' => 'field_link',
          'publicName' => 'link',
          'enhancer' => ['id' => 'uuid_link'],
          'disabled' => FALSE,
        ],
        'field_timestamp' => [
          'fieldName' => 'field_timestamp',
          'publicName' => 'timestamp',
          'enhancer' => [
            'id' => 'date_time',
            'settings' => ['dateTimeFormat' => 'Y-m-d\TH:i:sO'],
          ],
          'disabled' => FALSE,
        ],
        'comment' => [
          'fieldName' => 'comment',
          'publicName' => 'comment',
          'enhancer' => ['id' => ''],
          'disabled' => FALSE,
        ],
        'field_image' => [
          'fieldName' => 'field_image',
          'publicName' => 'image',
          'enhancer' => ['id' => ''],
          'disabled' => FALSE,
        ],
        'field_recipes' => [
          'fieldName' => 'field_recipes',
          'publicName' => 'recipes',
          'enhancer' => ['id' => ''],
          'disabled' => FALSE,
        ],
        'field_tags' => [
          'fieldName' => 'field_tags',
          'publicName' => 'tags',
          'enhancer' => ['id' => ''],
          'disabled' => FALSE,
        ],
      ],
    ])->save();
  }

}
