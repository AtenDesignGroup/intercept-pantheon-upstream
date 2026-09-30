<?php

namespace Drupal\Tests\flag\FunctionalJavascript;

use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the click sorting AJAX functionality of Views exposed forms.
 *
 * @group views
 */
class ViewsAjaxLinkTest extends FlagJsTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views', 'flag', 'flag_bookmark', 'flag_test_views'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['flag_test_content'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ViewTestData::createTestViews(self::class, ['flag_test_views']);

    // Create a Content type and two test nodes.
    $this->createContentType(['type' => 'page']);
    $this->createNode(['title' => 'Page A']);
    $nodeB = $this->createNode(['title' => 'Page B']);

    // Create a user privileged enough to view content.
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'access content',
      'access content overview',
      'flag bookmark',
    ]);
    $this->drupalLogin($user);

    // Flag "Page B" node.
    $flag_service = \Drupal::service('flag');
    $flag = $flag_service->getFlagById('bookmark');
    $flag_service->flag($flag, $nodeB, $user);
  }

  /**
   * Tests if sorting via AJAX works for the "Bookmarks content" View.
   */
  public function testClickSorting() {
    // Visit the content page.
    $this->drupalGet('flag-test-content');

    $session_assert = $this->assertSession();

    $page = $this->getSession()->getPage();

    // Ensure that the Content we're testing for is in the right order, default
    // sorting is by Flagged (Unflagged first) so "Page A" node should be first.
    /** @var \Behat\Mink\Element\NodeElement[] $rows */
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(2, $rows);
    $this->assertStringContainsString('Page A', $rows[0]->getHtml());
    $this->assertStringContainsString('Page B', $rows[1]->getHtml());

    // Now sort by Flag link field and check if the order changed.
    $page->clickLink('Flag link');
    // Wait for some visual indication of the AJAX request completion instead of
    // using $session_assert->assertWaitOnAjaxRequest().
    $session_assert->waitForElementRemoved('css', '.visually-hidden');

    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(2, $rows);
    $this->assertStringContainsString('Page B', $rows[0]->getHtml());
    $this->assertStringContainsString('Page A', $rows[1]->getHtml());
  }

}
