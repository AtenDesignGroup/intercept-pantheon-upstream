<?php

declare(strict_types=1);

namespace Drupal\Tests\votingapi\Kernel\migrate;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests D6 rate source plugin.
 *
 * @group VotingAPI
 */
class D6VoteMigrationTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['votingapi'];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath(): string {
    return __DIR__ . '/../../../fixtures/drupal6.php';
  }

  /**
   * Tests migration.
   */
  public function testVoteMigration(): void {
    $this->installEntitySchema('vote');
    $this->executeMigration('d6_vote_type');
    $this->executeMigration('d6_vote');
    $storage = \Drupal::entityTypeManager()->getStorage('vote');
    assert($storage instanceof EntityStorageInterface);
    $votes = $storage->loadMultiple();
    $this->assertCount(10, $votes);
    $array_1 = $votes[1]->toArray();
    $test_1 = [
      'id' => [['value' => '1']],
      'type' => [['target_id' => 'vote']],
      'entity_type' => [['value' => 'node']],
      'entity_id' => [['target_id' => '8']],
      'value' => [['value' => '77']],
      'value_type' => [['value' => 'percent']],
      'user_id' => [['target_id' => '4']],
      'timestamp' => [['value' => '1635759875']],
      'vote_source' => [['value' => '127.0.0.1']],
    ];
    $this->assertEquals(
      $test_1,
      array_diff_key(
        $array_1,
        ['uuid' => 'uuid']
      )
    );
  }

  /**
   * Tests Vote Type migration.
   */
  public function testVoteTypeMigration(): void {
    $vote_types_before_migration = \Drupal::entityTypeManager()->getStorage('vote_type');
    $vote_type_before = $vote_types_before_migration->loadMultiple();
    $this->assertCount(0, $vote_type_before);
    $this->executeMigrations(['d6_vote_type']);
    $vote_types_after_migration = \Drupal::entityTypeManager()->getStorage('vote_type');
    $vote_type_after = $vote_types_after_migration->loadMultiple();
    $this->assertCount(1, $vote_type_after);
  }

}
