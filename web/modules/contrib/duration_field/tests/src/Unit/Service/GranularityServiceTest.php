<?php

namespace Drupal\Tests\duration_field\Unit\Service;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\duration_field\Service\GranularityService
 * @group duration_field
 */
class GranularityServiceTest extends UnitTestCase {

  /**
   * @covers ::convertGranularityArrayToGranularityString
   * @dataProvider convertGranularityArrayToGranularityStringDataProvider
   */
  public function testconvertGranularityArrayToGranularityString($input, $expectedResponse, $message) {
    $service = new GranularityService();
    $result = $service->convertGranularityArrayToGranularityString($input);
    $this->assertSame($result, $expectedResponse, $message);
  }

  /**
   * Data provider for testconvertGranularityArrayToGranularityString().
   */
  public function convertGranularityArrayToGranularityStringDataProvider() {
    return [
      [
        [
          'y' => TRUE,
          'm' => TRUE,
          'd' => TRUE,
          'h' => TRUE,
          'i' => TRUE,
          's' => TRUE,
        ],
        'y:m:d:h:i:s',
        'A full granularity array was converted to a granularity string',
      ],
      [
        [
          'y' => TRUE,
          'm' => FALSE,
          'd' => TRUE,
          'h' => FALSE,
          'i' => TRUE,
          's' => FALSE,
        ],
        'y:d:i',
        'A granularity array was correctly converted to the granularity string y:d:i',
      ],
      [
        [
          'y' => FALSE,
          'm' => FALSE,
          'd' => FALSE,
          'h' => FALSE,
          'i' => FALSE,
          's' => FALSE,
        ],
        '',
        'An empty granularity array was correctly converted to an empty granularity string',
      ],
    ];
  }

  /**
   * @covers ::convertGranularityStringToGranularityArray
   * @dataProvider convertGranularityStringToGranularityArrayDataProvider
   */
  public function testconvertGranularityStringToGranularityArray($input, $expectedResponse, $message) {
    $service = new GranularityService();
    $result = $service->convertGranularityStringToGranularityArray($input);
    $this->assertSame($result, $expectedResponse, $message);
  }

  /**
   * Data provider for testconvertGranularityStringToGranularityArray().
   */
  public function convertGranularityStringToGranularityArrayDataProvider() {
    return [
      [
        'y:m:d:h:i:s',
        [
          'y' => TRUE,
          'm' => TRUE,
          'd' => TRUE,
          'h' => TRUE,
          'i' => TRUE,
          's' => TRUE,
        ],
        'A full granularity string was converted to a full granularity array',
      ],
      [
        'y:d:i',
        [
          'y' => TRUE,
          'm' => FALSE,
          'd' => TRUE,
          'h' => FALSE,
          'i' => TRUE,
          's' => FALSE,
        ],
        'A partial granularity string y:d:i was correctly converted to a granularity array',
      ],
      [
        '',
        [
          'y' => FALSE,
          'm' => FALSE,
          'd' => FALSE,
          'h' => FALSE,
          'i' => FALSE,
          's' => FALSE,
        ],
        'An empty granularity string was correctly converted to an empty granularity array',
      ],
    ];
  }

  /**
   * @covers ::includeGranularityElement
   * @dataProvider includeGranularityElementDataProvider
   */
  public function testincludeGranularityElement($input, $expectedResponse, $message) {
    $service = new GranularityService();
    $result = $service->includeGranularityElement($input['granularity_element'], $input['granularity_string']);
    $this->assertSame($result, $expectedResponse, $message);
  }

  /**
   * Data provider for testincludeGranularityElement().
   */
  public function includeGranularityElementDataProvider() {
    return [
      [
        [
          'granularity_element' => 'y',
          'granularity_string' => 'y:m:d:h:i:s',
        ],
        TRUE,
        'y correctly returned TRUE when it is supposed to be included',
      ],
      [
        [
          'granularity_element' => 'y',
          'granularity_string' => 'm:d:h:i:s',
        ],
        FALSE,
        'y correctly returned FALSE when it is not supposed to be included',
      ],
      [
        [
          'granularity_element' => 'm',
          'granularity_string' => 'y:m:d:h:i:s',
        ],
        TRUE,
        'm correctly returned TRUE when it is supposed to be included',
      ],
      [
        [
          'granularity_element' => 'm',
          'granularity_string' => 'y:d:h:i:s',
        ],
        FALSE,
        'm correctly returned FALSE when it is not supposed to be included',
      ],
      [
        [
          'granularity_element' => 'd',
          'granularity_string' => 'y:m:d:h:i:s',
        ],
        TRUE,
        'd correctly returned TRUE when it is supposed to be included',
      ],
      [
        [
          'granularity_element' => 'd',
          'granularity_string' => 'y:m:h:i:s',
        ],
        FALSE,
        'y correctly returned FALSE when it is not supposed to be included',
      ],
      [
        [
          'granularity_element' => 'h',
          'granularity_string' => 'y:m:d:h:i:s',
        ],
        TRUE,
        'h correctly returned TRUE when it is supposed to be included',
      ],
      [
        [
          'granularity_element' => 'h',
          'granularity_string' => 'm:d:i:s',
        ],
        FALSE,
        'h correctly returned FALSE when it is not supposed to be included',
      ],
      [
        [
          'granularity_element' => 'i',
          'granularity_string' => 'y:m:d:h:i:s',
        ],
        TRUE,
        'i correctly returned TRUE when it is supposed to be included',
      ],
      [
        [
          'granularity_element' => 'i',
          'granularity_string' => 'm:d:h:s',
        ],
        FALSE,
        'y correctly returned FALSE when it is not supposed to be included',
      ],
      [
        [
          'granularity_element' => 's',
          'granularity_string' => 'y:m:d:h:i:s',
        ],
        TRUE,
        's correctly returned TRUE when it is supposed to be included',
      ],
      [
        [
          'granularity_element' => 'y',
          'granularity_string' => 'm:d:h:i',
        ],
        FALSE,
        'y correctly returned FALSE when it is not supposed to be included',
      ],
    ];
  }

}
