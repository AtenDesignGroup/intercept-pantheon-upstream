<?php

namespace Drupal\Tests\existing_values_autocomplete_widget\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\existing_values_autocomplete_widget\TestContentTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the field widget settings.
 *
 * @group existing_values_autocomplete_widget
 */
class ExistingValuesAutocompleteWidgetSettingsTest extends BrowserTestBase {

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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createArticleTypeWithField();
    $this->createArticle('abc');
    $this->createArticle('another value');

    $this->user = $this->drupalCreateUser();
    $this->drupalLogin($this->user);
  }

  /**
   * Tests the field widget settings.
   */
  public function testWidgetSettings(): void {
    // Test that the default settings are present:
    $formDisplay = \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'article');
    $suggestionsCount = $formDisplay->getComponent('field_text')['settings']['suggestions_count'];
    $this->assertEquals(15, $suggestionsCount);
    // Change the settings and check that they were saved correctly:
    $formDisplay->setComponent('field_text', [
      'type' => 'existing_autocomplete_field_widget',
      'settings' => [
        'suggestions_count' => 1,
      ],
    ])->save();
    $suggestionsCount = $formDisplay->getComponent('field_text')['settings']['suggestions_count'];
    $this->assertEquals(1, $suggestionsCount);
    // Test the changes in the controller response:
    $this->drupalGet('/existing-values/autocomplete/node/article/field_text', [
      'query' => ['q' => 'a'],
    ]);
    $session = $this->assertSession();
    $session->statusCodeEquals(Response::HTTP_OK);
    $session->responseContains('abc');
    $session->responseNotContains('another value');
  }

}
