<?php

namespace Drupal\jsonapi_resources_test\Entity\Query;

use Drupal\jsonapi_resources\Entity\Query\NonEntityQueryBase;
use Drupal\jsonapi_resources\Entity\Query\PaginatorMetadata;

/**
 * A sample implementation of a "non-entity" query adapter for JSON:API.
 *
 * This "query" returns mock color scheme information, and takes the place of a
 * real query object. In an actual system, the color schemes that this query
 * returns would not be hard-coded and would instead be sourced from somewhere,
 * such as a config file or an external theme customization service.
 */
class ColorSchemeQuery extends NonEntityQueryBase {

  /**
   * Gets the hard-coded color scheme data in this mock query object.
   *
   * @return array
   *   A two-dimensional associative array of color schemes, where the value at
   *   the top level is an array of data about a particular color scheme and the
   *   key is the UUID of the scheme.
   */
  protected static function getColorSchemes(): array {
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
    return [
      'f4f6004c-feac-4fb0-a8a6-3c7d0937b9bf' => [
        'name'             => \t('Blue Lagoon'),
        'background_color' => NULL,
        'text_color'       => [\t('Mine Shaft'),    '3b3b3b'],
        'link_color'       => [\t('Deep Cerulean'), '0071b3'],
        'accent_colors'    => [
          [\t('Venice Blue'),  '055a8e'],
          [\t('Curious Blue'), '1d84c3'],
          [\t('Cararra'),      'f6f6f2'],
          [\t('Alabaster'),    'f9f9f9'],
        ],
      ],

      'b9dfcef1-583f-403f-b0f6-c083bbdb9287' => [
        'name'             => \t('Plum'),
        'background_color' => [\t('Quarter Pearl Lusta'), 'fffdf7'],
        'text_color'       => [\t('Tamarind'),            '301313'],
        'link_color'       => [\t('Rouge'),               '9d408d'],
        'accent_colors'    => [
          [\t('Grape'),               '4c1c58'],
          [\t('Voodoo'),              '593662'],
          [\t('Cararra'),             'edede7'],
          [\t('Mercury'),             'e7e7e7'],
          [\t('Tuatara'),             '2c2c28'],
          [\t('Quarter Pearl Lusta'), 'fffdf7'],
        ],
      ],

      '5ccdea06-7f5a-4636-94e9-b0eae097121a' => [
        'name'             => \t('Black and White'),
        'background_color' => [\t('White'),         'ffffff'],
        'text_color'       => [\t('Black'),         '000000'],
        'link_color'       => [\t('Deep Cerulean'), '0071b3'],
        'accent_colors'    => [],
      ],
    ];
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces
  }

  /**
   * Constructs a new instance.
   */
  public function __construct() {
    parent::__construct('color_scheme');
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchCountOfResults(): int {
    return count($this->fetchResults(NULL));
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchResults(
    ?PaginatorMetadata $paginator_metadata,
  ): array {
    $color_schemes = static::getColorSchemes();
    $target_uuids  = $this->extractTargetUuids();

    if (empty($target_uuids)) {
      // Load all.
      $target_schemes = $color_schemes;
    }
    else {
      $target_schemes = array_filter(
        $color_schemes,
        function (string $uuid) use ($target_uuids): bool {
          return in_array($uuid, $target_uuids);
        },
        ARRAY_FILTER_USE_KEY
      );
    }

    $range = $this->getRange();

    $start_offset = $range['start'] ?? 0;
    $limit        = $range['length'] ?? 50;

    return array_slice($target_schemes, $start_offset, $limit);
  }

  /**
   * Extracts color scheme UUIDs from query conditions and returns them.
   *
   * At present, only the following types of queries are supported:
   *   - Queries with no filters at all (returns all color schemes).
   *   - Queries with one equals (=) filter on the "uuid" field.
   *   - Queries with one contains (IN) filter on the "uuid" field.
   *
   * The following are not (yet) supported:
   *   - Queries with filters on fields other than "uuid".
   *   - Queries with multiple filters on the "uuid" field (e.g., "'uuid' equals
   *     A OR B but NOT C").
   *
   * @return string[]
   *   The UUIDs of each file being requested.
   *
   * @throws \InvalidArgumentException
   *   If the filtering operation on the UUID is anything other than equality
   *   (=) or set inclusion (IN).
   */
  protected function extractTargetUuids(): array {
    $target_uuids = [];
    $condition    = $this->getCondition();

    $handlers = [
      'uuid' => [$this, 'extractTargetUuidsFromUuidCondition'],
    ];

    foreach ($condition->conditions() as $field_condition) {
      $field    = $field_condition['field'] ?? NULL;
      $value    = $field_condition['value'] ?? NULL;
      $operator = $field_condition['operator'] ?? '=';

      $handler = $handlers[$field] ?? NULL;

      if ($handler === NULL) {
        throw new \InvalidArgumentException(
          sprintf(
            'Only filters on the fields [%s] are currently supported.',
            implode(', ', array_keys($handlers))
          )
        );
      }

      $new_uuids = $handler($value, $operator);

      if (!empty($new_uuids) && !empty($target_uuids)) {
        throw new \InvalidArgumentException(
          'Only simple queries (no boolean logic) are currently supported.'
        );
      }

      $target_uuids = $new_uuids;
    }

    return $target_uuids;
  }

  /**
   * Extracts the UUIDs that the given condition is targeting.
   *
   * @param string|array $value
   *   The value or values of the condition.
   * @param string $operator
   *   The equality operator of the condition.
   *
   * @return array
   *   One or more UUIDs that are being targeted by the condition.
   */
  protected function extractTargetUuidsFromUuidCondition(
    $value,
    string $operator,
  ): array {
    if (empty($value)) {
      throw new \InvalidArgumentException('Condition value cannot be empty.');
    }

    switch ($operator) {
      case '=':
        if (is_array($value)) {
          throw new \InvalidArgumentException('Value cannot be an array.');
        }

        $target_uuids = [$value];
        break;

      case 'IN':
        if (!is_array($value)) {
          throw new \InvalidArgumentException('Value must be an array.');
        }

        $target_uuids = $value;
        break;

      default:
        throw new \InvalidArgumentException(
          sprintf('The operator "%s" is not supported.', $operator)
        );
    }

    return $target_uuids;
  }

}
