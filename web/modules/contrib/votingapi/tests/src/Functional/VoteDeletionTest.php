<?php

declare(strict_types=1);

namespace Drupal\Tests\votingapi\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the deletion of votes.
 *
 * @group VotingAPI
 */
class VoteDeletionTest extends BrowserTestBase {

  /**
   * The node object.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'votingapi',
    'votingapi_test',
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

    $user = $this->createUser([
      'access administration pages',
      'delete votes',
    ]);

    $this->node = $this->createNode([
      'type' => 'article',
      'title' => $this->randomMachineName(),
    ]);

    $this->drupalLogin($user);
  }

  /**
   * Tests deleting a vote.
   */
  public function testVoteDeletion(): void {
    $session = $this->assertSession();
    /** @var \Drupal\votingapi\VoteStorageInterface $vote_storage */
    $vote_storage = $this->container->get('entity_type.manager')->getStorage('vote');

    // Save a few votes.
    $values = [
      3 => 'source_1',
      4 => 'source_2',
      5 => 'source_2',
    ];

    foreach ($values as $value => $source) {
      $vote_storage->create([
        'type' => 'vote',
        'entity_id' => $this->node->id(),
        'entity_type' => 'node',
        'user_id' => 0,
        'value' => $value,
        'vote_source' => $source,
      ])->save();
    }

    // Get vote id.
    $vote_id = \Drupal::entityQuery('vote')
      ->condition('vote_source', 'source_1')
      ->accessCheck(TRUE)
      ->execute();

    /** @var \Drupal\votingapi\VoteInterface $vote */
    $vote = $vote_storage->load(reset($vote_id));
    $vote_owner = $vote->getOwner()->getDisplayName();
    $entity_type = $this->node->getEntityType()->getSingularLabel();
    $label = $this->node->label();

    // Delete a vote.
    $this->drupalGet('admin/vote/' . reset($vote_id) . '/delete');
    $session->pageTextContains('You are about to delete a vote by ' . $vote_owner . ' on ' . $entity_type . ' ' . $label . '. This action cannot be undone.');
    $this->submitForm([], 'Delete');
    $session->pageTextContains('The vote by ' . $vote_owner . ' on ' . $entity_type . ' ' . $label . ' has been deleted.');

    // Assert that the vote got deleted and other votes remain.
    $source_1_votes = $vote_storage->getUserVotes(0, 'vote', 'node', 1, 'source_1');
    $this->assertCount(0, $source_1_votes);
    $source_2_votes = $vote_storage->getUserVotes(0, 'vote', 'node', 1, 'source_2');
    $this->assertCount(2, $source_2_votes);
  }

}
