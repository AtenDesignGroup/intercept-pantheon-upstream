<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Unit\Plugin\Action;

use Drupal\comment\CommentInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\Plugin\Flag\CommentFlagType;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\flag\Plugin\Action\FlagAction;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit tests for the flag action plugin.
 *
 * @group flag
 *
 * @coversDefaultClass \Drupal\flag\Plugin\Action\FlagAction
 */
class FlagActionTest extends UnitTestCase {

  /**
   * Mock flag.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * Mock user 1 account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $user1;

  /**
   * Mock user 2 account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $user2;

  /**
   * Mock comment flag type plugin.
   *
   * @var \Drupal\flag\Plugin\Flag\CommentFlagType
   */
  protected CommentFlagType $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $flag = $this->prophesize(FlagInterface::class);
    $flag->id()->willReturn(strtolower($this->randomMachineName()));
    $this->flag = $flag->reveal();
  }

  /**
   * Tests the execute method.
   *
   * @covers ::execute
   */
  public function testExecute() {
    // Test 'flag' op.
    $config = [
      'flag_id' => $this->flag->id(),
      'flag_action' => 'flag',
    ];
    $flag_service = $this->prophesize(FlagServiceInterface::class);
    $flag_service->getFlagById($this->flag->id())->willReturn($this->flag);
    $entity = $this->prophesize(EntityInterface::class)->reveal();
    $flag_service->flag($this->flag, $entity)->shouldBeCalled();
    $plugin = new FlagAction($config, 'flag_action:' . $this->flag->id() . '_flag', [], $flag_service->reveal());
    $plugin->execute($entity);

    // Test 'unflag' op.
    $config = [
      'flag_id' => $this->flag->id(),
      'flag_action' => 'unflag',
    ];
    $flag_service = $this->prophesize(FlagServiceInterface::class);
    $flag_service->getFlagById($this->flag->id())->willReturn($this->flag);
    $entity = $this->prophesize(EntityInterface::class)->reveal();
    $flag_service->unflag($this->flag, $entity)->shouldBeCalled();
    $plugin = new FlagAction($config, 'flag_action:' . $this->flag->id() . '_flag', [], $flag_service->reveal());
    $plugin->execute($entity);
  }

  /**
   * Tests the access method.
   *
   * @covers ::access
   */
  public function testAccess() {
    // Test access denied.
    $entity = $this->prophesize(EntityInterface::class)->reveal();
    $account = $this->prophesize(UserInterface::class)->reveal();
    $flag = $this->prophesize(FlagInterface::class);
    $flag->id()->willReturn(strtolower($this->randomMachineName()));
    $denied = $this->prophesize(AccessResultForbidden::class);
    $denied->isAllowed()->willReturn(FALSE);
    $denied = $denied->reveal();
    $flag->actionAccess('flag', $account, $entity)->willReturn($denied);
    $this->flag = $flag->reveal();
    $flag_service = $this->prophesize(FlagServiceInterface::class);
    $flag_service->getFlagById($this->flag->id())->willReturn($this->flag);

    $config = [
      'flag_id' => $this->flag->id(),
      'flag_action' => 'flag',
    ];
    $plugin = new FlagAction($config, 'flag_action:' . $this->flag->id() . '_flag', [], $flag_service->reveal());
    $this->assertFalse($plugin->access($entity, $account));
    $this->assertEquals($denied, $plugin->access($entity, $account, TRUE));

    // Test access allowed.
    $flag = $this->prophesize(FlagInterface::class);
    $flag->id()->willReturn(strtolower($this->randomMachineName()));
    $allowed = $this->prophesize(AccessResult::class);
    $allowed->isAllowed()->willReturn(TRUE);
    $allowed = $allowed->reveal();
    $flag->actionAccess('flag', $account, $entity)->willReturn($allowed);
    $this->flag = $flag->reveal();
    $flag_service = $this->prophesize(FlagServiceInterface::class);
    $flag_service->getFlagById($this->flag->id())->willReturn($this->flag);

    $config = [
      'flag_id' => $this->flag->id(),
      'flag_action' => 'flag',
    ];
    $plugin = new FlagAction($config, 'flag_action:' . $this->flag->id() . '_flag', [], $flag_service->reveal());
    $this->assertTrue($plugin->access($entity, $account));
    $this->assertEquals($allowed, $plugin->access($entity, $account, TRUE));
  }

  /**
   * Tests access permission for comments.
   */
  public function testCommentAccess(): void {
    // Set up all needed containers and mock all dependencies.
    $container = new ContainerBuilder();

    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('invokeAll')->willReturn([]);
    $container->set('module_handler', $module_handler);

    $current_user = $this->createMock(AccountInterface::class);
    $current_user->method('id')->willReturn(0);
    $container->set('current_user', $current_user);

    $container->set('cache_contexts_manager', $this->prophesize(CacheContextsManager::class));

    \Drupal::setContainer($container);

    $this->plugin = $this->getMockBuilder(CommentFlagType::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['hasExtraPermission', 'parentActionAccess'])
      ->getMock();

    $this->plugin->method('hasExtraPermission')
      ->with('parent_owner')
      ->willReturn(TRUE);

    $this->plugin->method('parentActionAccess')
      ->willReturn(AccessResult::neutral());

    $this->flag = $this->createMock(FlagInterface::class);
    $this->flag->method('id')->willReturn('test_flag');

    $this->user1 = $this->createMock(AccountInterface::class);
    $this->user2 = $this->createMock(AccountInterface::class);

    // Permissions for user1: can flag own parent comments.
    $this->user1->method('id')->willReturn(2);
    $this->user1->method('hasPermission')->willReturnCallback(function ($permission) {
      return in_array($permission, [
        'flag test_flag comments on own parent entities',
      ]);
    });

    // Permissions for user2: can flag others' parent comments.
    $this->user2->method('id')->willReturn(3);
    $this->user2->method('hasPermission')->willReturnCallback(function ($permission) {
      return in_array($permission, [
        'flag test_flag comments on other parent entities',
      ]);
    });

    // Test permissions.
    $this->testOwnParentCommentAccess();
    $this->testOthersParentCommentAccess();
    $this->testCannotFlagOthersOwnParent();

  }

  /**
   * Tests comments on own parent entities permission.
   */
  protected function testOwnParentCommentAccess(): void {
    // Node owned by user1.
    $node = $this->createMock(NodeInterface::class);
    $node->method('getOwnerId')->willReturn(2);

    // Comment on that node.
    $comment = $this->createMock(CommentInterface::class);
    $comment->method('getCommentedEntity')->willReturn($node);

    // User1 flags own comment.
    $result = $this->plugin->actionAccess('flag', $this->flag, $this->user1, $comment);
    $this->assertInstanceOf(AccessResult::class, $result);
    $this->assertTrue($result->isAllowed(), 'User1 can flag own parent comment.');

  }

  /**
   * Tests comments on other parent entities permission.
   */
  protected function testOthersParentCommentAccess():void {
    // Node owned by user1.
    $node = $this->createMock(NodeInterface::class);
    $node->method('getOwnerId')->willReturn(2);

    // Comment on that node.
    $comment = $this->createMock(CommentInterface::class);
    $comment->method('getCommentedEntity')->willReturn($node);

    // User2 flags user1 comment.
    $result = $this->plugin->actionAccess('flag', $this->flag, $this->user2, $comment);
    $this->assertInstanceOf(AccessResult::class, $result);
    $this->assertTrue($result->isAllowed(), 'User2 can flag comments on others’ parent entities.');
  }

  /**
   * Tests comments on own parent entities permission on other parent entity.
   */
  protected function testCannotFlagOthersOwnParent(): void {
    // Node owned by user2.
    $node = $this->createMock(NodeInterface::class);
    $node->method('getOwnerId')->willReturn(3);

    // Comment on that node.
    $comment = $this->createMock(CommentInterface::class);
    $comment->method('getCommentedEntity')->willReturn($node);

    // User1 tries to flag user2's node comment.
    $result = $this->plugin->actionAccess('flag', $this->flag, $this->user1, $comment);
    $this->assertInstanceOf(AccessResult::class, $result);
    $this->assertFalse($result->isAllowed(), 'User1 cannot flag comments on other users’ parent entities.');
  }

}
