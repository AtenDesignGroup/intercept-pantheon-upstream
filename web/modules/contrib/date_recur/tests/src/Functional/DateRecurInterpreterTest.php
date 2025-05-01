<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur\Functional;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests interfaces for interpreters.
 *
 * @group date_recur
 * @method \Drupal\FunctionalJavascriptTests\JSWebAssert assertSession($name = NULL)
 */
final class DateRecurInterpreterTest extends WebDriverTestBase {

  protected $defaultTheme = 'starterkit_theme';

  protected static $modules = [
    'date_recur_interpreter_test',
    'date_recur',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'date_recur manage interpreters',
    ]));
  }

  /**
   * Tests adding a new interpreter.
   */
  public function testInterpreterWebCreate(): void {
    $instanceLabel = 'Kaya';
    $url = Url::fromRoute('entity.date_recur_interpreter.add_form');
    $this->drupalGet($url);
    $this->assertSession()->buttonExists('Next');
    $this->assertSession()->pageTextContains('Add interpreter');
    $this->assertSession()->optionExists('plugin_type', 'test_interpreter');
    $page = $this->getSession()->getPage();
    $page->findField('label')->setValue($instanceLabel);
    $this->assertSession()->waitForElementVisible('css', '[name="label"] + * .machine-name-value');
    $page->findField('plugin_type')->setValue('test_interpreter');
    $this->submitForm([], 'Next', 'date-recur-interpreter-add-form');

    // Page should have reloaded, a different submit button visible.
    $this->assertSession()->buttonNotExists('Next');
    $this->assertSession()->pageTextContains('Add interpreter');
    $this->assertSession()->checkboxNotChecked('configure[show_foo]');
    $page = $this->getSession()->getPage();
    $page->checkField('configure[show_foo]');
    $this->submitForm([], 'Save', 'date-recur-interpreter-add-form');

    // Page reloaded to interpreter collection page, message displayed.
    $this->assertSession()->addressEquals(Url::fromRoute('entity.date_recur_interpreter.collection')->setAbsolute()->toString());
    $this->assertSession()->elementTextContains('css', '.messages', 'Saved the ' . $instanceLabel . ' interpreter.');
  }

}
