<?php

namespace Drupal\Tests\duration_field\Unit\Service;

use DateInterval;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\duration_field\Service\DurationService;

/**
 * @coversDefaultClass \Drupal\duration_field\Service\DurationService
 * @group duration_field
 */
class DurationServiceTest extends UnitTestCase {

  /**
   * @covers ::checkDurationInvalid
   * @dataProvider checkDurationInvalidDataProvider
   */
  public function testCheckDurationInvalid($pattern, $expectedResponse, $message) {
    $duration_service = new DurationService();
    $duration_service->setStringTranslation($this->getStringTranslationStub());
    if ($expectedResponse) {
      $this->expectException('Drupal\duration_field\Exception\InvalidDurationException');
      $duration_service->checkDurationInvalid($pattern);
    }
    else {
      $response = $duration_service->checkDurationInvalid($pattern);
      $this->assertTrue((bool) $response == $expectedResponse, $message);
    }
  }

  /**
   * Data provider for testCheckDurationInvalid().
   */
  public function checkDurationInvalidDataProvider() {
    return [
      ['PY1D', TRUE, 'PY1D correctly tested as invalid'],
      ['P1Y2M3DT4H', FALSE, 'P1Y2M3DT4H correctly tested as valid'],
    ];
  }

  /**
   * @covers ::convertDateArrayToDurationString
   * @dataProvider convertDateArrayToDurationStringDataProvider
   */
  public function testconvertDateArrayToDurationString($input, $expectedResponse, $message) {
    $duration_service = new DurationService();
    $response = $duration_service->convertDateArrayToDurationString($input);
    $this->assertSame($response, $expectedResponse, $message);
  }

  /**
   * Data provider for testconvertDateArrayToDurationString().
   */
  public function convertDateArrayToDurationStringDataProvider() {
    return [
      [
        [
          'y' => 1,
          'm' => 2,
          'd' => 3,
          'h' => 4,
          'i' => 5,
          's' => 6,
        ],
        'P1Y2M3DT4H5M6S',
        'P1Y2M3DT4H5M6S was correctly validated',
      ],
      [
        [
          'y' => 1,
          'm' => 2,
          'd' => 3,
        ],
        'P1Y2M3D', '
        P1Y2M3D was correctly validated',
      ],
      [
        [
          'h' => 4,
          'i' => 5,
          's' => 6,
        ],
        'PT4H5M6S',
        'PT4H5M6S was correctly validated',
      ],
      [['y' => 1, 'h' => 4], 'P1YT4H', 'P1YT4H was correctly validated'],
      [[], 'P0M', 'empty string was correctly validated'],
    ];
  }

  /**
   * @covers ::getDurationStringFromDateInterval
   * @dataProvider getDurationStringFromDateIntervalDataProvider
   */
  public function testgetDurationStringFromDateInterval($input, $expectedResponse, $message) {
    $duration_service = new DurationService();
    $response = $duration_service->getDurationStringFromDateInterval($input);
    $this->assertSame($response, $expectedResponse, $message);
  }

  /**
   * Data provider for testgetDurationStringFromDateInterval().
   */
  public function getDurationStringFromDateIntervalDataProvider() {
    return [
      [
        new DateInterval('P0M'),
        'P0M',
        'P0M correctly retrieved as duration string from DateInterval',
      ],
      [
        new DateInterval('P1Y2M3DT4H5M6S'),
        'P1Y2M3DT4H5M6S',
        'P1Y2M3DT4H5M6S correctly retrieved as duration string from DateInterval',
      ],
    ];
  }

  /**
   * @covers ::convertDateArrayToDateInterval
   * @dataProvider convertDateArrayToDateIntervalDataProvider
   */
  public function testconvertDateArrayToDateInterval($input, $expectedResponse, $message) {
    $duration_service = new DurationService();
    $response = $duration_service->convertDateArrayToDateInterval($input);
    $this->assertEquals($response, $expectedResponse, $message);
    $this->assertSame($duration_service->getDurationStringFromDateInterval($response), $duration_service->getDurationStringFromDateInterval($expectedResponse), 'Converted date array contains correct value');
  }

