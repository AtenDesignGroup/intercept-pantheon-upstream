<?php

namespace Drupal\Tests\duration_field\Unit\Normalizer;

use Drupal\Core\TypedData\Plugin\DataType\IntegerData;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\duration_field\Normalizer\DateIntervalDataNormalizer;
use Drupal\duration_field\Plugin\DataType\DateIntervalData;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test coverage for the "php_date_interval" @DataType.
 *
 * @coversDefaultClass \Drupal\duration_field\Normalizer\DateIntervalDataNormalizer
 * @group duration_field
 *
 * @see \Drupal\duration_field\Plugin\DataType\DateIntervalData
 */
class DurationIntervalNormalizerTest extends UnitTestCase {

  /**
   * The tested data type's normalizer.
   *
   * @var \Drupal\duration_field\Normalizer\DateIntervalDataNormalizer
   */
  protected $normalizer;

  /**
   * The DateIntervalData object prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $data;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->normalizer = new DateIntervalDataNormalizer();
    $this->data = $this->prophesize(DateIntervalData::class);
  }

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $this->assertTrue($this->normalizer->supportsNormalization($this->data->reveal()));

    $datetime = $this->prophesize(DateTimeInterface::class);
    $this->assertFalse($this->normalizer->supportsNormalization($datetime->reveal()));

    $integer = $this->prophesize(IntegerData::class);
    $this->assertFalse($this->normalizer->supportsNormalization($integer->reveal()));

    $string = $this->prophesize(StringData::class);
    $this->assertFalse($this->normalizer->supportsNormalization($string->reveal()));
  }

  /**
   * @covers ::supportsDenormalization
   */
  public function testSupportsDenormalization() {
    $this->assertTrue($this->normalizer->supportsDenormalization($this->data->reveal(), DateIntervalData::class));
  }

  /**
   * @covers ::normalize
   * @dataProvider normalizeDataProvider
   */
  public function testNormalize($interval_string) {
    $this->data->getString()
      ->willReturn($interval_string);

    $normalized = $this->normalizer->normalize($this->data->reveal());
    $this->assertSame($interval_string, $normalized);
  }

  /**
   * Data provider for testNormalize.
   *
   * @return array
   *   An array of test data.
   */
  public static function normalizeDataProvider() {
    return [
      ['P1Y2M3DT4H5M6S'],
      ['P1Y2M3DT4H5M'],
      ['P1Y2M3DT4H'],
      ['P1Y2M3D'],
      ['P1Y2M'],
      ['P1Y'],
      ['PT4H5M6S'],
      ['PT4H5M'],
      ['PT4H'],
      ['PT5M6S'],
      ['PT5M'],
      ['PT6S'],
    ];
  }

}
