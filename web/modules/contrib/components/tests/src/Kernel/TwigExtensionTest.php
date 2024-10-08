<?php

namespace Drupal\Tests\components\Kernel;

/**
 * @coversDefaultClass \Drupal\components\Template\TwigExtension
 * @group components
 */
class TwigExtensionTest extends ComponentsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'components',
    'components_twig_extension_test',
  ];

  /**
   * Ensures the Twig template() function works inside a Drupal instance.
   *
   * @covers ::template
   *
   * @throws \Exception
   */
  public function testTemplateFunction() {
    try {
      $element = [
        '#theme' => 'components_twig_extension_test_template_function',
        '#items' => [
          'first item',
          'second item',
        ],
      ];
      $result = $this->render($element);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected; "' . $e->getMessage() . '" thrown during: ' . $this->getName());
    }
    $this->assertStringContainsString('<ul><li>first item</li><li>second item</li></ul>', $result);
  }

  /**
   * Ensures the Twig "recursive_merge" filter works inside a Drupal instance.
   *
   * @covers ::recursiveMergeFilter
   *
   * @dataProvider providerTestRecursiveMergeFilter
   */
  public function testRecursiveMergeFilter(string $theme_hook, string $expected) {
    try {
      $element = [
        '#theme' => $theme_hook,
        'list' => [
          '#theme' => 'item_list',
          '#items' => [
            [
              '#type' => 'container',
              '#attributes' => [
                'id' => 'the_element_id',
                'class' => ['original-container-class'],
              ],
            ],
          ],
        ],
      ];
      $result = $this->render($element);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected; "' . $e->getMessage() . '" thrown during: ' . $this->getName());
    }
    $this->assertStringContainsString($expected, $result);
  }

  /**
   * Data provider for testRecursiveMergeFilter().
   *
   * @see testRecursiveMergeFilter()
   */
  public static function providerTestRecursiveMergeFilter(): array {
    return [
      'Uses positional arguments' => [
        'theme_hook' => 'components_twig_extension_test_recursive_merge_filter',
        'expected' => '<div id="the_element_id" class="new-class"></div>',
      ],
      'Uses named arguments' => [
        'theme_hook' => 'components_twig_extension_test_recursive_merge_filter_named_arguments',
        'expected' => '<div id="the_element_id" class="new-class"></div>',
      ],
    ];
  }

  /**
   * Ensures the Twig "set" filter works inside a Drupal instance.
   *
   * @covers ::setFilter
   *
   * @dataProvider providerTestSetFilter
   */
  public function testSetFilter(string $theme_hook, string $expected) {
    try {
      $element = [
        '#theme' => $theme_hook,
        'list' => [
          '#theme' => 'item_list',
          '#items' => [
            [
              '#type' => 'container',
              '#attributes' => [
                'id' => 'the_element_id',
                'class' => ['original-container-class'],
              ],
            ],
          ],
        ],
      ];
      $result = $this->render($element);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected; "' . $e->getMessage() . '" thrown during: ' . $this->getName());
    }
    $this->assertStringContainsString($expected, $result);
  }

  /**
   * Data provider for testSetFilter().
   *
   * @see testSetFilter()
   */
  public static function providerTestSetFilter(): array {
    return [
      'Uses positional arguments' => [
        'theme_hook' => 'components_twig_extension_test_set_filter',
        'expected' => '<div class="new-class"></div>',
      ],
      'Uses named arguments' => [
        'theme_hook' => 'components_twig_extension_test_set_filter_named_arguments',
        'expected' => '<div class="new-class"></div>',
      ],
    ];
  }

  /**
   * Ensures the Twig "add" filter works inside a Drupal instance.
   *
   * @covers ::addFilter
   *
   * @dataProvider providerTestAddFilter
   */
  public function testAddFilter(string $theme_hook, string $expected) {
    try {
      $element = [
        '#theme' => $theme_hook,
        'list' => [
          '#theme' => 'item_list',
          '#items' => [
            [
              '#type' => 'container',
              '#attributes' => [
                'class' => ['original-container-class'],
              ],
            ],
          ],
        ],
      ];
      $result = $this->render($element);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected; "' . $e->getMessage() . '" thrown during: ' . $this->getName());
    }
    $this->assertStringContainsString($expected, $result);
  }

  /**
   * Data provider for testAddFilter().
   *
   * @see testAddFilter()
   */
  public static function providerTestAddFilter(): array {
    return [
      'Uses positional arguments' => [
        'theme_hook' => 'components_twig_extension_test_add_filter',
        'expected' => '<div class="original-container-class new-class"></div>',
      ],
      'Uses named arguments' => [
        'theme_hook' => 'components_twig_extension_test_add_filter_named_arguments',
        'expected' => '<div class="original-container-class new-class"></div>',
      ],
      'Uses "values" named argument' => [
        'theme_hook' => 'components_twig_extension_test_add_filter_plural_named_arguments',
        'expected' => '<div class="original-container-class new-class-1 new-class-2"></div>',
      ],
    ];
  }

}
