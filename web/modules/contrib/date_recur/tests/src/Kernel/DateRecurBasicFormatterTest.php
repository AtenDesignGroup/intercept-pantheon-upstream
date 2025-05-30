<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\Core\Datetime\DateFormatInterface;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\date_recur\Entity\DateRecurInterpreter;
use Drupal\date_recur\Entity\DateRecurInterpreterInterface;
use Drupal\date_recur_entity_test\Entity\DrEntityTestBasic;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests date recur formatter.
 *
 * @group date_recur
 * @coversDefaultClass \Drupal\date_recur\Plugin\Field\FieldFormatter\DateRecurBasicFormatter
 */
final class DateRecurBasicFormatterTest extends KernelTestBase {

  protected static $modules = [
    'date_recur_entity_test',
    'entity_test',
    'datetime',
    'datetime_range',
    'date_recur',
    'field',
    'user',
    // System provides 'time' template.
    'system',
  ];

  /**
   * A date format for testing.
   *
   * @var \Drupal\Core\Datetime\DateFormatInterface
   */
  private DateFormatInterface $dateFormat;

  /**
   * An interpreter for testing.
   *
   * @var \Drupal\date_recur\Entity\DateRecurInterpreterInterface
   */
  private DateRecurInterpreterInterface $interpreter;

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('dr_entity_test');

