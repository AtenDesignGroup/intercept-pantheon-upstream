<?php

namespace Drupal\Tests\duration_field\Kernel;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the 'duration' form element.
 *
 * @group duration_field
 */
class DurationFormElementTest extends KernelTestBase implements FormInterface {

  /**
   * User for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'duration_field'];

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences', 'key_value_expire']);
    $this->installEntitySchema('user');
    \Drupal::service('router.builder')->rebuild();
    $this->testUser = User::create([
      'name' => 'foobar',
      'mail' => 'foobar@example.com',
    ]);
    $this->testUser->save();
    \Drupal::service('current_user')->setAccount($this->testUser);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'duration_form_element_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['full_duration'] = [
      '#type' => 'duration',
    ];
    $increments = [
      '5sec' => 5,
      '1min' => 60,
      '5min' => 60 * 5,
      '15min' => 60 * 15,
      '1hr' => 60 * 60,
      '2hr' => 60 * 60 * 2,
      '7hr' => 60 * 60 * 7,
      '1day' => 60 * 60 * 24,
      '3day' => 60 * 60 * 24 * 3,
    ];
    foreach ($increments as $id => $value) {
      $form["duration_date_increment_$id"] = [
        '#type' => 'duration',
        '#date_increment' => $value,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Tests that the duration form element works as expected.
   */
  public function testDurationElement() {
    $form_builder = $this->container->get('form_builder');

    // Try everything with a valid duration.
    $form_state = (new FormState())
      ->setValues([
        'full_duration' => [],
        'duration_date_increment_5sec' => ['s' => 20],
        'duration_date_increment_1min' => ['i' => 3],
        'duration_date_increment_5min' => ['i' => 10],
        'duration_date_increment_15min' => ['i' => 45],
        'duration_date_increment_1hr' => ['h' => 3],
        'duration_date_increment_2hr' => ['h' => 4],
        'duration_date_increment_7hr' => ['h' => 21],
        'duration_date_increment_1day' => ['d' => 2],
        'duration_date_increment_3day' => ['d' => 9],
      ]);
    $form_builder->submitForm($this, $form_state);
    // There should be no errors.
    $this->assertCount(0, $form_state->getErrors());

    $form = \Drupal::formBuilder()->getForm($this);
    $this->render($form);

    $expected_steps = [
      'duration_date_increment_5sec' => ['s' => 5],
      'duration_date_increment_1min' => ['i' => 1],
      'duration_date_increment_5min' => ['i' => 5],
      'duration_date_increment_15min' => ['i' => 15],
      'duration_date_increment_1hr' => ['h' => 1],
      'duration_date_increment_2hr' => ['h' => 2],
      'duration_date_increment_7hr' => ['h' => 7],
      'duration_date_increment_1day' => ['d' => 1],
      'duration_date_increment_3day' => ['d' => 3],
    ];
    foreach ($expected_steps as $element_id => $step_value) {
      foreach (['s', 'i', 'h', 'd', 'm', 'y'] as $sub_field) {
        $name = $element_id . '[' . $sub_field . ']';
        $expected_step = $step_value[$sub_field] ?? 1;
        $input = $this->xpath("//form//input[@name='$name']");
        $this->assertCount(1, $input, "Duration input $name should appear exactly once.");
        $actual_step = (integer) $input[0]->attributes()->{'step'};
        $this->assertEquals($expected_step, $actual_step, "Duration input $name should have the correct step value.");
      }
    }

    // Try again with invalid values.
    $form_state = (new FormState())
      ->setValues([
        'full_duration' => [],
        'duration_date_increment_5sec' => ['s' => 13],
        // 'duration_date_increment_1min' is always valid.
        'duration_date_increment_5min' => ['i' => 11],
        'duration_date_increment_15min' => ['i' => 47],
        // 'duration_date_increment_1hr' is always valid.
        'duration_date_increment_2hr' => ['h' => 5],
        'duration_date_increment_7hr' => ['h' => 20],
        // 'duration_date_increment_1day' is always valid.
        'duration_date_increment_3day' => ['d' => 4],
      ]);
    $form_builder->submitForm($this, $form_state);
    $errors = $form_state->getErrors();
    $expected_errors = [
      'duration_date_increment_5sec][s' => 'Seconds',
      'duration_date_increment_5min][i' => 'Minutes',
      'duration_date_increment_15min][i' => 'Minutes',
      'duration_date_increment_2hr][h' => 'Hours',
      'duration_date_increment_7hr][h' => 'Hours',
      'duration_date_increment_3day][d' => 'Days',
    ];
    $this->assertCount(count($expected_errors), $errors);
    foreach ($expected_errors as $field => $name) {
      $this->assertEqual($errors[$field], t('%name is not a valid number.', ['%name' => $name]));
    }
  }

}
