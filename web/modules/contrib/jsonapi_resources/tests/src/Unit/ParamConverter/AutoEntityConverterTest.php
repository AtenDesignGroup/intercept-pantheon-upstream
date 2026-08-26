<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi_resources\Unit\ParamConverter;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\jsonapi_resources\ParamConverter\AutoEntityConverter;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\jsonapi_resources\ParamConverter\AutoEntityConverter
 * @group jsonapi_resources
 */
final class AutoEntityConverterTest extends UnitTestCase {

  private const UUID = '00112233-4455-6677-8899-aabbccddeeff';

  /**
   * @covers ::convert
   */
  public function testUuidValueResolvesToIdBeforeUpcasting(): void {
    $definition = ['type' => 'entity:user'];
    $expected_entity = new \stdClass();

    $entity_repository = $this->createMock(EntityRepositoryInterface::class);
    $entity_repository->expects($this->once())
      ->method('getCanonical')
      ->with('user', '42', ['operation' => 'entity_upcast'])
      ->willReturn($expected_entity);

    $converter = new AutoEntityConverter(
      $this->createEntityTypeManager(['42']),
      $entity_repository,
    );

    $this->assertSame($expected_entity, $converter->convert(self::UUID, $definition, 'user', []));
  }

  /**
   * @covers ::convert
   */
  public function testUnknownUuidReturnsNull(): void {
    $entity_repository = $this->createMock(EntityRepositoryInterface::class);
    $entity_repository->expects($this->never())->method('getCanonical');

    $converter = new AutoEntityConverter(
      $this->createEntityTypeManager([]),
      $entity_repository,
    );

    $this->assertNull($converter->convert(self::UUID, ['type' => 'entity:user'], 'user', []));
  }

  /**
   * @covers ::convert
   */
  public function testNumericValueDelegatesToParent(): void {
    $expected_entity = new \stdClass();
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->never())->method('getStorage');
    $entity_repository = $this->createMock(EntityRepositoryInterface::class);
    $entity_repository->expects($this->once())
      ->method('getCanonical')
      ->with('user', '42', ['operation' => 'entity_upcast'])
      ->willReturn($expected_entity);

    $converter = new AutoEntityConverter($entity_type_manager, $entity_repository);

    $this->assertSame($expected_entity, $converter->convert('42', ['type' => 'entity:user'], 'user', []));
  }

  /**
   * @covers ::applies
   */
  public function testNeverAppliesAutomatically(): void {
    $converter = new AutoEntityConverter(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(EntityRepositoryInterface::class),
    );

    // The converter is wired exclusively via an explicit `converter:` option,
    // so it must never auto-apply to entity parameters on other routes.
    $this->assertFalse($converter->applies(['type' => 'entity:user'], 'user', new Route('/example/{user}')));
  }

  /**
   * Builds an entity type manager whose UUID query returns the given IDs.
   */
  private function createEntityTypeManager(array $query_result): EntityTypeManagerInterface {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->expects($this->once())->method('condition')->with('uuid', self::UUID)->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn($query_result);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('getKey')->with('uuid')->willReturn('uuid');

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')->with('user')->willReturn($entity_type);
    $entity_type_manager->method('getStorage')->with('user')->willReturn($storage);
    return $entity_type_manager;
  }

}
