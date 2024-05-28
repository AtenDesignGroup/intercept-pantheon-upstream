<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi_resources\Kernel;

use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi_resources\Unstable\Value\NewResourceObject;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests DocumentExtractor.
 *
 * @coversDefaultClass \Drupal\jsonapi_resources\Unstable\DocumentExtractor
 * @group jsonapi_resources
 */
final class DocumentExtractorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'serialization',
    'jsonapi',
    'jsonapi_resources',
  ];

  /**
   * Tests decoding the document.
   *
   * @covers ::getDocument
   */
  public function testGetDocument(): void {
    $sut = $this->container->get('jsonapi_resources.document_extractor');

    $content = json_encode([
      'data' => [
        'type' => 'node--reminder',
        'attributes' => [
          'title' => "Don't panic.",
        ],
      ],
      'meta' => [
        'foo' => 'bar',
      ],
    ], JSON_THROW_ON_ERROR);
    $request = Request::create('/', 'POST', [], [], [], [], $content);
    $request->headers->set('Accept', 'application/vnd.api+json');
    $request->headers->set('Content-Type', 'application/vnd.api+json');
    $request->attributes->set('resource_types', [
      new ResourceType('node', 'reminder', Node::class),
    ]);
    $document = $sut->getDocument($request);
    $resource_object = $document->getData()->toArray()[0];
    self::assertInstanceOf(NewResourceObject::class, $resource_object);
    self::assertEquals(['title' => "Don't panic."], $resource_object->getFields());
    self::assertEquals(['foo' => 'bar'], $document->getMeta());
  }

}
