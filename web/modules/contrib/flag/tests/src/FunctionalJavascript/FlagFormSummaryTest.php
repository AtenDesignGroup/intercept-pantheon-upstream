<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\FunctionalJavascript;

use Drupal\flag\Entity\Flag;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests flag summary on node edit forms.
 *
 * @group flag
 */
final class FlagFormSummaryTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'field',
    'text',
    'flag',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $flag = Flag::create([
      'id' => 'test_label_123',
      'label' => 'Test flag',
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_short' => 'Flag',
      'unflag_short' => 'Unflag',
      'unflag_denied_text' => 'Denied',
      'flag_long' => '',
      'unflag_long' => '',
      'flag_message' => 'Flagged',
      'unflag_message' => 'Unflagged',
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
      'flagTypeConfig' => [
        'show_as_field' => TRUE,
        'show_on_form' => TRUE,
        'show_contextual_link' => FALSE,
      ],
      'linkTypeConfig' => [],
      'global' => FALSE,
    ]);
    $flag->save();

    $user = $this->drupalCreateUser([
      'create article content',
      'administer flags',
      'flag test_label_123',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests summary updates when flags are selected.
   */
  public function testFlagsSummary(): void {
    $this->drupalGet('node/add/article');

    $page = $this->getSession()->getPage();

    $summary = $page->find(
      'css',
      'details[data-drupal-selector="edit-flag"] span[class*="summary"]'
    );

    $this->assertStringContainsString('No flags', trim($summary->getHtml()));

    $page->checkField('edit-flag-test-label-123');

    $summary = $page->find(
      'css',
      'details[data-drupal-selector="edit-flag"] span[class*="summary"]'
    );

    $this->assertStringContainsString('Test flag', trim($summary->getHtml()));
  }

}
