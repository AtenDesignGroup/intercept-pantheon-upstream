<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests ContentEntityNullStorage entity query support.
 *
 * @see \Drupal\Core\Entity\ContentEntityNullStorage
 * @see \Drupal\Core\Entity\Query\Null\Query
 *
 * @group Entity
 */
class ContentEntityNullStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'contact', 'user'];

  /**
   * Tests using entity query with ContentEntityNullStorage.
   *
   * @see \Drupal\Core\Entity\Query\Null\Query
   */
  public function testEntityQuery(): void {
    $this->assertSame(0, \Drupal::entityQuery('contact_message')->accessCheck(FALSE)->count()->execute(), 'Counting a null storage returns 0.');
    $this->assertSame([], \Drupal::entityQuery('contact_message')->accessCheck(FALSE)->execute(), 'Querying a null storage returns an empty array.');
    $this->assertSame([], \Drupal::entityQuery('contact_message')->accessCheck(FALSE)->condition('contact_form', 'test')->execute(), 'Querying a null storage returns an empty array and conditions are ignored.');
    $this->assertSame([], \Drupal::entityQueryAggregate('contact_message')->accessCheck(FALSE)->aggregate('name', 'AVG')->execute(), 'Aggregate querying a null storage returns an empty array');

  }

  /**
   * Tests deleting a contact form entity via a configuration import.
   *
   * @see \Drupal\Core\Entity\Event\BundleConfigImportValidate
   */
  public function testDeleteThroughImport(): void {
    $this->installConfig(['system']);
    $contact_form = ContactForm::create(['id' => 'test', 'label' => 'Test contact form']);
    $contact_form->save();

    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));

    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->container->get('config.storage.sync'),
      $this->container->get('config.storage')
    );
    $config_importer = new ConfigImporter(
      $storage_comparer->createChangelist(),
      $this->container->get('event_dispatcher'),
      $this->container->get('config.manager'),
      $this->container->get('lock'),
      $this->container->get('config.typed'),
      $this->container->get('module_handler'),
      $this->container->get('module_installer'),
      $this->container->get('theme_handler'),
      $this->container->get('string_translation'),
      $this->container->get('extension.list.module'),
      $this->container->get('extension.list.theme')
    );

    // Delete the contact message in sync.
    $sync = $this->container->get('config.storage.sync');
    $sync->delete($contact_form->getConfigDependencyName());

    // Import.
    $config_importer->reset()->import();
    $this->assertNull(ContactForm::load($contact_form->id()), 'The contact form has been deleted.');
  }

}
