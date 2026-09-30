<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\flag\Traits\FlagCreateTrait;
use Drupal\Tests\flag\Traits\FlagPermissionsTrait;
use Drupal\user\Entity\Role;

/**
 * Javascript test for AjaxLinks.
 *
 * When a user clicks on an AJAX link a salvo of AJAX commands is issued in
 * response which update the DOM with a new link and a short lived message.
 *
 * @see ActionLinkController
 *
 * @group flag
 */
class AjaxLinkTest extends WebDriverTestBase {

  use FlagCreateTrait;
  use FlagPermissionsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['flag', 'node', 'user'];

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
  protected $disableCssAnimations = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // A article to test with.
    $this->createContentType(['type' => 'article']);

    $this->admin = $this->createUser();

    $this->node = $this->createNode([
      'type' => 'article',
      'uid' => $this->admin->id(),
    ]);

    // A test flag.
    $this->flag = $this->createFlagFromArray([
      'link_type' => 'ajax_link',
      'flag_message' => 'Otters fascinate me.',
      'unflag_message' => 'No more otters.',
    ]);
    $this->flagService = $this->container->get('flag');

    $this->webUser = $this->createUser([
      'access content',
    ]);

    $this->grantFlagPermissions($this->flag);
    $this->drupalLogin($this->webUser);

  }

  /**
   * Tests DOM update.
   *
   * Click on flag and then unflag links verifying that the link cycles as
   * expected and flag flash message functions.
   */
  public function testDomUpdate() {
    // Get Page.
    $node_path = '/node/' . $this->node->id();
    $this->drupalGet($node_path);
    $session = $this->getSession();
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();

    // Verify initially flag link is on the page.
    $page = $session->getPage();
    $flag_link = $page->findLink($this->flag->getShortText('flag'));
    $this->assertTrue($flag_link->isVisible(), 'flag link exists.');

    $flag_link->click();

    // Verify flags message appears.
    $flag_message = $this->flag->getMessage('flag');
    $p_flash = $assert_session->waitForElementVisible('css', 'p.js-flag-message');
    $this->assertEquals($flag_message, $p_flash->getText(), 'DOM update(1): The flag message is flashed.');

    $assert_session->assertNoElementAfterWait('css', 'p.js-flag-message');
    $assert_session->elementTextEquals('css', '#drupal-live-announce', $flag_message);

    // Verify new link.
    $unflag_link = $session->getPage()->findLink($this->flag->getShortText('unflag'));
    $this->assertTrue($unflag_link->isVisible(), 'unflag link exists.');

    $unflag_link->click();

    // Verify unflag message appears.
    $unflag_message = $this->flag->getMessage('unflag');
    $p_flash2 = $assert_session
      ->waitForElementVisible('xpath', '//p[@class="js-flag-message" and contains(text(), "' . $unflag_message . '")]');
    $this->assertEquals($unflag_message, $p_flash2->getText(), 'DOM update(3): The unflag message is flashed.');
    $assert_session->assertNoElementAfterWait('css', 'p.js-flag-message');
    $assert_session->elementTextEquals('css', '#drupal-live-announce', $unflag_message);

    // Verify the cycle completes and flag returns.
    $flag_link2 = $session->getPage()->findLink($this->flag->getShortText('flag'));
    $this->assertTrue($flag_link2->isVisible(), 'flag cycle return to start.');

  }

  /**
   * Test anonymous role sees flag message when permission to unflag not set.
   */
  public function testAnonymousMessage() {
    $this->drupalLogout();
    // Grant the anonymous role permission to flag.
    /** @var \Drupal\user\RoleInterface $anonymousRole */
    $anonymousRole = Role::load(Role::ANONYMOUS_ID);
    $anonymousRole->grantPermission('flag ' . $this->flag->id());
    $anonymousRole->save();

    // Get Page.
    $this->drupalGet('/node/' . $this->node->id());

    // Verify flag link is on the page.
    $page = $this->getSession()->getPage();
    $flagLink = $page->findLink($this->flag->getShortText('flag'));
    $this->assertTrue($flagLink->isVisible(), 'flag link exists.');

    // Click the flag link.
    $flagLink->click();

    // Verify flags message appears.
    $flagMessage = $this->flag->getMessage('flag');
    $p_flash = $this->assertSession()->waitForElementVisible('css', 'p.js-flag-message');
    $this->assertEquals($flagMessage, $p_flash->getText(), 'DOM update(1): The flag message is flashed.');

    $this->assertSession()->assertNoElementAfterWait('css', 'p.js-flag-message');
    $this->assertSession()->elementTextEquals('css', '#drupal-live-announce', $flagMessage);

    // Verify unflag link does not exists.
    $unflagLink = $this->getSession()->getPage()->findLink($this->flag->getShortText('unflag'));
    $this->assertNull($unflagLink);

  }

}
