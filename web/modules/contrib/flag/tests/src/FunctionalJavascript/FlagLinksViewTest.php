<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\FunctionalJavascript;

use Drupal\flag\Entity\Flag;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test the links are not rendered if content is not allow to be flagged.
 *
 * @group flag
 */
class FlagLinksViewTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'flag',
    'node',
    'user',
    'flag_bookmark',
    'views_ui',
  ];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * Flag to test with.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * The other flag to test with.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $otherFlag;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Testing node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The other testing node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $otherNode;

  /**
   * The third testing node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $thirdNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Get the flag service.
    $this->flagService = \Drupal::service('flag');

    // Create admin user.
    $this->admin = $this->drupalCreateUser([
      'administer nodes',
      'administer content types',
      'administer flags',
      'administer flaggings',
      'administer views',
      'flag bookmark',
      'unflag bookmark',
    ]);

    // Create content types.
    $this->drupalCreateContentType(['type' => 'test_page']);
    $this->drupalCreateContentType(['type' => 'article']);
    $this->drupalCreateContentType(['type' => 'page']);

    // Create nodes to flag.
    $this->node = $this->createNode([
      'type' => 'article',
      'uid' => $this->admin->id(),
      'title' => 'Article test page',
    ]);

    $this->otherNode = $this->createNode([
      'type' => 'page',
      'uid' => $this->admin->id(),
      'title' => 'Basic page test page',
    ]);

    $this->thirdNode = $this->createNode([
      'type' => 'test_page',
      'uid' => $this->admin->id(),
      'title' => 'Test page',
    ]);

    // Create a second flag.
    $this->otherFlag = Flag::create([
      'id' => 'test_flag',
      'label' => 'Test flag',
      'global' => FALSE,
      'entity_type' => 'node',
      'bundles' => ['test_page'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
    ]);
    $this->otherFlag->save();

    // Log in as admin and update the bookmarks view to display all flags.
    $this->drupalLogin($this->admin);
    $this->drupalGet('admin/structure/views/nojs/handler/flag_bookmark/page_1/relationship/flag_relationship');
    $this->submitForm(['options[required]' => 0], 'Apply');
    $this->submitForm([], 'Save');

    // Flag content.
    $bookmark = $this->flagService->getFlagById('bookmark');
    $otherFlag = $this->flagService->getFlagById('test_flag');
    $this->flagService->flag($bookmark, $this->node, $this->admin);
    $this->flagService->flag($bookmark, $this->otherNode, $this->admin);
    $this->flagService->flag($otherFlag, $this->thirdNode, $this->admin);

  }

  /**
   * Verify the behaviour of views flag links.
   */
  public function testViewLinks(): void {
    // Go to the bookmarks page.
    $this->drupalGet('bookmarks');

    // Assert all 3 nodes are displayed.
    $this->assertSession()->pageTextContains($this->node->label());
    $this->assertSession()->pageTextContains($this->otherNode->label());
    $this->assertSession()->pageTextContains($this->thirdNode->label());

    // Find all 3 elements with the class views-field-link-flag excluding
    // the table header.
    $page = $this->getSession()->getPage();
    $containers = $page->findAll('css', '.views-field-link-flag:not(th)');
    // Assert 3 elements found on the page.
    $this->assertCount(3, $containers, 'Found expected number (3) of link containers.');

    // Count how many containers contain a non-empty <a> link.
    $count = 0;
    foreach ($containers as $container) {
      $link = $container->find('css', 'a');
      if ($link && trim($link->getText()) !== '') {
        $count++;
      }
    }
    // Assert the number of valid links is 2.
    $this->assertEquals(2, $count, 'Expected number (2) of non-empty links found.');
  }

}
