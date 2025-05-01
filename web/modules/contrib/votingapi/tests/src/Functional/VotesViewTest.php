<?php

declare(strict_types=1);

namespace Drupal\Tests\votingapi\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the "votingapi_votes" View that is shipped with Voting API.
 *
 * @group VotingAPI
 */
class VotesViewTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'views',
    'votingapi',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that views.view.votingapi_votes is enabled.
   *
   * The views.view.votingapi_votes configuration depends on the modules:
   * Views, Node, and User.
   */
  public function testViewsEnabled(): void {
    $status = \Drupal::service('module_handler')->moduleExists('views');
    $this->assertTrue($status, 'Views module is enabled.');
    $config = \Drupal::configFactory()->get('views.view.votingapi_votes');
    $this->assertNotNull($config, 'views.view.votingapi_votes exists.');
    $this->assertTrue($config->get('status'), 'views.view.votingapi_votes is enabled.');
  }

}
