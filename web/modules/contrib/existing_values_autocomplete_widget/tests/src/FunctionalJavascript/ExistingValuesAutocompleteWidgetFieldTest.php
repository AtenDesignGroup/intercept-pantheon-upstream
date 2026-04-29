<?php

namespace Drupal\Tests\existing_values_autocomplete_widget\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\existing_values_autocomplete_widget\TestContentTrait;

/**
 * Tests the autocomplete field widget functionality.
 *
 * @group existing_values_autocomplete_widget
 */
class ExistingValuesAutocompleteWidgetFieldTest extends WebDriverTestBase {

  use TestContentTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'existing_values_autocomplete_widget',
    'node',
    'field',
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
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createTestContent();

    $this->user = $this->drupalCreateUser([
      'create article content',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests the field widget autocomplete functionality.
   */
  public function testAutocompleteFieldWidget(): void {
    // $this->drupalGet('/node/add/article');
    // $page = $this->getSession()->getPage();
    // $page->fillField('field_text[0][value]', 'a');
    // $session = $this->assertSession();
    // $session->waitForElement('css', '.ui-autocomplete');
    // $session->elementContains('css', '.ui-autocomplete', 'abc');
    // $session->elementContains('css', '.ui-autocomplete', 'another value');
    // $page->fillField('field_text[0][value]', 'ab');
    // $session->elementContains('css', '.ui-autocomplete', 'abc');
    // $session->elementNotContains('css', '.ui-autocomplete', 'another value');
    // $page->fillField('field_text[0][value]', 'an');
    // $session->elementNotContains('css', '.ui-autocomplete', 'abc');
    // $session->elementContains('css', '.ui-autocomplete', 'another value');
  }

}