    $this->dateFormat = DateFormat::create([
      'id' => $this->randomMachineName(),
      'pattern' => 'r',
    ]);
    $this->dateFormat->save();
    $this->interpreter = DateRecurInterpreter::create([
      'id' => $this->randomMachineName(),
      'plugin' => 'rl',
      'settings' => [
        'show_start_date' => TRUE,
        'show_until' => TRUE,
        'date_format' => $this->dateFormat->id(),
        'show_infinite' => TRUE,
      ],
    ]);
    $this->interpreter->save();
  }

  /**
   * Tests interpretation.
   */
  public function testFormatterInterpretation(): void {
    $dateFormatId = $this->dateFormat->id();
    $settings = [
      'format_type' => $dateFormatId,
      'occurrence_format_type' => $dateFormatId,
      'same_end_date_format_type' => $dateFormatId,
      'interpreter' => $this->interpreter->id(),
    ];
    $this->renderFormatterSettings($this->createRecurringEntity(), $settings);

    $interpretation = $this->cssSelect('.date-recur-interpretation');
    static::assertCount(1, $interpretation);
    $assertInnerText = (string) $interpretation[0];
    static::assertEquals('weekly on Monday, Tuesday, Wednesday, Thursday and Friday, starting from Mon, 16 Jun 2014 09:00:00 +1000, forever', $assertInnerText);
  }

  /**
   * Tests occurrences.
   */
  public function testFormatterOccurrencesPerItem(): void {
    $this->dateFormat = DateFormat::create([
      'id' => $this->randomMachineName(),
      'pattern' => 'H:i',
    ]);
    $this->dateFormat->save();
    $dateFormatId = $this->dateFormat->id();
    $settings = [
      'show_next' => 2,
      'count_per_item' => TRUE,
      'format_type' => $dateFormatId,
      'occurrence_format_type' => $dateFormatId,
      'same_end_date_format_type' => $dateFormatId,
      'interpreter' => $this->interpreter->id(),
    ];

    $entity = DrEntityTestBasic::create();
    $entity->dr->setValue([
      [
        // 10am-4pm weekdaily.
        'value' => '2008-06-16T00:00:00',
        'end_value' => '2008-06-16T06:00:00',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
        'infinite' => '1',
        'timezone' => 'Australia/Sydney',
      ],
      [
        // 9am-5pm weekdaily.
        'value' => '2014-06-15T23:00:00',
        'end_value' => '2014-06-16T07:00:00',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
        'infinite' => '1',
        'timezone' => 'Australia/Sydney',
      ],
    ]);
    $this->renderFormatterSettings($entity, $settings);

    $occurrences = $this->cssSelect('.date-recur-occurrences li');
    static::assertCount(4, $occurrences);
    static::assertEquals('10:00', (string) $occurrences[0]->time[0]);
    static::assertEquals('16:00', (string) $occurrences[0]->time[1]);
    static::assertEquals('10:00', (string) $occurrences[1]->time[0]);
    static::assertEquals('16:00', (string) $occurrences[1]->time[1]);
    static::assertEquals('09:00', (string) $occurrences[2]->time[0]);
    static::assertEquals('17:00', (string) $occurrences[2]->time[1]);
    static::assertEquals('09:00', (string) $occurrences[3]->time[0]);
    static::assertEquals('17:00', (string) $occurrences[3]->time[1]);
  }

  /**
   * Tests occurrences.
   */
  public function testFormatterOccurrencesNotPerItem(): void {
    $this->dateFormat = DateFormat::create([
      'id' => $this->randomMachineName(),
      'pattern' => 'H:i',
    ]);
    $this->dateFormat->save();
    $dateFormatId = $this->dateFormat->id();
    $settings = [
      'show_next' => 2,
      'count_per_item' => FALSE,
      'format_type' => $dateFormatId,
      'occurrence_format_type' => $dateFormatId,
      'same_end_date_format_type' => $dateFormatId,
      'interpreter' => $this->interpreter->id(),
    ];

    $entity = DrEntityTestBasic::create();
    $entity->dr->setValue([
      [
        // 10am-4pm weekdaily.
        'value' => '2008-06-16T00:00:00',
        'end_value' => '2008-06-16T06:00:00',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
        'infinite' => '1',
        'timezone' => 'Australia/Sydney',
      ],
      [
        // 9am-5pm weekdaily.
        'value' => '2014-06-15T23:00:00',
        'end_value' => '2014-06-16T07:00:00',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
        'infinite' => '1',
        'timezone' => 'Australia/Sydney',
      ],
    ]);
    $this->renderFormatterSettings($entity, $settings);

    $occurrences = $this->cssSelect('.date-recur-occurrences li');
    static::assertCount(2, $occurrences);
    static::assertEquals('10:00', (string) $occurrences[0]->time[0]);
    static::assertEquals('16:00', (string) $occurrences[0]->time[1]);
    static::assertEquals('10:00', (string) $occurrences[1]->time[0]);
    static::assertEquals('16:00', (string) $occurrences[1]->time[1]);
  }

  /**
   * Tests setting summary.
   */
  public function testFormatterSettingsSummary(): void {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $efm */
    $efm = $this->container->get(EntityFieldManagerInterface::class);
    $definitions = $efm->getBaseFieldDefinitions('dr_entity_test');

    $separator = $this->randomMachineName(4);

    $dateFormatId = $this->dateFormat->id();
    $options = [
      'configuration' => [
        'label' => 'above',
        'type' => 'date_recur_basic_formatter',
        'settings' => [
          'format_type' => $dateFormatId,
          'occurrence_format_type' => $dateFormatId,
          'same_end_date_format_type' => $dateFormatId,
          'interpreter' => $this->interpreter->id(),
          'count_per_item' => TRUE,
          'separator' => $separator,
          'show_next' => 5,
        ],
      ],
      'field_definition' => $definitions['dr'],
      'prepare' => TRUE,
      'view_mode' => 'full',
    ];

    /** @var \Drupal\Core\Field\FormatterPluginManager $fieldFormatterManager */
    $fieldFormatterManager = $this->container->get('plugin.manager.field.formatter');
    $instance = $fieldFormatterManager->getInstance($options);

    // Generate after summary to prevent random test failures.
    $now = new \DateTime('now');
    $formatSample = $now->format($this->dateFormat->getPattern());

    /** @var array<string|\Drupal\Core\StringTranslation\TranslatableMarkup|array{'#context': array{label: \Drupal\Core\StringTranslation\TranslatableMarkup}}> $summary */
    $summary = $instance->settingsSummary();
    $summary = \array_map(static function (string|TranslatableMarkup|array $summary): string {
      return (string) (\is_array($summary) ? $summary['#context']['label'] : $summary);
    }, $summary);
    static::assertContains('Format: ' . $formatSample, $summary);
    static::assertContains('Separator: <em class="placeholder">' . $separator . '</em>', $summary);
    static::assertContains('Show maximum of 5 occurrences per field item', $summary);
  }

  /**
   * Tests setting summary where count is shared across items.
   */
  public function testFormatterSettingsSummaryNotPerItem(): void {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $efm */
    $efm = $this->container->get(EntityFieldManagerInterface::class);
    $definitions = $efm->getBaseFieldDefinitions('dr_entity_test');

    $dateFormatId = $this->dateFormat->id();
    $options = [
      'configuration' => [
        'label' => 'above',
        'type' => 'date_recur_basic_formatter',
        'settings' => [
          'format_type' => $dateFormatId,
          'occurrence_format_type' => $dateFormatId,
          'same_end_date_format_type' => $dateFormatId,
          'interpreter' => $this->interpreter->id(),
          'count_per_item' => FALSE,
          'separator' => '-',
          'show_next' => 10,
        ],
      ],
      'field_definition' => $definitions['dr'],
      'prepare' => TRUE,
      'view_mode' => 'full',
    ];

    /** @var \Drupal\Core\Field\FormatterPluginManager $fieldFormatterManager */
    $fieldFormatterManager = $this->container->get('plugin.manager.field.formatter');
    $instance = $fieldFormatterManager->getInstance($options);
    /** @var array<string|\Drupal\Core\StringTranslation\TranslatableMarkup|array{'#context': array{label: \Drupal\Core\StringTranslation\TranslatableMarkup}}> $summary */
    $summary = $instance->settingsSummary();
    $summary = \array_map(static function (string|TranslatableMarkup|array $summary): string {
      return (string) (\is_array($summary) ? $summary['#context']['label'] : $summary);
    }, $summary);
    static::assertContains('Show maximum of 10 occurrences across all field items', $summary);
  }

  /**
   * Tests setting summary occurrence sample for same day.
   */
  public function testFormatterSettingsSummarySampleOccurrenceSameDay(): void {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $efm */
    $efm = $this->container->get('entity_field.manager');
    $definitions = $efm->getBaseFieldDefinitions('dr_entity_test');

    $dateFormatId = $this->dateFormat->id();
    $options = [
      'configuration' => [
        'label' => 'above',
        'type' => 'date_recur_basic_formatter',
        'settings' => [
          'format_type' => $dateFormatId,
          'occurrence_format_type' => $dateFormatId,
          'same_end_date_format_type' => $dateFormatId,
          'interpreter' => $this->interpreter->id(),
          'count_per_item' => FALSE,
          'separator' => '-',
          'show_next' => 10,
        ],
      ],
      'field_definition' => $definitions['dr'],
      'prepare' => TRUE,
      'view_mode' => 'full',
    ];

    /** @var \Drupal\Core\Field\FormatterPluginManager $fieldFormatterManager */
    $fieldFormatterManager = $this->container->get('plugin.manager.field.formatter');
    $instance = $fieldFormatterManager->getInstance($options);
    $summary = $instance->settingsSummary();

    $start = new \DateTime('today 9am');
    $endSameDay = clone $start;
    $endSameDay->setTime(17, 0, 0);

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $rendered = $renderer->renderRoot($summary['sample_same_day']);
    // Remove newlines from Twig templates.
    $rendered = \preg_replace('/\n/', '', (string) $rendered);
    $this->setRawContent($rendered);
    $this->removeWhiteSpace();
    $pattern = $this->dateFormat->getPattern();
    static::assertEquals(\sprintf('Same day range: %s-%s', $start->format($pattern), $endSameDay->format($pattern)), $this->getTextContent());
  }

  /**
   * Tests setting summary occurrence sample for different day.
   */
  public function testFormatterSettingsSummarySampleOccurrenceDifferentDay(): void {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $efm */
    $efm = $this->container->get('entity_field.manager');
    $definitions = $efm->getBaseFieldDefinitions('dr_entity_test');

    $dateFormatId = $this->dateFormat->id();
    $options = [
      'configuration' => [
        'label' => 'above',
        'type' => 'date_recur_basic_formatter',
        'settings' => [
          'format_type' => $dateFormatId,
          'occurrence_format_type' => $dateFormatId,
          'same_end_date_format_type' => $dateFormatId,
          'interpreter' => $this->interpreter->id(),
          'count_per_item' => FALSE,
          'separator' => '-',
          'show_next' => 10,
        ],
      ],
      'field_definition' => $definitions['dr'],
      'prepare' => TRUE,
      'view_mode' => 'full',
    ];

    /** @var \Drupal\Core\Field\FormatterPluginManager $fieldFormatterManager */
    $fieldFormatterManager = $this->container->get('plugin.manager.field.formatter');
    $instance = $fieldFormatterManager->getInstance($options);
    $summary = $instance->settingsSummary();

    $start = new \DateTime('today 9am');
    $endDifferentDay = clone $start;
    $endDifferentDay->setTime(17, 0, 0);
    $endDifferentDay->modify('+1 day');

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $rendered = $renderer->renderRoot($summary['sample_different_day']);
    // Remove newlines from Twig templates.
    $rendered = \preg_replace('/\n/', '', (string) $rendered);
    $this->setRawContent($rendered);
    $this->removeWhiteSpace();
    $pattern = $this->dateFormat->getPattern();
    static::assertEquals(\sprintf('Different day range: %s-%s', $start->format($pattern), $endDifferentDay->format($pattern)), $this->getTextContent());
  }

  /**
   * Tests setting summary occurrence sample for different day.
   */
  public function testFormatterDependencies(): void {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $efm */
    $efm = $this->container->get('entity_field.manager');
    $definitions = $efm->getBaseFieldDefinitions('dr_entity_test');

    $dateFormat1 = DateFormat::create(['id' => $this->randomMachineName()]);
    $dateFormat1->save();
    $dateFormat2 = DateFormat::create(['id' => $this->randomMachineName()]);
    $dateFormat2->save();
    $dateFormat3 = DateFormat::create(['id' => $this->randomMachineName()]);
    $dateFormat3->save();
    $options = [
      'configuration' => [
        'label' => 'above',
        'type' => 'date_recur_basic_formatter',
        'settings' => [
          'format_type' => $dateFormat1->id(),
          'occurrence_format_type' => $dateFormat2->id(),
          'same_end_date_format_type' => $dateFormat3->id(),
          'interpreter' => $this->interpreter->id(),
          'count_per_item' => FALSE,
          'separator' => '-',
          'show_next' => 10,
        ],
      ],
      'field_definition' => $definitions['dr'],
      'prepare' => TRUE,
      'view_mode' => 'full',
    ];

    /** @var \Drupal\Core\Field\FormatterPluginManager $fieldFormatterManager */
    $fieldFormatterManager = $this->container->get('plugin.manager.field.formatter');
    /** @var \Drupal\date_recur\Plugin\Field\FieldFormatter\DateRecurBasicFormatter $instance */
    $instance = $fieldFormatterManager->getInstance($options);
    $expectedConfigDependencies = [
      'core.date_format.' . $dateFormat1->id(),
      'core.date_format.' . $dateFormat2->id(),
      'core.date_format.' . $dateFormat3->id(),
      'date_recur.interpreter.' . $this->interpreter->id(),
    ];
    \sort($expectedConfigDependencies);
    static::assertEquals($expectedConfigDependencies, $instance->calculateDependencies()['config']);
  }

  /**
   * Tests formatter output for same start/end date.
   *
   * It doesn't matter which time zone the data is in, we only check same date
   * for the current logged in user.
   */
  public function testFormatterSameDay(): void {
    $user = User::create([
      'uid' => 2,
      // UTC+10.
      'timezone' => 'Pacific/Port_Moresby',
    ]);
    $this->container->get('current_user')->setAccount($user);

    $dateFormatSameDate = DateFormat::create([
      'id' => $this->randomMachineName(),
      'pattern' => '\s\a\m\e \d\a\t\e',
    ]);
    $dateFormatSameDate->save();
    $settings = [
      'format_type' => $this->dateFormat->id(),
      'occurrence_format_type' => $this->dateFormat->id(),
      'same_end_date_format_type' => $dateFormatSameDate->id(),
      'interpreter' => $this->interpreter->id(),
    ];
    $entity = DrEntityTestBasic::create();
    $entity->dr->setValue([
      // 10pm-9:59:59pm HK time.
      'value' => '2014-06-14T14:00:00',
      'end_value' => '2014-06-15T13:59:59',
      'rrule' => '',
      'infinite' => '0',
      // HK is UTC+8.
      'timezone' => 'Asia/Hong_Kong',
    ]);
    $this->renderFormatterSettings($entity, $settings);

    $dates = $this->cssSelect('time');
    static::assertCount(2, $dates);

    // First time is start date.
    static::assertEquals('Sun, 15 Jun 2014 00:00:00 +1000', (string) $dates[0]);

    // Second time is end date.
    static::assertEquals('same date', (string) $dates[1]);
  }

  /**
   * Renders the date recur formatter and sets the HTML ready to be asserted.
   *
   * @param \Drupal\date_recur_entity_test\Entity\DrEntityTestBasic $entity
   *   A date recur test entity.
   * @param array $settings
   *   Settings for date recur basic formatter.
   */
  protected function renderFormatterSettings(DrEntityTestBasic $entity, array $settings): void {
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList $field */
    $field = $entity->dr;
    $build = $field->view([
      'type' => 'date_recur_basic_formatter',
      'settings' => $settings,
    ]);
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get(RendererInterface::class);
    $this->setRawContent((string) $renderer->renderInIsolation($build));
  }

  /**
   * Creates a recurring entity.
   *
   * @return \Drupal\date_recur_entity_test\Entity\DrEntityTestBasic
   *   A recurring entity.
   */
  protected function createRecurringEntity(): DrEntityTestBasic {
    $entity = DrEntityTestBasic::create();
    $entity->dr->setValue([
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
      'infinite' => '1',
      'timezone' => 'Australia/Sydney',
    ]);
    return $entity;
  }

}
