<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Kernel;

use Drupal\Core\Url;
use Drupal\flag\Entity\Flag;
use Drupal\node\Entity\Node;

/**
 * Tests the getAsUrl() method in ActionLinkTypeBase.
 *
 * @group flag
 */
class ActionLinkTypeBaseTest extends FlagKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['flag', 'node', 'user'];

  /**
   * The flag entity.
   *
   * @var \Drupal\flag\Entity\Flag
   */
  protected $flag;

  /**
   * A test node entity.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * The action link type plugin.
   *
   * @var \Drupal\flag\ActionLink\ActionLinkTypeBase
   */
  protected $actionLinkType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install required entity schemas.
    $this->installEntitySchema('flagging');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['flag']);

    // Create a test flag.
    $this->flag = Flag::create([
      'id' => 'test_flag',
      'label' => 'Test Flag',
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_type' => 'entity:node',
      'action_link_type' => 'confirm',
    ]);
    $this->flag->save();

    // Create a test node.
    $this->node = Node::create([
      'type' => 'article',
      'title' => 'Test Node',
    ]);
    $this->node->save();

    // Get the action link type plugin.
    $this->actionLinkType = $this->container->get('plugin.manager.flag.linktype')->createInstance('confirm');
  }

  /**
   * Tests the getAsUrl() method.
   */
  public function testGetAsUrl(): void {
    $url = $this->actionLinkType->getAsUrl($this->flag, $this->node, 'teaser');

    // Assert the returned value is a Url object.
    $this->assertInstanceOf(Url::class, $url);

    // Assert the generated URL matches the expected route and parameters.
    $expected_route_name = 'flag.confirm_flag';
    $expected_parameters = [
      'flag' => 'test_flag',
      'entity_id' => $this->node->id(),
      'view_mode' => 'teaser',
    ];

    $this->assertEquals($expected_route_name, $url->getRouteName());
    $this->assertEquals($expected_parameters, $url->getRouteParameters());
  }

}
