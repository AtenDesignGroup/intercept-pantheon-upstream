<?php

namespace Drupal\Tests\consumer_image_styles\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\consumer_image_styles\ImageStylesProvider;
use Drupal\consumers\Entity\Consumer;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\jsonapi_extras\Entity\JsonapiResourceConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use GuzzleHttp\RequestOptions;

/**
 * @group consumer_image_styles
 */
class ConsumerImageSylesFunctionalTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use ImageFieldCreationTrait;
  use JsonApiRequestTestTrait;

  public static $modules = [
    'consumers',
    'consumer_image_styles',
    'jsonapi',
    'jsonapi_extras',
    'serialization',
    'node',
    'image',
  ];

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The name of the image field.
   *
   * @var string
   */
  protected $imageFieldName;

  /**
   * The content type to attach the fields to test.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * @var \Drupal\node\Entity\Node[]
   */
  protected $nodes = [];

  /**
   * @var \Drupal\file\Entity\File[]
   */
  protected $files = [];

  /**
   * @var \Drupal\consumers\Entity\Consumer
   */
  protected $consumer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->contentType = $this->createContentType();
    $this->imageFieldName = $this->getRandomGenerator()->word(8);
    $this->user = $this->drupalCreateUser();
    $this->createImageField($this->imageFieldName, $this->contentType->id());
    $this->overrideResources();
    drupal_flush_all_caches();
  }

  /**
   * Creates default content to test the API.
   *
   * @param int $num_nodes
   *   Number of articles to create.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createDefaultContent($num_nodes) {
    $random = $this->getRandomGenerator();
    for ($created_nodes = 0; $created_nodes < $num_nodes; $created_nodes++) {
      $file = File::create([
        'uri' => 'public://' . $random->name() . '.png',
      ]);
      // We need to create an actual empty PNG, or the GD toolkit will not
      // consider the image valid.
      $png_resource = imagecreate(300, 300);
      imagefill($png_resource, 0, 0, imagecolorallocate($png_resource, 0, 0, 0));
      imagepng($png_resource, $file->getFileUri());
      $file->setPermanent();
      $file->save();
      $this->files[] = $file;
      $values = [
        'uid' => ['target_id' => $this->user->id()],
        'type' => $this->contentType->id(),
      ];
      $values[$this->imageFieldName] = [
        'target_id' => $file->id(),
        'alt' => 'alt text',
      ];
      $node = $this->createNode($values);
      $this->nodes[] = $node;
    }
    // Create the image styles.
    $image_styles = array_map(function ($name) {
      $image_style = ImageStyle::create(['name' => $name]);
      $image_style->save();
      return $image_style;
    }, ['foo', 'bar']);

    // Create the consumer.
    $this->consumer = Consumer::create([
      'owner_id' => '',
      'label' => $this->getRandomGenerator()->name(),
      'image_styles' => array_map(function (ImageStyle $image_style) {
        return ['target_id' => $image_style->id()];
      }, $image_styles),
    ]);
    $this->consumer->save();
  }

  /**
   * Test the GET method.
   */
  public function testRead() {
    $this->createDefaultContent(1);

    // 1. Check the request for the image directly.
    $url = Url::fromRoute('jsonapi.file--file.individual', ['entity' => $this->files[0]->uuid()]);
    $request_options = [
      RequestOptions::HEADERS => ['X-Consumer-ID' => $this->consumer->uuid()],
    ];
    $response = $this->request('GET', $url, $request_options);
    $output = Json::decode($response->getBody());
    $this->assertEquals(200, $response->getStatusCode());
    $links = $output['data']['links'];
    $derivatives = array_filter($links, function ($link) {
      $rels = isset($link['meta']['rel']) ? $link['meta']['rel'] : [];
      return !empty($rels) && in_array(ImageStylesProvider::DERIVATIVE_LINK_REL, $rels);
    });
    $this->assertNotEmpty($derivatives);
    $this->assertContains('/files/styles/foo/public/', $derivatives['foo']['href']);
    $this->assertContains('/files/styles/bar/public/', $derivatives['bar']['href']);
    $this->assertContains('itok=', $derivatives['foo']['href']);
    $this->assertContains('itok=', $derivatives['bar']['href']);

    // 2. Check the request via the node.
    $url = Url::fromRoute(
      sprintf('jsonapi.node--%s.individual', $this->contentType->id()),
      ['entity' => $this->nodes[0]->uuid()]
    );
    $request_options = [
      RequestOptions::QUERY => ['include' => $this->imageFieldName],
      RequestOptions::HEADERS => ['X-Consumer-ID' => $this->consumer->uuid()],
    ];
    $response = $this->request('GET', $url, $request_options);
    $output = Json::decode($response->getBody());
    $this->assertEquals(200, $response->getStatusCode());
    $links = $output['included'][0]['links'];
    $derivatives = array_filter($links, function ($link) {
      $rels = isset($link['meta']['rel']) ? $link['meta']['rel'] : [];
      return !empty($rels) && in_array(ImageStylesProvider::DERIVATIVE_LINK_REL, $rels);
    });
    $this->assertContains(file_create_url('public://styles/foo/public/'), $derivatives['foo']['href']);
    $this->assertContains(file_create_url('public://styles/bar/public/'), $derivatives['bar']['href']);
    $this->assertContains('itok=', $derivatives['foo']['href']);
    $this->assertContains('itok=', $derivatives['bar']['href']);

    // 3. Check the request for the image directly without consumer.
    $url = Url::fromRoute('jsonapi.file--file.individual', ['entity' => $this->files[0]->uuid()]);
    $response = $this->request('GET', $url, []);
    $output = Json::decode($response->getBody());
    $this->assertEquals(200, $response->getStatusCode());
    $links = $output['data']['links'];
    $derivatives = array_filter($links, function ($link) {
      $rels = isset($link['meta']['rel']) ? $link['meta']['rel'] : [];
      return !empty($rels) && in_array(ImageStylesProvider::DERIVATIVE_LINK_REL, $rels);
    });
    $this->assertEmpty(empty($derivatives));

    // 4. Apply the field enhancer and check the image field.
    $url = Url::fromRoute(
      sprintf('jsonapi.node--%s.individual', $this->contentType->id()),
      ['entity' => $this->nodes[0]->uuid()]
    );
    $request_options = [
      RequestOptions::HEADERS => ['X-Consumer-ID' => $this->consumer->uuid()],
    ];
    $response = $this->request('GET', $url, $request_options);
    $output = Json::decode($response->getBody());
    $this->assertEquals(200, $response->getStatusCode());
    $links = NestedArray::getValue($output, [
      'data',
      'relationships',
      $this->imageFieldName,
      'data',
      'meta',
      'imageDerivatives',
      'links',
    ]);
    $derivatives = array_filter($links, function ($link) {
      $rels = isset($link['meta']['rel']) ? $link['meta']['rel'] : [];
      return !empty($rels) && in_array(ImageStylesProvider::DERIVATIVE_LINK_REL, $rels);
    });
    $this->assertContains(
      file_create_url('public://styles/foo/public/'),
      $derivatives['foo']['href']
    );
    $this->assertContains(
      ImageStylesProvider::DERIVATIVE_LINK_REL,
      $derivatives['foo']['meta']['rel']
    );
    $this->assertTrue(empty($derivatives['bar']));
    $this->assertContains('itok=', $derivatives['foo']['href']);
  }

  /**
   * Creates the JSON API Resource Config entities to override the resources.
   */
  protected function overrideResources() {
    // Override paths and fields in the articles resource.
    $content_type = $this->contentType->id();
    JsonapiResourceConfig::create([
      'id' => 'node--' . $content_type,
      'disabled' => FALSE,
      'path' => 'node/' . $content_type,
      'resourceType' => 'node--' . $content_type,
      'resourceFields' => [
        $this->imageFieldName => [
          'fieldName' => $this->imageFieldName,
          'publicName' => $this->imageFieldName,
          'enhancer' => [
            'id' => 'image_styles',
            'settings' => [
              'styles' => [
                'refine' => TRUE,
                'custom_selection' => ['foo'],
              ],
            ],
          ],
          'disabled' => FALSE,
        ],
      ],
    ])->save();
  }

}
