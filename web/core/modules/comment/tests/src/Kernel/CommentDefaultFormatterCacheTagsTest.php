<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Cache\Cache;
use Drupal\comment\CommentInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\comment\Entity\Comment;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests comment cache tag bubbling up when using the Comment list formatter.
 *
 * @group comment
 */
class CommentDefaultFormatterCacheTagsTest extends EntityKernelTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'comment'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create user 1 so that the user created later in the test has a different
    // user ID.
    // @todo Remove in https://www.drupal.org/node/540008.
    $this->createUser([], NULL, FALSE, ['uid' => 1, 'name' => 'user1'])->save();

    $this->container->get('module_handler')->loadInclude('comment', 'install');
    comment_install();

    $session = new Session();

    $request = Request::create('/');
    $request->setSession($session);

    /** @var \Symfony\Component\HttpFoundation\RequestStack $stack */
    $stack = $this->container->get('request_stack');
    $stack->pop();
    $stack->push($request);

    // Set the current user to one that can access comments. Specifically, this
    // user does not have access to the 'administer comments' permission, to
    // ensure only published comments are visible to the end user.
    $current_user = $this->container->get('current_user');
    $current_user->setAccount($this->createUser(['access comments', 'post comments']));

    // Install tables and config needed to render comments.
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['system', 'filter', 'comment']);

    // Set up a field, so that the entity that'll be referenced bubbles up a
    // cache tag when rendering it entirely.
    $this->addDefaultCommentField('entity_test', 'entity_test');
  }

  /**
   * Tests the bubbling of cache tags.
   */
  public function testCacheTags(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    // Create the entity that will be commented upon.
    $commented_entity = EntityTest::create(['name' => $this->randomMachineName()]);
    $commented_entity->save();

    // Verify cache tags on the rendered entity before it has comments.
    $build = \Drupal::entityTypeManager()
      ->getViewBuilder('entity_test')
      ->view($commented_entity);
    $renderer->renderRoot($build);
    $expected_cache_tags = [
      'entity_test_view',
      'CACHE_MISS_IF_UNCACHEABLE_HTTP_METHOD:form',
      'entity_test:' . $commented_entity->id(),
      'config:core.entity_form_display.comment.comment.default',
      'config:field.field.comment.comment.comment_body',
      'config:field.field.entity_test.entity_test.comment',
      'config:field.storage.comment.comment_body',
      'config:user.settings',
    ];
    $this->assertEqualsCanonicalizing($expected_cache_tags, $build['#cache']['tags']);

    // Create a comment on that entity. Comment loading requires that the uid
    // also exists in the {users} table.
    $user = $this->createUser();
    $user->save();
    $comment = Comment::create([
      'subject' => 'Llama',
      'comment_body' => [
        'value' => 'Llamas are cool!',
        'format' => 'plain_text',
      ],
      'entity_id' => $commented_entity->id(),
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'comment_type' => 'comment',
      'status' => CommentInterface::PUBLISHED,
      'uid' => $user->id(),
    ]);
    $comment->save();

    // Load commented entity so comment_count gets computed.
    // @todo Remove the $reset = TRUE parameter after
    //   https://www.drupal.org/node/597236 lands. It's a temporary work-around.
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test');
    $storage->resetCache([$commented_entity->id()]);
    $commented_entity = $storage->load($commented_entity->id());

    // Verify cache tags on the rendered entity when it has comments.
    $build = \Drupal::entityTypeManager()
      ->getViewBuilder('entity_test')
      ->view($commented_entity);
    $renderer->renderRoot($build);
    $expected_cache_tags = [
      'entity_test_view',
      'entity_test:' . $commented_entity->id(),
      'comment_view',
      'comment:' . $comment->id(),
      'config:filter.format.plain_text',
      'user_view',
      'CACHE_MISS_IF_UNCACHEABLE_HTTP_METHOD:form',
      'user:' . $user->id(),
      'config:core.entity_form_display.comment.comment.default',
      'config:field.field.comment.comment.comment_body',
      'config:field.field.entity_test.entity_test.comment',
      'config:field.storage.comment.comment_body',
      'config:user.settings',
    ];
    $this->assertEqualsCanonicalizing($expected_cache_tags, $build['#cache']['tags']);

    // Build a render array with the entity in a sub-element so that lazy
    // builder elements bubble up outside of the entity and we can check that
    // it got the correct cache max age.
    $build = ['#type' => 'container'];
    $build['entity'] = \Drupal::entityTypeManager()
      ->getViewBuilder('entity_test')
      ->view($commented_entity);
    $renderer->renderRoot($build);

    // The entity itself was cached but the top-level element is max-age 0 due
    // to the bubbled up max age due to the lazy-built comment form.
    $this->assertSame(Cache::PERMANENT, $build['entity']['#cache']['max-age']);
    $this->assertSame(0, $build['#cache']['max-age'], 'Top level render array has max-age 0');

    // The children (fields) of the entity render array are only built in case
    // of a cache miss.
    $this->assertFalse(isset($build['entity']['comment']), 'Cache hit');
  }

}
