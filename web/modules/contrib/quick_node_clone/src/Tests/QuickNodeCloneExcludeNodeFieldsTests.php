<?php

namespace Drupal\quick_node_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests node cloning excluding node fields.
 *
 * @group Quick Node Clone
 */
class QuickNodeCloneExcludeNodeFieldsTests extends WebTestBase {

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('quick_node_clone');

  /**
   * A user with the 'Administer quick_node_clone' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'Administer Quick Node Clone Settings',
      'clone page content',
      'create page content',
    ]);

    // Since we don't have ajax here, we need to set the config manually then test the form.
    \Drupal::configFactory()->getEditable('quick_node_clone.settings')
      ->set('exclude.node.page', ['body'])
      ->save();
  }

  /**
   * Test node clone excluding fields.
   */
  function testNodeCloneExcludeNodeFields() {
    $this->drupalLogin($this->adminUser);

    // Test the form.
    $edit = [
      'text_to_prepend_to_title' => 'Cloned from',
      'bundle_names[page]' => TRUE,
      'page[body]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/quick-node-clone', $edit, t('Save configuration'));

    // Create a basic page.
    $title_value = $this->randomGenerator->word(10);
    $body_value =  $this->randomGenerator->sentences(10);
    $edit = [
      'title[0][value]' => $title_value,
      'body[0][value]' => $body_value,
      'body[0][format]' => 'basic_html',
    ];
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $this->assertRaw($title_value);
    $this->assertRaw($body_value);

    // Clone node.
    $this->clickLink('Clone');
    $node = $this->getNodeByTitle($title_value);
    $this->drupalGet('clone/' . $node->id() . '/quick_clone');
    $this->drupalPostForm('clone/' . $node->id() . '/quick_clone', [], 'Save');
    $this->assertRaw('Cloned from ' . $title_value);
    $this->assertNoRaw($body_value);
  }

}
