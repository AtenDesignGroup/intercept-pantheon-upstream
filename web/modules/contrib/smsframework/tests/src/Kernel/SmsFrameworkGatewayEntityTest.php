<?php

declare(strict_types = 1);

namespace Drupal\Tests\sms\Kernel;

use Drupal\Core\Url;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsGatewayInterface;

/**
 * Tests SMS Framework gateway entity.
 *
 * @group SMS Framework
 */
final class SmsFrameworkGatewayEntityTest extends SmsFrameworkKernelBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'sms', 'sms_test_gateway', 'telephone', 'dynamic_entity_reference',
  ];

  /**
   * Tests skip queue.
   */
  public function testSkipQueue(): void {
    $gateway = $this->createGateway();
    $this->assertFalse($gateway->getSkipQueue(), 'Default value does not skip queue.');

    $gateway->setSkipQueue(TRUE);
    $this->assertTrue($gateway->getSkipQueue());
  }

  /**
   * Tests incoming retention setting.
   */
  public function testIncomingRetentionDuration(): void {
    $gateway = $this->createGateway();

    // Default value.
    $this->assertEquals(0, $gateway->getRetentionDuration(Direction::INCOMING));

    $gateway->setRetentionDuration(Direction::INCOMING, 444);
    $this->assertEquals(444, $gateway->getRetentionDuration(Direction::INCOMING));
  }

  /**
   * Tests outgoing retention setting.
   */
  public function testOutgoingRetentionDuration(): void {
    $gateway = $this->createGateway();

    // Default value.
    $this->assertEquals(0, $gateway->getRetentionDuration(Direction::INCOMING));

    $gateway->setRetentionDuration(Direction::OUTGOING, 999);
    $this->assertEquals(999, $gateway->getRetentionDuration(Direction::OUTGOING));
  }

  /**
   * Tests a bad retention direction.
   */
  public function testGetRetentionDurationInvalidDirection(): void {
    $gateway = $this->createGateway();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('0 is not a valid direction.');
    $gateway->getRetentionDuration(0);
  }

  /**
   * Tests incoming message path.
   */
  public function testPushIncomingPath(): void {
    $gateway = $this->createGateway(['plugin' => 'incoming']);

    $path = $gateway->getPushIncomingPath();
    $this->assertTrue(strpos($path, '/sms/incoming/receive/') === 0);

    $new_path = '/' . $this->randomMachineName();
    $return = $gateway->setPushIncomingPath($new_path);

    $this->assertTrue($return instanceof SmsGatewayInterface);
    $this->assertEquals($new_path, $gateway->getPushIncomingPath());
  }

  /**
   * Tests 'incoming' annotation custom value.
   */
  public function testSupportsIncoming(): void {
    $gateway = $this->createGateway(['plugin' => 'incoming']);
    $this->assertTrue($gateway->supportsIncoming());
  }

  /**
   * Tests 'incoming' annotation default value.
   */
  public function testNotSupportsIncoming(): void {
    $gateway = $this->createGateway(['plugin' => 'capabilities_default']);
    $this->assertFalse($gateway->supportsIncoming());
  }

  /**
   * Tests 'incoming_route' annotation custom value.
   */
  public function testAutoCreateIncomingRoute(): void {
    $gateway = $this->createGateway(['plugin' => 'incoming']);
    $this->assertTrue($gateway->autoCreateIncomingRoute());
  }

  /**
   * Tests 'incoming_route' annotation default value.
   */
  public function testNoAutoCreateIncomingRoute(): void {
    $gateway = $this->createGateway(['plugin' => 'capabilities_default']);
    $this->assertFalse($gateway->autoCreateIncomingRoute());
  }

  /**
   * Tests incoming report path.
   */
  public function testPushReportPath(): void {
    $gateway = $this->createGateway();

    $path = $gateway->getPushReportPath();
    $this->assertTrue(strpos($path, '/sms/delivery-report/receive/') === 0);

    $new_path = '/' . $this->randomMachineName();
    $return = $gateway->setPushReportPath($new_path);

    $this->assertTrue($return instanceof SmsGatewayInterface);
    $this->assertEquals($new_path, $gateway->getPushReportPath());
  }

  /**
   * Tests incoming report url.
   */
  public function testPushReportUrl(): void {
    $gateway = $this->createGateway();
    $this->assertTrue($gateway->getPushReportUrl() instanceof Url);
  }

  /**
   * Tests 'supports pushed reports' annotation custom value.
   */
  public function testSupportsReportsPushCustom(): void {
    $gateway = $this->createGateway([
      'plugin' => 'memory',
    ]);
    $this->assertTrue($gateway->supportsReportsPush());
  }

  /**
   * Tests 'supports credit balance' annotation default value.
   */
  public function testSupportsReportsPushDefault(): void {
    $gateway = $this->createGateway([
      'plugin' => 'capabilities_default',
    ]);
    $this->assertFalse($gateway->supportsReportsPush());
  }

  /**
   * Tests 'supports pulling reports' annotation custom value.
   */
  public function testSupportsReportsPullCustom(): void {
    $gateway = $this->createGateway([
      'plugin' => 'memory',
    ]);
    $this->assertTrue($gateway->supportsReportsPull());
  }

  /**
   * Tests 'supports pulling balance' annotation default value.
   */
  public function testSupportsReportsPullDefault(): void {
    $gateway = $this->createGateway([
      'plugin' => 'capabilities_default',
    ]);
    $this->assertFalse($gateway->supportsReportsPull());
  }

  /**
   * Tests 'max outgoing recipients' annotation custom value.
   */
  public function testGetMaxRecipientsOutgoingCustom(): void {
    $gateway = $this->createGateway([
      'plugin' => 'memory',
    ]);
    $this->assertEquals(-1, $gateway->getMaxRecipientsOutgoing());
  }

  /**
   * Tests 'max outgoing recipients' annotation default value.
   */
  public function testGetMaxRecipientsOutgoingDefault(): void {
    $gateway = $this->createGateway([
      'plugin' => 'capabilities_default',
    ]);
    $this->assertEquals(1, $gateway->getMaxRecipientsOutgoing());
  }

  /**
   * Tests 'incoming' annotation custom value.
   */
  public function testSupportsIncomingCustom(): void {
    $gateway = $this->createGateway([
      'plugin' => 'memory',
    ]);
    $this->assertEquals(TRUE, $gateway->supportsIncoming());
  }

  /**
   * Tests 'incoming' annotation default value.
   */
  public function testSupportsIncomingDefault(): void {
    $gateway = $this->createGateway([
      'plugin' => 'capabilities_default',
    ]);
    $this->assertEquals(FALSE, $gateway->supportsIncoming());
  }

  /**
   * Tests 'schedule aware annotation' custom value.
   */
  public function testIsScheduleAwareCustom(): void {
    $gateway = $this->createGateway([
      'plugin' => 'memory_schedule_aware',
    ]);
    $this->assertTrue($gateway->isScheduleAware());
  }

  /**
   * Tests 'schedule aware annotation' default value.
   */
  public function testIsScheduleAwareDefault(): void {
    $gateway = $this->createGateway([
      'plugin' => 'capabilities_default',
    ]);
    $this->assertFalse($gateway->isScheduleAware());
  }

  /**
   * Tests 'supports credit balance' annotation custom value.
   */
  public function testSupportsCreditBalanceQueryCustom(): void {
    $gateway = $this->createGateway([
      'plugin' => 'memory',
    ]);
    $this->assertTrue($gateway->supportsCreditBalanceQuery());
  }

  /**
   * Tests 'supports credit balance' annotation default value.
   */
  public function testSupportsCreditBalanceQueryDefault(): void {
    $gateway = $this->createGateway([
      'plugin' => 'capabilities_default',
    ]);
    $this->assertFalse($gateway->supportsCreditBalanceQuery());
  }

  /**
   * Create a new gateway.
   *
   * @param array $values
   *   Custom values to pass to the gateway.
   *
   * @return \Drupal\sms\Entity\SmsGatewayInterface
   *   An unsaved gateway config entity.
   */
  protected function createGateway(array $values = []): SmsGatewayInterface {
    return SmsGateway::create($values + [
      'plugin' => 'memory',
    ]);
  }

}
