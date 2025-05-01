<?php

declare(strict_types=1);

namespace Drupal\Tests\votingapi\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\votingapi\VoteInterface;

/**
 * Tests access control for vote entities.
 *
 * @group VotingAPI
 */
class VoteAccessTest extends KernelTestBase {
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'votingapi',
    'votingapi_test',
    'entity_test',
  ];

  /**
   * Access handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * A user with view own vote permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $viewOwnVoteUser;

  /**
   * A user with view any vote permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $viewAnyVoteUser;

  /**
   * A user without vote viewing permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $cannotViewVoteUser;

  /**
   * A vote cast by the viewOwnVoteUser user.
   *
   * @var \Drupal\votingapi\VoteInterface
   */
  protected $userVote;

  /**
   * A vote cast by the viewAnyVoteUser user.
   *
   * @var \Drupal\votingapi\VoteInterface
   */
  protected $adminVote;

  /**
   * A vote cast by the cannotViewVoteUser user.
   *
   * @var \Drupal\votingapi\VoteInterface
   */
  protected $noUserVote;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('vote');
    $this->installEntitySchema('entity_test');
    $this->installConfig('votingapi_test');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->accessHandler = $this->entityTypeManager
      ->getAccessControlHandler('vote');

    // Test entity.
    $entity_type_id = 'entity_test';
    $test_entity = $this->entityTypeManager->getStorage($entity_type_id)->create([]);
    $test_entity->save();
    $entity_id = $test_entity->id();

    // Test users.  The hasPermission() method assumes a user with a UID of 1
    // is guaranteed to have all permissions.  This is not necessarily true when
    // running a test, so a dummy user is created to absorb this special UID.
    $this->createUser([]);
    $this->viewOwnVoteUser = $this->createUser(['view own vote']);
    $this->viewAnyVoteUser = $this->createUser(['view any vote']);
    $this->cannotViewVoteUser = $this->createUser([]);

    // Test votes.
    $this->userVote = $this->createVote($entity_id, $entity_type_id, $this->viewOwnVoteUser->id());
    $this->adminVote = $this->createVote($entity_id, $entity_type_id, $this->viewAnyVoteUser->id());
    $this->noUserVote = $this->createVote($entity_id, $entity_type_id, $this->cannotViewVoteUser->id());
  }

  /**
   * Tests access control for viewing votes.
   *
   * @dataProvider viewVoteAccessProvider
   */
  public function testVoteViewAccess($expected_access, $vote, $user): void {
    $this->assertSame($expected_access,
      $this->accessHandler->access($this->{$vote}, 'view', $this->{$user}),
      "$user user does not have expected vote view access to vote $vote."
    );
  }

  /**
   * Provides test data for testVoteViewAccess().
   */
  public static function viewVoteAccessProvider(): array {
    return [
      [
        'can_view' => TRUE,
        'vote_getting_viewed' => 'userVote',
        'user_viewing_vote' => 'viewOwnVoteUser',
      ],
      [
        'can_view' => FALSE,
        'vote_getting_viewed' => 'adminVote',
        'user_viewing_vote' => 'viewOwnVoteUser',
      ],
      [
        'can_view' => TRUE,
        'vote_getting_viewed' => 'adminVote',
        'user_viewing_vote' => 'viewAnyVoteUser',
      ],
      [
        'can_view' => TRUE,
        'vote_getting_viewed' => 'userVote',
        'user_viewing_vote' => 'viewAnyVoteUser',
      ],
      [
        'can_view' => FALSE,
        'vote_getting_viewed' => 'noUserVote',
        'user_viewing_vote' => 'cannotViewVoteUser',
      ],
      [
        'can_view' => FALSE,
        'vote_getting_viewed' => 'userVote',
        'user_viewing_vote' => 'cannotViewVoteUser',
      ],
    ];
  }

  /**
   * Helper method to create a vote.
   *
   * @param int $entity_id
   *   The ID of the test entity.
   * @param string $entity_type_id
   *   The entity type ID of the test entity.
   * @param int $uid
   *   The user ID of the user casting the vote.
   *
   * @return \Drupal\votingapi\VoteInterface
   *   An instance of a vote entity.
   */
  protected function createVote($entity_id, $entity_type_id, $uid): VoteInterface {
    /** @var \Drupal\votingapi\VoteTypeInterface $vote_type */
    $vote_type = $this->entityTypeManager->getStorage('vote_type')->load('test');
    /** @var \Drupal\votingapi\VoteInterface $vote */
    $vote = $this->entityTypeManager->getStorage('vote')->create(['type' => 'test']);
    $vote->setVotedEntityId($entity_id);
    $vote->setVotedEntityType($entity_type_id);
    $vote->setValueType($vote_type->getValueType());
    $vote->setValue(1);
    $vote->setOwnerId($uid);
    $vote->save();
    return $vote;
  }

}
