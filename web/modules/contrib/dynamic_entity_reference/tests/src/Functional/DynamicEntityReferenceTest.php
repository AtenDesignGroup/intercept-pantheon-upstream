<?php

namespace Drupal\Tests\dynamic_entity_reference\Functional;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Ensures that Dynamic Entity References field works correctly.
 *
 * @group dynamic_entity_reference
 */
class DynamicEntityReferenceTest extends BrowserTestBase {

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The another user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $anotherUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field_ui',
    'dynamic_entity_reference',
    'entity_test',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'access administration pages',
    'view test entity',
    'administer entity_test fields',
    'administer entity_test content',
    'access user profiles',
  ];

  /**
   * List of excluded entity IDs.
   *
   * @var array
   */
  protected $excludedEntities = [
    'user',
    'file',
    'path_alias',
    'entity_test_label',
    'entity_test_no_id',
    'entity_test_no_bundle',
    'entity_test_string_id',
    'entity_test_computed_field',
    'entity_test_map_field',
    'entity_test_no_bundle_with_label',
    'entity_test_unique_constraint',
    'entity_test_uuid_id',
  ];

  /**
   * List of test entity IDs with bundle entities.
   *
   * @var array
   */
  protected $bundleAbleEntities = [
    'entity_test_with_bundle' => 'entity_test_bundle',
    'entity_test_mul_with_bundle' => 'entity_test_mul_bundle',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Sets the test up.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->anotherUser = $this->drupalCreateUser();
    $entity_type_manager = \Drupal::entityTypeManager();

    // Add entity bundles for entity with bundles.
    foreach ($this->bundleAbleEntities as $entity_id => $bundle_able_entity_id) {
      $entity_type_manager->getStorage($bundle_able_entity_id)
        ->create([
          'id' => $bundle_able_entity_id,
          'label' => "$entity_id Bundle",
          'description' => "Bundle for $entity_id",
        ])
        ->save();
    }
  }

  /**
   * Tests field settings of dynamic entity reference field.
   */
  public function testFieldSettings() {
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->adminUser);
    // Add a new dynamic entity reference field.
    $this->drupalGet('entity_test/structure/entity_test/fields/add-field');
    $edit = [];
    $excluded_entity_type_ids = $this->excludedEntities;
    $bundled_entities_type_ids = $this->bundleAbleEntities;
    $labels = $this->container->get('entity_type.repository')->getEntityTypeLabels(TRUE);
    foreach ($labels[(string) t('Content', [], ['context' => 'Entity type group'])] as $entity_type_id => $entity_type_label) {
      if (!in_array($entity_type_id, $excluded_entity_type_ids, TRUE)) {
        if (!in_array($entity_type_id, array_keys($bundled_entities_type_ids, TRUE))) {
          $edit["settings[$entity_type_id][handler_settings][target_bundles][$entity_type_id]"] = TRUE;
        }
        else {
          $edit["settings[$entity_type_id][handler_settings][target_bundles][{$bundled_entities_type_ids[$entity_type_id]}]"] = TRUE;
        }
      }
    }
    $additions = [];
    if (version_compare(\Drupal::VERSION, '10.2-dev', '>=')) {
      $this->submitForm([
        'new_storage_type' => 'reference',
      ], 'Continue');
      $this->submitForm([
        'group_field_options_wrapper' => 'dynamic_entity_reference',
        'label' => 'Foobar',
        'field_name' => 'foobar',
      ], 'Continue');
      $field_name = 'field_storage[subform][settings][entity_type_ids][]';
      $button_label = 'Save settings';
      $cardinality_field = 'field_storage[subform][cardinality]';
      $additions += $edit;
      $edit_path = 'entity_test/structure/entity_test/fields/entity_test.entity_test.field_foobar';
      $exclude_field_name = 'field_storage[subform][settings][exclude_entity_types]';
    }
    else {
      $edit_path = 'entity_test/structure/entity_test/fields/entity_test.entity_test.field_foobar/storage';
      $this->submitForm([
        'label' => 'Foobar',
        'field_name' => 'foobar',
        'new_storage_type' => 'dynamic_entity_reference',
      ], 'Save and continue');
      $field_name = 'settings[entity_type_ids][]';
      $button_label = 'Save field settings';
      $cardinality_field = 'cardinality';
      $exclude_field_name = 'settings[exclude_entity_types]';
    }
    $assert_session->optionNotExists($field_name, 'settings[entity_test_no_id][handler_settings][target_bundles][entity_test_no_id]');
    $assert_session->optionNotExists($field_name, 'settings[entity_test_no_id][handler_settings][target_bundles][entity_test_string_id]');
    $assert_session->fieldExists($cardinality_field)->setValue(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $assert_session->fieldExists($field_name)->setValue(['user', 'entity_test_label']);
    $assert_session->buttonExists('Update settings')->click();
    $assert_session->fieldExists('default_value_input[field_foobar][0][target_type]');
    $assert_session->optionExists('default_value_input[field_foobar][0][target_type]', 'entity_test');
    $assert_session->optionNotExists('default_value_input[field_foobar][0][target_type]', 'user');
    $this->submitForm($additions, $button_label);

    $assert_session->pageTextContains('Saved Foobar configuration');
    $excluded_entity_type_ids = FieldStorageConfig::loadByName('entity_test', 'field_foobar')
      ->getSetting('entity_type_ids');
    $this->assertNotNull($excluded_entity_type_ids);
    $excluded = array_keys($excluded_entity_type_ids);
    sort($excluded);
    $this->assertEquals($excluded, [
      'entity_test_label',
      'user',
    ]);
    // Check the include entity settings.
    $this->drupalGet($edit_path);
    $this->submitForm([
      $cardinality_field => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      $exclude_field_name => FALSE,
      $field_name => [],
    ], $button_label);
    $assert_session->pageTextContains('Select at least one entity type ID.');
    $options = array_filter(array_keys($labels[(string) t('Content', [], ['context' => 'Entity type group'])]), function ($entity_type_id) {
      return DynamicEntityReferenceItem::entityHasIntegerId($entity_type_id);
    });
    $this->submitForm([
      $cardinality_field => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      $exclude_field_name => TRUE,
      $field_name => $options,
    ], $button_label);
    $assert_session->pageTextContains('Select at least one entity type ID.');
    $this->submitForm([
      $cardinality_field => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      $exclude_field_name => FALSE,
      $field_name => ['user', 'entity_test_label'],
    ], $button_label);
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.field_foobar');
    $assert_session->fieldExists('default_value_input[field_foobar][0][target_type]');
    $assert_session->optionNotExists('default_value_input[field_foobar][0][target_type]', 'entity_test');
    $assert_session->optionExists('default_value_input[field_foobar][0][target_type]', 'user');
    $edit = [
      'settings[entity_test_label][handler_settings][target_bundles][entity_test_label]' => 'entity_test_label',
    ];
    $this->submitForm($edit, 'Save settings');
    $assert_session->pageTextContains('Saved Foobar configuration');
    $excluded_entity_type_ids = FieldStorageConfig::loadByName('entity_test', 'field_foobar')
      ->getSetting('entity_type_ids');
    $this->assertNotNull($excluded_entity_type_ids);
    $excluded = array_keys($excluded_entity_type_ids);
    sort($excluded);
    $this->assertSame($excluded, [
      'entity_test_label',
      'user',
    ]);
    // Check the default settings.
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.field_foobar');
    $this->submitForm([
      'set_default_value' => '1',
      'default_value_input[field_foobar][0][target_type]' => 'user',
      'default_value_input[field_foobar][0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
    ], 'Save settings');

    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', 'field_foobar')->toArray();
    $this->assertEquals($field_config['default_value']['0'], [
      'target_type' => 'user',
      'target_uuid' => $this->adminUser->uuid(),
    ]);
  }

  /**
   * Tests adding and editing values using dynamic entity reference.
   */
  public function testDynamicEntityReference() {
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->adminUser);
    // Add a new dynamic entity reference field.
    $this->drupalGet('entity_test/structure/entity_test/fields/add-field');
    if (version_compare(\Drupal::VERSION, '10.2-dev', '>=')) {
      $this->submitForm([
        'new_storage_type' => 'reference',
      ], 'Continue');
      $this->submitForm([
        'group_field_options_wrapper' => 'dynamic_entity_reference',
        'label' => 'Foobar',
        'field_name' => 'foobar',
      ], 'Continue');
      $field_name = 'field_storage[subform][settings][entity_type_ids][]';
      $button_label = 'Save settings';
      $cardinality_field = 'field_storage[subform][cardinality]';
    }
    else {
      $this->submitForm([
        'label' => 'Foobar',
        'field_name' => 'foobar',
        'new_storage_type' => 'dynamic_entity_reference',
      ], 'Save and continue');
      $field_name = 'settings[entity_type_ids][]';
      $button_label = 'Save field settings';
      $cardinality_field = 'cardinality';
    }
    $assert_session->optionNotExists($field_name, 'settings[entity_test_no_id][handler_settings][target_bundles][entity_test_no_id]');
    $assert_session->optionNotExists($field_name, 'settings[entity_test_no_id][handler_settings][target_bundles][entity_test_string_id]');
    $this->submitForm([
      $cardinality_field => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ], $button_label);
    $assert_session->fieldNotExists('settings[entity_test_no_id][handler_settings][target_bundles][entity_test_no_id]');
    $assert_session->fieldNotExists('settings[entity_test_string_id][handler_settings][target_bundles][entity_test_string_id]');
    $labels = $this->container->get('entity_type.repository')->getEntityTypeLabels(TRUE);
    $edit = [];
    $excluded_entity_type_ids = array_combine($this->excludedEntities, $this->excludedEntities);
    unset($excluded_entity_type_ids['entity_test_label']);
    $bundled_entities_type_ids = $this->bundleAbleEntities;
    foreach ($labels[(string) t('Content', [], ['context' => 'Entity type group'])] as $entity_type_id => $entity_type_label) {
      if (!in_array($entity_type_id, $excluded_entity_type_ids, TRUE)) {
        if (!in_array($entity_type_id, array_keys($bundled_entities_type_ids, TRUE))) {
          $edit["settings[$entity_type_id][handler_settings][target_bundles][$entity_type_id]"] = TRUE;
        }
        else {
          $edit["settings[$entity_type_id][handler_settings][target_bundles][{$bundled_entities_type_ids[$entity_type_id]}]"] = TRUE;
        }
      }
    }
    $this->submitForm($edit, 'Save settings');
    $assert_session->pageTextContains('Saved Foobar configuration');
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Create some items to reference.
    $item1 = EntityTest::create([
      'name' => 'item1',
    ]);
    $item1->save();
    $item2 = EntityTest::create([
      'name' => 'item2',
    ]);
    $item2->save();

    // Test the new entity commenting inherits default.
    $this->drupalGet('entity_test/add');
    $assert_session->fieldExists('field_foobar[0][target_id]');
    $target_type = $assert_session->fieldExists('field_foobar[0][target_type]');

    // Ensure that the autocomplete path is correct.
    $input = $assert_session->fieldExists('field_foobar[0][target_id]');
    $selected = $target_type->getValue();
    $settings = FieldConfig::loadByName('entity_test', 'entity_test', 'field_foobar')->getSettings();
    $selection_settings = $settings[$selected]['handler_settings'] ?: [];
    $selection_settings += [
      'match_operator' => 'CONTAINS',
      'match_limit' => 10,
    ];
    $data = serialize($selection_settings) . $selected . $settings[$selected]['handler'];
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());
    $expected_autocomplete_path = Url::fromRoute('system.entity_autocomplete', [
      'target_type' => $selected,
      'selection_handler' => $settings[$selected]['handler'],
      'selection_settings_key' => $selection_settings_key,
    ])->toString();
    $this->assertStringContainsString($input->getAttribute('data-autocomplete-path'), $expected_autocomplete_path);

    // Add some extra dynamic entity reference fields.
    $this->getSession()->getPage()->findButton('Add another item')->click();
    $this->getSession()->getPage()->findButton('Add another item')->click();

    $edit = [
      'field_foobar[0][target_id]' => $this->anotherUser->label() . ' (' . $this->anotherUser->id() . ')',
      'field_foobar[0][target_type]' => 'user',
      // Ensure that an exact match on a unique label is accepted.
      'field_foobar[1][target_id]' => 'item1',
      'field_foobar[1][target_type]' => 'entity_test',
      'field_foobar[2][target_id]' => 'item2 (' . $item2->id() . ')',
      'field_foobar[2][target_type]' => 'entity_test',
      'name[0][value]' => 'Barfoo',
      'user_id[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
    ];

    $this->submitForm($edit, 'Save');
    $entities = \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->loadByProperties(['name' => 'Barfoo']);
    $this->assertCount(1, $entities, 'Entity was saved');
    $entity = reset($entities);
    $this->drupalGet('entity_test/' . $entity->id());
    $assert_session->pageTextContains('Barfoo');
    $assert_session->pageTextContains($this->anotherUser->label());
    $assert_session->pageTextContains('item1');
    $assert_session->pageTextContains('item2');

    $this->assertCount(3, $entity->field_foobar, 'Three items in field');
    $this->assertEquals($entity->field_foobar[0]->entity->label(), $this->anotherUser->label());
    $this->assertEquals($entity->field_foobar[1]->entity->label(), 'item1');
    $this->assertEquals($entity->field_foobar[2]->entity->label(), 'item2');

    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');

    // Ensure that the autocomplete path is correct.
    $expected_entity_types = [
      '0' => 'user',
      '1' => 'entity_test',
      '2' => 'entity_test',
    ];
    foreach ($expected_entity_types as $index => $expected_entity_type) {
      $selection_settings = $settings[$expected_entity_type]['handler_settings'] ?: [];
      $selection_settings += [
        'match_operator' => 'CONTAINS',
        'match_limit' => 10,
      ];
      $data = serialize($selection_settings) . $expected_entity_type . $settings[$expected_entity_type]['handler'];
      $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());
      $input = $assert_session->fieldExists('field_foobar[' . $index . '][target_id]');
      $expected_autocomplete_path = Url::fromRoute('system.entity_autocomplete', [
        'target_type' => $expected_entity_type,
        'selection_handler' => $settings[$expected_entity_type]['handler'],
        'selection_settings_key' => $selection_settings_key,
      ])->toString();
      $this->assertStringContainsString($input->getAttribute('data-autocomplete-path'), $expected_autocomplete_path);
    }

    $edit = [
      'name[0][value]' => 'Bazbar',
      // Remove one child.
      'field_foobar[2][target_id]' => '',
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('entity_test/' . $entity->id());
    $assert_session->pageTextContains('Bazbar');
    // Reload entity.
    \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->resetCache([$entity->id()]);
    $entity = EntityTest::load($entity->id());
    $this->assertCount(2, $entity->field_foobar, 'Two values in field');

    // Create two entities with the same label.
    $labels = [];
    $duplicates = [];
    for ($i = 0; $i < 2; $i++) {
      $duplicates[$i] = EntityTest::create([
        'name' => 'duplicate label',
      ]);
      $duplicates[$i]->save();
      $labels[$i] = $duplicates[$i]->label() . ' (' . $duplicates[$i]->id() . ')';
    }

    // Now try to submit and just specify the label.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $edit = [
      'field_foobar[1][target_id]' => 'duplicate label',
    ];
    $this->submitForm($edit, 'Save');

    // We don't know the order in which the entities will be listed, so just
    // assert parts and make sure both are shown.
    $assert_session->responseContains('Multiple test entity entities match this reference;');
    $assert_session->responseContains($labels[0]);
    $assert_session->responseContains($labels[1]);

    // Create a few more to trigger the case where there are more than 5
    // matching results.
    for ($i = 2; $i < 7; $i++) {
      $duplicates[$i] = EntityTest::create([
        'name' => 'duplicate label',
      ]);
      $duplicates[$i]->save();
      $labels[$i] = $duplicates[$i]->label() . ' (' . $duplicates[$i]->id() . ')';
    }

    // Submit again with the same values.
    $this->submitForm($edit, 'Save');

    // We don't know which id it will display, so just assert a part of the
    // error.
    $assert_session->pageTextContains('Many test entity entities are called duplicate label. Specify the one you want by appending the id in parentheses');

    // Submit with a label that does not match anything.
    // Now try to submit and just specify the label.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $edit = [
      'field_foobar[1][target_id]' => 'does not exist',
    ];
    $this->submitForm($edit, 'Save');

    $assert_session->pageTextContains('There are no test entity entities matching "does not exist".');

    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $edit = [
      'name[0][value]' => 'Bazbar',
      // Reference itself.
      'field_foobar[1][target_id]' => 'Bazbar (' . $entity->id() . ')',
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('entity_test/' . $entity->id());
    $assert_session->pageTextContains('Bazbar');
    // Reload entity.
    \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->resetCache([$entity->id()]);
    $entity = EntityTest::load($entity->id());
    $this->assertEquals($entity->field_foobar[1]->entity->label(), 'Bazbar');
  }

  /**
   * Tests entity auto creation using dynamic entity reference.
   */
  public function testDynamicEntityReferenceAutoCreate() {
    $assert_session = $this->assertSession();
    \Drupal::service('module_installer')->install(['taxonomy']);
    $vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'vid' => mb_strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();
    $this->drupalLogin($this->adminUser);
    // Add a new dynamic entity reference field.
    $this->drupalGet('entity_test/structure/entity_test/fields/add-field');
    $edit = [
      'settings[taxonomy_term][handler_settings][target_bundles][' . $vocabulary->id() . ']' => $vocabulary->id(),
      'settings[taxonomy_term][handler_settings][auto_create]' => TRUE,
    ];
    $additional = [];
    if (version_compare(\Drupal::VERSION, '10.2-dev', '>=')) {
      $additional = $edit;
      $this->submitForm([
        'new_storage_type' => 'reference',
      ], 'Continue');
      $this->submitForm([
        'group_field_options_wrapper' => 'dynamic_entity_reference',
        'label' => 'Foobar',
        'field_name' => 'foobar',
      ], 'Continue');
      $field_name = 'field_storage[subform][settings][entity_type_ids][]';
      $button_label = 'Save settings';
      $cardinality_field = 'field_storage[subform][cardinality]';
      $exclude_field_name = 'field_storage[subform][settings][exclude_entity_types]';
    }
    else {
      $this->submitForm([
        'label' => 'Foobar',
        'field_name' => 'foobar',
        'new_storage_type' => 'dynamic_entity_reference',
      ], 'Save and continue');
      $field_name = 'settings[entity_type_ids][]';
      $button_label = 'Save field settings';
      $cardinality_field = 'cardinality';
      $exclude_field_name = 'settings[exclude_entity_types]';
    }
    $assert_session->fieldExists($field_name)->setValue(['taxonomy_term', 'user']);
    $assert_session->fieldExists($exclude_field_name)->uncheck();
    $assert_session->buttonExists('Update settings')->click();
    $this->submitForm([
      $cardinality_field => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ] + $additional, $button_label);
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.field_foobar');
    $this->submitForm($edit, $button_label);
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $this->drupalGet('entity_test/add');

    // Add some extra dynamic entity reference fields.
    $this->getSession()->getPage()->findButton('Add another item')->click();
    $this->getSession()->getPage()->findButton('Add another item')->click();
    $edit = [
      'field_foobar[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
      'field_foobar[0][target_type]' => 'user',
      // Add a non-existing term.
      'field_foobar[1][target_id]' => 'tag',
      'field_foobar[1][target_type]' => 'taxonomy_term',
      'field_foobar[2][target_id]' => $term->label() . ' (' . $term->id() . ')',
      'field_foobar[2][target_type]' => 'taxonomy_term',
      'name[0][value]' => 'Barfoo',
      'user_id[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
    ];

    $this->submitForm($edit, 'Save');

    $entities = \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->loadByProperties(['name' => 'Barfoo']);
    $this->assertCount(1, $entities, 'Entity was saved');
    $entity = reset($entities);

    $this->assertCount(3, $entity->field_foobar, 'Three items in field');
    $this->assertEquals($entity->field_foobar[0]->entity->label(), $this->adminUser->label());
    $this->assertEquals($entity->field_foobar[1]->entity->label(), 'tag');
    $this->assertEquals($entity->field_foobar[2]->entity->label(), $term->label());

    $this->drupalGet('entity_test/' . $entity->id());
    $assert_session->pageTextContains('Barfoo');
    $assert_session->pageTextContains($this->adminUser->label());
    $assert_session->pageTextContains('tag');
    $assert_session->pageTextContains($term->label());

  }

  /**
   * Tests node preview of dynamic entity reference field.
   */
  public function testNodePreview() {
    $assert_session = $this->assertSession();
    \Drupal::service('module_installer')->install(['taxonomy', 'node']);
    $this->drupalCreateContentType(['type' => 'article']);
    $this->permissions = [
      'access content',
      'administer nodes',
      'administer node fields',
      'create article content',
    ];
    $this->adminUser = $this->drupalCreateUser($this->permissions);

    $vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'vid' => mb_strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();

    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();

    $this->drupalLogin($this->adminUser);

    // Add a new dynamic entity reference field.
    $edit = [
      'settings[taxonomy_term][handler_settings][target_bundles][' . $vocabulary->id() . ']' => $vocabulary->id(),
      'settings[taxonomy_term][handler_settings][auto_create]' => TRUE,
    ];
    $additional = [];
    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    if (version_compare(\Drupal::VERSION, '10.2-dev', '>=')) {
      $this->submitForm([
        'new_storage_type' => 'reference',
      ], 'Continue');
      $this->submitForm([
        'group_field_options_wrapper' => 'dynamic_entity_reference',
        'label' => 'Foobar',
        'field_name' => 'der',
      ], 'Continue');
      $field_name = 'field_storage[subform][settings][entity_type_ids][]';
      $button_label = 'Save settings';
      $cardinality_field = 'field_storage[subform][cardinality]';
      $exclude_field_name = 'field_storage[subform][settings][exclude_entity_types]';
      $additional = $edit;
    }
    else {
      $this->submitForm([
        'label' => 'Foobar',
        'field_name' => 'der',
        'new_storage_type' => 'dynamic_entity_reference',
      ], 'Save and continue');
      $field_name = 'settings[entity_type_ids][]';
      $button_label = 'Save field settings';
      $cardinality_field = 'cardinality';
      $exclude_field_name = 'settings[exclude_entity_types]';
    }
    $assert_session->fieldExists($field_name)->setValue(['taxonomy_term', 'user']);
    $assert_session->fieldExists($exclude_field_name)->uncheck();
    $assert_session->buttonExists('Update settings')->click();
    $this->submitForm([
      $cardinality_field => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ] + $additional, $button_label);
    $this->drupalGet('/admin/structure/types/manage/article/fields/node.article.field_der');
    $this->submitForm($edit, $button_label);
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Test the node preview for existing term.
    $this->drupalGet('node/add/article');
    $assert_session->fieldExists('field_der[0][target_id]');
    $assert_session->fieldExists('field_der[0][target_type]');
    $title = $this->randomMachineName();
    $edit = [
      'field_der[0][target_id]' => $term->label() . ' (' . $term->id() . ')',
      'field_der[0][target_type]' => 'taxonomy_term',
      'title[0][value]' => $title,
      'uid[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
    ];

    $this->submitForm($edit, 'Preview');
    $assert_session->pageTextContains($title);
    $assert_session->pageTextContains($term->label());
    // Back to node add page.
    $this->clickLink('Back to content editing');
    $assert_session->fieldValueEquals('field_der[0][target_id]', $term->label() . ' (' . $term->id() . ')');

    // Test the node preview for new term.
    $this->drupalGet('node/add/article');
    $assert_session->fieldExists('field_der[0][target_id]');
    $assert_session->fieldExists('field_der[0][target_type]');

    $new_term = $this->randomMachineName();
    $edit = [
      'field_der[0][target_id]' => $new_term,
      'field_der[0][target_type]' => 'taxonomy_term',
      'title[0][value]' => $title,
      'uid[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
    ];

    $this->submitForm($edit, 'Preview');
    $assert_session->pageTextContains($title);
    $assert_session->pageTextContains($new_term);
    // Back to node add page.
    $this->clickLink('Back to content editing');
    $assert_session->fieldValueEquals('field_der[0][target_id]', $new_term);
  }

}
