<?php

namespace Drupal\Tests\flag\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Browser tests for the flag.twig.link service.
 *
 * @see Drupal\flag\TwigExtension\FlagLink
 *
 * @group flag
 */
class FlagExtensionTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'views',
    'flag',
    'flag_bookmark',
    'flag_twig_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    // Set the linkTypePlugin of the flag to ajax_link for running the tests.
    $flag_service = $this->container->get('flag');
    $bookmark_flag = $flag_service->getFlagById('bookmark');
    $bookmark_flag->setlinkTypePlugin('ajax_link');
    $bookmark_flag->save();
  }

  /**
   * Tests that the flag link toggles correctly using the Twig extension.
   */
  public function testUi() {
    // Generate a unique title so we can find it on the page easily.
    $title = $this->randomMachineName();

    // Add a single article.
    $article = $this->drupalCreateNode(['type' => 'article', 'title' => $title]);

    $auth_user = $this->drupalCreateUser([
      'flag bookmark',
      'unflag bookmark',
      'access content',
    ]);

    $this->drupalLogin($auth_user);

    // Visit the test page using the Twig extension.
    $this->drupalGet('flag-test-page/' . $article->id());
    // First toggle: Flag the entity.
    $this->assertFlagLinkPresentAndClick();
    // Second toggle: Unflag the entity.
    $this->assertUnflagLinkPresentAndClick();
    // Third toggle: Flag again.
    $this->assertFlagLinkPresentAndClick();

  }

  /**
   * Asserts the flag link is present and toggles it.
   */
  private function assertFlagLinkPresentAndClick(): void {
    $this->getSession()->wait(5000, "document.querySelector('div.flag-bookmark.action-flag > a') !== null");

    $page = $this->getSession()->getPage();
    $flagDiv = $page->find('css', 'div.flag-bookmark.action-flag');
    $this->assertNotNull($flagDiv, 'Flag wrapper div with action-flag class is present.');

    $flagLink = $flagDiv->find('css', 'a.use-ajax');
    $this->assertNotNull($flagLink, 'Flag link inside the wrapper div is present.');
    $this->assertEquals('Bookmark this', trim($flagLink->getText()), 'Flag link text is as expected.');

    $flagLink->click();
  }

  /**
   * Asserts the unflag link is present and toggles it.
   */
  private function assertUnflagLinkPresentAndClick(): void {
    $this->getSession()->wait(5000, "document.querySelector('div.flag-bookmark.action-unflag > a') !== null");

    $page = $this->getSession()->getPage();
    $unflagLink = $page->find('css', 'div.flag-bookmark.action-unflag > a');
    $this->assertNotNull($unflagLink, 'Unflag link is present after toggling.');

    // Assert message appears.
    $message = $page->find('css', '.js-flag-message');
    $this->assertNotNull($message, 'Flag message is rendered.');

    $unflagLink->click();
  }

}
