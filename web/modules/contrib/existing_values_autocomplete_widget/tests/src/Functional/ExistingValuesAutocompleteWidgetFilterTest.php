<?php

namespace Drupal\Tests\existing_values_autocomplete_widget\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\existing_values_autocomplete_widget\TestContentTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the filtering of duplicate results in the autocomplete controller.
 *
 * @group existing_values_autocomplete_widget
 */
class ExistingValuesAutocompleteWidgetFilterTest extends BrowserTestBase {

  use TestContentTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'existing_values_autocomplete_widget',
    'node',
  ];

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A test article node used for controller responses.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testArticle;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createArticleTypeWithField();
    $this->createArticle('foo');
    $this->createArticle('foo');
    $this->createArticle('foo');
    $this->createArticle('foobar');

    $this->user = $this->drupalCreateUser();
    $this->drupalLogin($this->user);
  }

  /**
   * Tests the filtering of duplicate results in the controller.
   */
  public function testDuplicateResultFiltering(): void {
    $this->drupalGet('/existing-values/autocomplete/node/article/field_text', [
      'query' => ['q' => 'foo'],
    ]);
    $session = $this->assertSession();
    $session->statusCodeEquals(Response::HTTP_OK);
    $session->pageTextContainsOnce('"value":"foo"');
    $session->pageTextContainsOnce('"value":"foobar"');
    // Change the widget settings to only show 2 results and make sure that
    // both "foo" and "foobar" are still present:
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'article')
      ->setComponent('field_text', [
        'type' => 'existing_autocomplete_field_widget',
        'settings' => [
          'suggestions_count' => 2,
        ],
      ])
      ->save();
    $this->drupalGet('/existing-values/autocomplete/node/article/field_text', [
      'query' => ['q' => 'foo'],
    ]);
    $session->statusCodeEquals(Response::HTTP_OK);
    $session->pageTextContainsOnce('"value":"foo"');
    $session->pageTextContainsOnce('"value":"foobar"');
  }

}
