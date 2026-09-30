<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Functional;

use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\user\Entity\Role;

/**
 * Test the Field Entry link type.
 *
 * @group flag
 */
class LinkTypeFieldEntryTest extends FlagTestBase {

  use FieldUiTestTrait;

  /**
   * Node id.
   *
   * @var string
   */
  protected $nodeId;

  /**
   * Global node id.
   *
   * @var string
   */
  protected $globalNodeId;

  /**
   * Flag Confirm Message.
   *
   * @var string
   */
  protected $flagConfirmMessage = 'Flag test label 123?';

  /**
   * Flag Details Message.
   *
   * @var string
   */
  protected $flagDetailsMessage = 'Enter flag test label 123 details';

  /**
   * Unflag Confirm Message.
   *
   * @var string
   */
  protected $unflagConfirmMessage = 'Unflag test label 123?';

  /**
   * Create Button Text.
   *
   * @var string
   */
  protected $createButtonText = 'Create flagging 123?';

  /**
   * Delete Button Text.
   *
   * @var string
   */
  protected $deleteButtonText = 'Delete flagging 123?';

  /**
   * Update Button Text.
   *
   * @var string
   */
  protected $updateButtonText = 'Updating flagging 123?';

  /**
   * Flag Field Id.
   *
   * @var string
   */
  protected $flagFieldId = 'flag_text_field';

  /**
   * Flag Field Label.
   *
   * @var string
   */
  protected $flagFieldLabel = 'Flag Text Field';

  /**
   * Flag Field Value.
   *
   * @var string
   */
  protected $flagFieldValue;

  /**
   * The flag object.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * The global flag object.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $globalFlag;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The breadcrumb block is needed for FieldUiTestTrait's tests.
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Create a new flag with the Field Entry type, and add fields.
   */
  public function testCreateFieldEntryFlag() {
    $this->drupalLogin($this->adminUser);
    $this->doCreateFlag();
    $this->doAddFields();
    $this->doFlagNode();
    $this->doEditFlagField();
    $this->doBadEditFlagField();
    $this->doUnflagNode();
    $this->drupalLogout();
  }

  /**
   * Create a node type and flag.
   */
  public function doCreateFlag() {
    $edit = [
      'bundles' => [$this->nodeType],
      'linkTypeConfig' => [
        'flag_confirmation' => $this->flagConfirmMessage,
        'unflag_confirmation' => $this->unflagConfirmMessage,
        'edit_flagging' => $this->flagDetailsMessage,
        'flag_create_button' => $this->createButtonText,
        'flag_delete_button' => $this->deleteButtonText,
        'flag_update_button' => $this->updateButtonText,
      ],
      'link_type' => 'field_entry',
    ];
    $this->flag = $this->createFlagFromArray($edit);
  }

  /**
   * Add fields to flag.
   */
  public function doAddFields() {
    $flag_id = $this->flag->id();

    // Check the Field UI tabs appear on the flag edit page.
    $this->drupalGet('admin/structure/flags/manage/' . $flag_id);
    $this->assertSession()->responseContains('Manage fields');

    $this->fieldUIAddNewField('admin/structure/flags/manage/' . $flag_id, $this->flagFieldId, $this->flagFieldLabel, 'text');
  }

  /**
   * Create a node and flag it.
   */
  public function doFlagNode() {
    $node = $this->drupalCreateNode(['type' => $this->nodeType]);
    $this->nodeId = $node->id();

    // Grant the flag permissions to the authenticated role, so that both
    // users have the same roles and share the render cache.
    $this->grantFlagPermissions($this->flag);

    // Create and login a new user.
    $user_1 = $this->drupalCreateUser();
    $this->drupalLogin($user_1);

    // Click the flag link.
    $this->drupalGet('node/' . $this->nodeId);
    $this->clickLink($this->flag->getShortText('flag'));

    // Check if we have the confirm form message displayed.
    $this->assertSession()->responseContains($this->flagConfirmMessage);

    // Enter the field value and submit it.
    $this->flagFieldValue = $this->randomString();
    $edit = [
      'field_' . $this->flagFieldId . '[0][value]' => $this->flagFieldValue,
    ];
    $this->submitForm($edit, $this->createButtonText);

    // Check that the node is flagged.
    $this->assertSession()->linkExists($this->flag->getShortText('unflag'));
  }

  /**
   * Edit the field value of the existing flagging.
   */
  public function doEditFlagField() {
    $flag_id = $this->flag->id();

    $this->drupalGet('node/' . $this->nodeId);

    // Get the details form.
    $this->clickLink($this->flag->getShortText('unflag'));

    $this->assertSession()->addressEquals('flag/details/edit/' . $flag_id . '/' . $this->nodeId);

    // See if the details message is displayed.
    $this->assertSession()->responseContains($this->flagDetailsMessage);

    // See if the field value was preserved.
    $this->assertSession()->fieldValueEquals('field_' . $this->flagFieldId . '[0][value]', $this->flagFieldValue);

    // Update the field value.
    $this->flagFieldValue = $this->randomString();
    $edit = [
      'field_' . $this->flagFieldId . '[0][value]' => $this->flagFieldValue,
    ];
    $this->submitForm($edit, $this->updateButtonText);

    // Get the details form.
    $this->drupalGet('flag/details/edit/' . $flag_id . '/' . $this->nodeId);

    // See if the field value was preserved.
    $this->assertSession()->fieldValueEquals('field_' . $this->flagFieldId . '[0][value]', $this->flagFieldValue);
  }

