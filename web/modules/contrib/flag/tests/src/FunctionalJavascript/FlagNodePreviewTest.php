<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\flag\Traits\FlagCreateTrait;

/**
 * Test flag link display on node preview page.
 *
 * @group flag
 */
class FlagNodePreviewTest extends WebDriverTestBase {

  use FlagCreateTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'flag',
    'user',
  ];

  /**
   * The flag for testing.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The node for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Get the Flag Service.
    $this->flagService = $this->container->get('flag');

    // Create content type.
    $this->drupalCreateContentType(['type' => 'article']);

    // Create flag.
    $this->flag = $this->createFlag('node', ['article'], 'ajax_link');

    // Create node.
    $this->node = $this->drupalCreateNode(['type' => 'article', 'title' => 'Test article']);
  }

  /**
   * Tests flag link display on node preview.
   */
  public function testFlagPreviewNode(): void {
    // Create a user with permissions to edit nodes and flag/unflag.
    $this->webUser = $this->drupalCreateUser([
      'administer nodes',
      'edit any article content',
      'flag ' . $this->flag->id(),
      'unflag ' . $this->flag->id(),
    ]);

    $this->drupalLogin($this->webUser);

    // Navigate to the edit node page.
    $this->drupalGet('node/' . $this->node->id() . '/edit');

    // Verify that the preview button exists and click it.
    $this->assertSession()->elementExists('css', '#edit-preview');
    $button = $this->getSession()->getPage()->find('css', '#edit-preview');
    $this->assertNotNull($button);
    $button->click();

    // Verify the button click rendered the preview page.
    $previewUrl = $this->getSession()->getCurrentUrl();
    $this->assertStringContainsString('/preview', $previewUrl);

    // Don't need to, but visit the preview page.
    $this->drupalGet($previewUrl);
    $this->assertSession()->pageTextContains('View mode');

    // Verify the flag link is visible.
    $this->assertSession()->linkExists($this->flag->getShortText('flag'));
  }

}