  /**
   * Data provider for testgetDurationStringFromDateInterval().
   */
  public function convertDateArrayToDateIntervalDataProvider() {
    return [
      [
        [
          'y' => 1,
          'm' => 2,
          'd' => 3,
          'h' => 4,
          'i' => 5,
          's' => 6,
        ],
        new DateInterval('P1Y2M3DT4H5M6S'),
        'P1Y2M3DT4H5M6S was correctly converted to a DateInterval',
      ],
      [
        [
          'y' => 0,
          'm' => 0,
          'd' => 0,
          'h' => 0,
          'i' => 0,
          's' => 0,
        ],
        new DateInterval('P0M'),
        'P0M was correctly converted to a DateInterval',
      ],
    ];
  }

  /**
   * @covers ::createEmptyDateInterval
   */
  public function testcreateEmptyDateInterval() {
    $duration_service = new DurationService();
    $response = $duration_service->createEmptyDateInterval();
    $expected_response = new DateInterval('P0M');
    $this->assertEquals($response, $expected_response, 'An empty date interval was successfully created');
  }

  /**
   * @covers ::getHumanReadableStringFromDateInterval
   * @dataProvider getHumanReadableStringFromDateIntervalDataProvider
   */
  public function testgetHumanReadableStringFromDateInterval($input, $expectedResponse, $message) {
    $duration_service = new DurationService();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
    $response = $duration_service->getHumanReadableStringFromDateInterval($input['value'], $input['granularity']);
    if (is_a($response, 'Drupal\Core\StringTranslation\TranslatableMarkup')) {
      $response = $response->__toString();
    }
    $this->assertSame($response, $expectedResponse, $message);
  }

  /**
   * Data provider for testgetHumanReadableStringFromDateInterval().
   */
  public function getHumanReadableStringFromDateIntervalDataProvider() {
    return [
      [
        [
          'value' => new DateInterval('P0M'),
          'granularity' => [
            'y' => TRUE,
            'm' => TRUE,
            'd' => TRUE,
            'h' => TRUE,
            'i' => TRUE,
            's' => TRUE,
          ],
        ],
        'Empty',
        'An empty DateInterval correctly outputted a response of "Empty"',
      ],
      [
        [
          'value' => new DateInterval('P1Y'),
          'granularity' => [
            'y' => TRUE,
            'm' => TRUE,
            'd' => TRUE,
            'h' => TRUE,
            'i' => TRUE,
            's' => TRUE,
          ],
        ],
        '1 year',
        'A date interval of P1Y correctly turned into text "1 year"',
      ],
      [
        [
          'value' => new DateInterval('P1Y1M'),
          'granularity' => [
            'y' => FALSE,
            'm' => TRUE,
            'd' => TRUE,
            'h' => TRUE,
            'i' => TRUE,
            's' => TRUE,
          ],
        ],
        '1 month',
        'A date interval of P1Y1M correctly turned into text "1 month", excluding the year as it is not part of the granularity',
      ],
      [
        [
          'value' => new DateInterval('P1Y2M3DT4H5M6S'),
          'granularity' => [
            'y' => TRUE,
            'm' => TRUE,
            'd' => TRUE,
            'h' => TRUE,
            'i' => TRUE,
            's' => TRUE,
          ],
        ],
        '1 year 2 months 3 days 4 hours 5 minutes 6 seconds',
        'A date interval of P1Y2M3DT4H5M6S correctly turned into text "1 year 2 months 3 days 4 hours 5 minutes 6 seconds"',
      ],
    ];
  }

  /**
   * @covers ::getSecondsFromDateInterval
   * @dataProvider getSecondsFromDateIntervalDataProvider
   */
  public function testgetSecondsFromDateInterval($input, $expectedResponse, $message) {
    $duration_service = new DurationService();

    $response = $duration_service->getSecondsFromDateInterval($input);
    $this->assertSame($response, $expectedResponse, $message);
  }

  /**
   * Data provider for testgetSecondsFromDateInterval().
   */
  public function getSecondsFromDateIntervalDataProvider() {
    return [
      [
        new DateInterval('P0M'),
        0,
        'Correctly got zero seconds for a duration of P0M',
      ],
      [
        new DateInterval('P1Y'),
        31536000,
        'Correctly got zero seconds for a duration of P0M',
      ],
      [
        new DateInterval('PT1H'),
        3600,
        'Correctly got zero seconds for a duration of P0M',
      ],
    ];
  }

}
