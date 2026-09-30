<?php

namespace Drupal\Tests\flag\Functional;

use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Entity\View;

/**
 * Tests the FlagViewsRelationship plugin.
 *
 * @group flag
 * @see \Drupal\flag\Plugin\views\relationship\FlagViewsRelationship
 */
class FlagViewsRelationshipTest extends FlagTestBase {

  use UserCreationTrait;

  /**
   * First fake user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $u1;

  /**
   * Second fake user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $u2;

  /**
   * List of fake articles.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $articles = [];

  /**
   * Views to use in the test.
   *
   * @var \Drupal\views\ViewEntityInterface
   */
  protected $view;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'flag',
    'flag_bookmark',
    'views',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();
    $this->createUsers();
    $this->createFakeData();
    $this->view = View::load('flag_bookmark');
    $page_1 = &$this->view->getDisplay('page_1');
    $page_1["display_options"]["path"] = 'user/%user/my-bookmarks';
    $this->view->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();
  }

  /**
   * Test flag relationship with context user scope.
   */
  public function testContextUserScope() {
    $this->changeFlagRelationshipFilterValue('context');
    $this->drupalLogin($this->u1);
    $this->drupalGet('/user/' . $this->u2->id() . '/my-bookmarks');
    $this->assertSession()->pageTextContains($this->articles[0]->getTitle());
    $this->assertSession()->pageTextContains($this->articles[2]->getTitle());
    $this->assertSession()->pageTextContains($this->articles[3]->getTitle());

    $this->assertSession()->pageTextNotContains($this->articles[1]->getTitle());
    $this->assertSession()->pageTextNotContains($this->articles[4]->getTitle());

    $this->drupalLogin($this->u2);
    $this->drupalGet('/user/' . $this->u1->id() . '/my-bookmarks');
    $this->assertSession()->pageTextContains($this->articles[0]->getTitle());
    $this->assertSession()->pageTextContains($this->articles[1]->getTitle());

    $this->assertSession()->pageTextNotContains($this->articles[2]->getTitle());
    $this->assertSession()->pageTextNotContains($this->articles[4]->getTitle());
    $this->assertSession()->pageTextNotContains($this->articles[3]->getTitle());
  }

  /**
   * Test flag relationship with the current user scope.
   */
  public function testCurrentUserScope() {
    $this->changeFlagRelationshipFilterValue('current');
    $this->drupalLogin($this->u1);
    $this->drupalGet('/user/' . $this->u1->id() . '/my-bookmarks');
    $this->assertSession()->pageTextContains($this->articles[0]->getTitle());
    $this->assertSession()->pageTextContains($this->articles[1]->getTitle());
    $this->assertSession()->pageTextNotContains($this->articles[2]->getTitle());
  }

  /**
   * Test flag relationship with any user scope.
   */
  public function testAnyUserScope() {
    $this->changeFlagRelationshipFilterValue('any');
    $this->drupalLogin($this->u1);
    $this->drupalGet('/user/' . $this->u1->id() . '/my-bookmarks');
    // Any user does not bookmark the last article.
    foreach (array_slice($this->articles, 0, 4) as $article) {
      $this->assertSession()->pageTextContains($article->getTitle());
    }
    $this->assertSession()->pageTextNotContains($this->articles[4]->getTitle());
  }

  /**
   * Change the flag relationship filter value.
   *
   * @param string $field
   *   The field to change.
   */
  protected function changeFlagRelationshipFilterValue(string $field) {
    $default = &$this->view->getDisplay('default');
    $default["display_options"]["relationships"]["flag_relationship"]["user_scope"] = $field;
    $this->view->save();
  }

  /**
   * Create fake data for the test.
   */
  protected function createFakeData() {
    $this->articles[] = $this->drupalCreateNode(['type' => 'article', 'title' => 'article 1']);
    $this->articles[] = $this->drupalCreateNode(['type' => 'article', 'title' => 'article 2']);
    $this->articles[] = $this->drupalCreateNode(['type' => 'article', 'title' => 'article 3']);
    $this->articles[] = $this->drupalCreateNode(['type' => 'article', 'title' => 'article 4']);
    $this->articles[] = $this->drupalCreateNode(['type' => 'article', 'title' => 'article 5']);

    /** @var \Drupal\flag\FlagService $flag_service */
    $flag_service = \Drupal::service('flag');
    $bookmark_flag = $flag_service->getFlagById('bookmark');

    $flag_service->flag($bookmark_flag, $this->articles[0], $this->u1);
    $flag_service->flag($bookmark_flag, $this->articles[1], $this->u1);

    $flag_service->flag($bookmark_flag, $this->articles[0], $this->u2);
    $flag_service->flag($bookmark_flag, $this->articles[2], $this->u2);
    $flag_service->flag($bookmark_flag, $this->articles[3], $this->u2);
  }

  /**
   * Helper function to create users.
   */
  public function createUsers(): void {
    $this->u1 = $this->createUser(
      [
        'flag bookmark',
        'unflag bookmark',
      ],
      'John'
    );

    $this->u2 = $this->createUser(
      [
        'flag bookmark',
        'unflag bookmark',
      ],
      'John Doe'
    );
  }

  /**
   * Add flagged filter and test "all/any" filter option is present.
   */
  public function testFlaggingExposedFilter(): void {
    $this->drupalLogin($this->rootUser);

    $this->articles[] = $this->drupalCreateNode(['type' => 'article', 'title' => 'Flagged article 1']);
    $this->articles[] = $this->drupalCreateNode(['type' => 'article', 'title' => 'Unflagged article 1']);

    /** @var \Drupal\flag\FlagService $flag_service */
    $flag_service = \Drupal::service('flag');
    $bookmark_flag = $flag_service->getFlagById('bookmark');

    $flag_service->flag($bookmark_flag, $this->articles[5], $this->rootUser);

    $this->drupalGet('/admin/structure/views/view/flag_bookmark');

    // Remove filter that only displays published content.
    $this->drupalGet('/admin/structure/views/nojs/handler/flag_bookmark/page_1/filter/status');
    $this->assertSession()->elementExists('css', '#edit-remove');
    $this->submitForm([], 'Remove');

    // Update flag relationship to display all content, not just flagged.
    $this->drupalGet('/admin/structure/views/nojs/handler/flag_bookmark/page_1/relationship/flag_relationship');
    $this->assertSession()->elementExists('css', 'input[data-drupal-selector="edit-options-required"]');
    $edit = [
      'options[required]' => 0,
    ];
    $this->submitForm($edit, 'Apply');

    $this->drupalGet('/admin/structure/views/nojs/add-handler/flag_bookmark/page_1/filter');
    $this->assertSession()->elementExists('css', 'input[data-drupal-selector="edit-name-flaggingflagged"]');
    $edit = [
      'name[flagging.flagged]' => 1,
    ];
    $this->submitForm($edit, 'Add and configure filter criteria');

    $edit = [
      'options[expose_button][checkbox][checkbox]' => 1,
    ];
    $this->submitForm($edit, 'Expose filter');
    $this->assertSession()->pageTextContains('All');
    $this->assertSession()->elementExists('css', '#edit-options-value-all');
    $edit = [
      'options[expose_button][checkbox][checkbox]' => 1,
      'options[expose][required]' => 0,
      'options[group_button][radios][radios]' => 0,
    ];
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');

    $this->drupalGet('/user/' . $this->rootUser->id() . '/my-bookmarks');
    $this->assertSession()->pageTextContains('Flagged');
    $this->assertSession()->pageTextContains('- Any -');

    // Verify that the Unflagged article 1 is displayed on the page.
    $this->assertSession()->pageTextContains('Unflagged article 1');
  }

}
