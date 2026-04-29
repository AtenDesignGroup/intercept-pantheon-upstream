<?php

namespace Drupal\Tests\flag\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\flag\Entity\Flag;
use Drupal\flag\Entity\Flagging;
use Drupal\flag\FlagInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Tests cache invalidation from flagging with custom cache tags.
 *
 * @group flag
 */
class FlagCacheTagsTest extends FlagKernelTestBase {

  /**
   * Node used in tests.
   *
   * @var \Drupal\node\NodeInterface|null
   */
  protected ?NodeInterface $node;

  /**
   * Flag for testing.
   *
   * @var \Drupal\flag\FlagInterface|null
   */
  protected ?FlagInterface $flag;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'node',
    'user',
    'flag',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->node = Node::create([
      'type' => 'page',
      'title' => 'Cache tags test',
    ]);
    $this->node->save();

    $flag = Flag::create([
      'id' => 'test_flag',
      'label' => 'Test Flag',
      'entity_type' => 'node',
      'bundles' => ['page'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
      'global' => FALSE,
    ]);
    $flag->save();

    $this->flag = $flag;
    $this->flagService = $this->container->get('flag');
  }

  /**
   * Test invalidation of cache tags.
   */
  public function testCustomCacheTagsInvalidation(): void {
    $render_array = $this->buildFlagRenderArray();

    $this->assertSame(['Flag this content'], $render_array['#markup']);
    $this->assertContains('flagging:test_flag:node:' . $this->node->id(), $render_array['#cache']['tags']);

    // Flag the node.
    $this->flagService->flag($this->flag, $this->node);

    $tags_to_invalidate = [
      'flagging:test_flag:node:' . $this->node->id(),
      'flagging:test_flag:node:' . $this->node->id() . ':1',
      'flagging:test_flag:node:*:1',
    ];
    Cache::invalidateTags($tags_to_invalidate);

    $render_array = $this->buildFlagRenderArray();
    $this->assertSame(['Unflag this content'], $render_array['#markup']);
  }

  /**
   * Builds a simulated render array for the flagged state.
   *
   * @return array
   *   Render array.
   */
  protected function buildFlagRenderArray(): array {
    $flaggings = $this->flagService->getEntityFlaggings($this->flag, $this->node);
    $flagging = reset($flaggings);
    return [
      '#markup' => [($flagging instanceof Flagging) ? 'Unflag this content' : 'Flag this content'],
      '#cache' => [
        'tags' => [
          'flagging:' . $this->flag->id() . ':node:' . $this->node->id(),
        ],
        'contexts' => ['user'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

}
