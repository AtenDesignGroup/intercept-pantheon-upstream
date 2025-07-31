<?php

namespace Drupal\Tests\duration_field\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests whether seconds are calculated properly for duration fields.
 *
 * @group duration_field
 */
class DurationFieldSecondsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['field', 'field_ui', 'duration_field', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The field name used for the duration field.
   *
   * @var string
   */
  protected $fieldName = 'field_duration';

  /**
   * The content type used for the test.
   *
   * @var string
   */
  protected $contentType = 'test_type';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer content types',
      'administer nodes',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalCreateContentType([
      'type' => $this->contentType,
      'name' => 'Test content',
    ]);

    // Add a duration field to test content type.
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'duration',
      'settings' => ['granularity' => 'y:m:d:h:i:s'],
    ]);
    $fieldStorage->save();
    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => $this->contentType,
      'required' => TRUE,
    ]);
    $field->save();

    // Configure the widget to make sure field in shown on node form.
    $display = \Drupal::configFactory()
      ->getEditable('core.entity_form_display.node.' . $this->contentType . '.default');
    $display->set('content.' . $this->fieldName . '.type', 'duration_widget')
      ->set('content.' . $this->fieldName . '.settings', [])
      ->set('content.' . $this->fieldName . '.third_party_settings', [])
      ->set('content.' . $this->fieldName . '.weight', 0)
      ->save();
  }

  /**
   * Tests seconds update when duration field updated via form.
   *
   * @dataProvider secondsUpdateDataProvider
   */
  public function testSecondsUpdateOnForm($date_interval, $seconds) {
    $this->drupalGet('node/add/' . $this->contentType);
    $this->assertSession()->statusCodeEquals(200);
    $page = $this->getSession()->getPage();

    $page->fillField('title[0][value]', 'Dummy Title');
    $interval = new \DateInterval($date_interval);
    foreach (DurationFieldBrowserTestBase::DURATION_GRANULARITY as $field) {
      $page->fillField($this->fieldName . '[0][duration][' . $field . ']', $interval->format('%' . $field));
    }
    $this->click('input[name="op"]');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressMatches('/^\/node\/\d$/');

    // Fetch node id and compare seconds.
    $nid = $this->getSession()->getCurrentUrl();
    $nid = preg_replace('/^.*\/(\d)$/', '$1', $nid);
    $node = \Drupal::entityTypeManager()->getStorage('node')
      ->load($nid);
    $this->assertEquals($seconds, $node->get($this->fieldName)->seconds);
  }

  /**
   * Tests seconds update when duration field updated via node save.
   *
   * @dataProvider secondsUpdateDataProvider
   */
  public function testSecondsUpdateOnNodeSave($date_interval, $seconds) {
    $node = $this->drupalCreateNode([
      'type' => $this->contentType,
      $this->fieldName => [['duration' => $date_interval]],
    ]);
    $this->assertEquals($seconds, $node->get($this->fieldName)->seconds);
  }

  /**
   * Data provider with date interval strings and expected seconds.
   */
  public static function secondsUpdateDataProvider() {
    return [
      [
        'P1Y2M3DT4H5M6S',
        36907506,
      ],
      [
        'P1Y2M3DT4H5M',
        36907500,
      ],
      [
        'P1Y2M3DT4H',
        36907200,
      ],
      [
        'P1Y2M3D',
        36892800,
      ],
      [
        'P1Y2M',
        36633600,
      ],
      [
        'P1Y',
        31536000,
      ],
      [
        'P1M',
        2678400,
      ],
      [
        'P1D',
        86400,
      ],
      [
        'PT1H',
        3600,
      ],
      [
        'PT1M',
        60,
      ],
      [
        'PT1S',
        1,
      ],
    ];
  }

}