  /**
   * Assert editing an invalid flagging throws an exception.
   */
  public function doBadEditFlagField() {
    $flag_id = $this->flag->id();

    // Test a good flag ID param, but a bad flaggable ID param.
    $this->drupalGet('flag/details/edit/' . $flag_id . '/-9999');
    $this->assertSession()->statusCodeEquals(404);

    // Test a bad flag ID param, but a good flaggable ID param.
    $this->drupalGet('flag/details/edit/jibberish/' . $this->nodeId);
    $this->assertSession()->statusCodeEquals(404);

    // Test editing a unflagged entity.
    $unlinked_node = $this->drupalCreateNode(['type' => $this->nodeType]);
    $this->drupalGet('flag/details/edit/' . $flag_id . '/' . $unlinked_node->id());
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Test unflagging content.
   */
  public function doUnflagNode() {

    // Navigate to the node page.
    $this->drupalGet('node/' . $this->nodeId);

    // Click the Unflag link.
    $this->clickLink($this->flag->getShortText('unflag'));

    // Click the delete link.
    $this->clickLink($this->deleteButtonText);

    // Check if we have the confirm form message displayed.
    $this->assertSession()->responseContains($this->unflagConfirmMessage);

    // Submit the confirm form.
    $this->submitForm([], $this->deleteButtonText);
    $this->assertSession()->statusCodeEquals(200);

    // Check that the node is no longer flagged.
    $this->drupalGet('node/' . $this->nodeId);
    $this->assertSession()->linkExists($this->flag->getShortText('flag'));
  }

  /**
   * Tests global flagging.
   */
  public function testGlobalFlaggingNode(): void {
    // Create a new global flag.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'bundles' => [$this->nodeType],
      'linkTypeConfig' => [
        'flag_confirmation' => $this->flagConfirmMessage,
        'unflag_confirmation' => $this->unflagConfirmMessage,
        'edit_flagging' => $this->flagDetailsMessage,
        'flag_create_button' => $this->createButtonText,
        'flag_delete_button' => $this->deleteButtonText,
        'flag_update_button' => $this->updateButtonText,
      ],
      'link_type' => 'field_entry',
      'global' => TRUE,
    ];
    $this->globalFlag = $this->createFlagFromArray($edit);

    // Create a new node that will be flagged with global flag.
    $globalNode = $this->drupalCreateNode(['type' => $this->nodeType]);
    $this->globalNodeId = $globalNode->id();

    // Grant flagging permission to authenticated users.
    $this->grantFlagPermissions($this->globalFlag);

    // Create and login a new user.
    $user_2 = $this->drupalCreateUser();
    $this->drupalLogin($user_2);

    // Visit the node and flag it.
    $this->drupalGet('node/' . $this->globalNodeId);
    $this->clickLink($this->globalFlag->getShortText('flag'));

    // Verify the confirm form message is displayed.
    $this->assertSession()->responseContains($this->flagConfirmMessage);
    $this->submitForm([], $this->createButtonText);

    // Verify the node is flagged.
    $this->assertSession()->linkExists($this->globalFlag->getShortText('unflag'));

    // Create and login a new user.
    $user_3 = $this->drupalCreateUser();
    $this->drupalLogin($user_3);

    // Verify the previous node remained flagged.
    $this->drupalGet('node/' . $this->globalNodeId);
    $this->assertSession()->linkExists($this->globalFlag->getShortText('unflag'));
  }

  /**
   * Tests personal anonymous flagging.
   */
  public function testAnonymousPersonalFlag(): void {
    // Create a test node.
    $node = $this->drupalCreateNode(['type' => $this->nodeType]);

    // Create the flag.
    $this->doCreateFlag();

    // Grant the anonymous role permission to flag.
    /** @var \Drupal\user\RoleInterface $anonymousRole */
    $anonymousRole = Role::load(Role::ANONYMOUS_ID);
    $anonymousRole->grantPermission('flag ' . $this->flag->id());
    $anonymousRole->grantPermission('unflag ' . $this->flag->id());
    $anonymousRole->save();

    // Navigate to the node page.
    $this->drupalGet('node/' . $node->id());
    $this->clickLink($this->flag->getShortText('flag'));

    // Verify confirm form message id displayed.
    $this->assertSession()->responseContains($this->flagConfirmMessage);

    // Confirm the form.
    $this->submitForm([], $this->createButtonText);

    // Verify the node is flagged.
    $this->assertSession()->linkExists($this->flag->getShortText('unflag'));
  }

}
