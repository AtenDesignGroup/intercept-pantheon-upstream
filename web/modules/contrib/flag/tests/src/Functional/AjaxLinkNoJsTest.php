<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\flag\Traits\FlagCreateTrait;
use Drupal\Tests\flag\Traits\FlagPermissionsTrait;

/**
 * Test the NoJS responses to clicking on  AjaxLinks.
 *
 * @see ActionLinkNoJsController
 *
 * @group flag
 */
class AjaxLinkNoJsTest extends BrowserTestBase {

  use FlagCreateTrait;
  use FlagPermissionsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['flag', 'flag_link_test', 'node', 'user'];

  /**
   * Flag to test with.
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
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * Normal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() :void {
    parent::setUp();

    // A article to test with.
    $this->createContentType(['type' => 'article']);

    $this->admin = $this->createUser();

    $this->node = $this->createNode([
      'type' => 'article',
      'uid' => $this->admin->id(),
    ]);

    // A test flag.
    $this->flag = $this->createFlag('node', ['article'], 'ajax_link');
    $this->flagService = $this->container->get('flag');

    $this->webUser = $this->createUser([
      'access content',
    ]);

    $this->grantFlagPermissions($this->flag);
    $this->drupalLogin($this->webUser);

  }

  /**
   * Test nojs response to AJAX links.
   *
   * The response is a redirect accompanied by a message appearing at the top
   * of the page.
   *
   * Click on flag and then unflag links verifying that the link cycles as
   * expected and flag message functions.
   */
  public function testNoJsMessage() {
    // Get Page.
    $this->drupalGet(Url::fromRoute('entity.node.canonical', ['node' => $this->node->id()]));
    $session = $this->getSession();

    // Verify initially flag link is on the page.
    $page = $session->getPage();
    $flag_link = $page->findLink($this->flag->getShortText('flag'));
    $this->assertNotNull($flag_link, 'flag link exists.');

    // Since this test is BrowserTestBase, and not JavascriptTestBase, this
    // simulates a noJS interaction.
    $flag_link->click();

    // Verify flags message appears.
    $flag_message = $this->flag->getMessage('flag');
    $this->assertSession()->pageTextContains($flag_message);

    // Verify new link.
    $unflag_link = $session->getPage()->findLink($this->flag->getShortText('unflag'));
    $this->assertNotNull($unflag_link, 'unflag link exists.');

    // Simulate a noJs ActionLink (unflag).
    $unflag_link->click();

    // Verify unflag message appears.
    $unflag_message = $this->flag->getMessage('unflag');
    $this->assertSession()->pageTextContains($unflag_message);

    // Verify the cycle completes and flag returns.
    $flag_link2 = $session->getPage()->findLink($this->flag->getShortText('flag'));
    $this->assertNotNull($flag_link2, 'flag cycle return to start.');

  }

  /**
   * Test nojs redirects when displaying the links on a different route.
   *
   * The user is redirected to the entity's canonical route by default after
   * flagging/unflagging. We need to test that the destination parameter is in
   * place to return to the page where the original link was found.
   */
  public function testNonCanonicalRouteRedirects() {
    // The URL of a test page containing the flag link.
    $url = Url::fromRoute('flag_link_test.page', [
      'entity_type_id' => 'node',
      'entity_id' => $this->node->id(),
      'flag' => $this->flag->id(),
    ]);

    // Navigate to the test page.
    $this->drupalGet($url);
    $session = $this->getSession();

    // Verify initially flag link is on the page.
    $flag_link = $session->getPage()->findLink($this->flag->getShortText('flag'));
    $this->assertNotNull($flag_link, 'flag link exists.');

    // Since this test is BrowserTestBase, and not JavascriptTestBase, this
    // simulates a noJS interaction.
    $flag_link->click();

    // Verify we have returned to the original page.
    $this->assertSession()->addressEquals($url);

    // Verify new link.
    $unflag_link = $session->getPage()->findLink($this->flag->getShortText('unflag'));
    $this->assertNotNull($unflag_link, 'unflag link exists.');

    // Simulate a noJs ActionLink (unflag).
    $unflag_link->click();

    // Verify we have returned to the original page again.
    $this->assertSession()->addressEquals($url);

    // Verify the cycle completes and flag returns.
    $flag_link2 = $session->getPage()->findLink($this->flag->getShortText('flag'));
    $this->assertNotNull($flag_link2, 'flag cycle return to start.');

  }

}
