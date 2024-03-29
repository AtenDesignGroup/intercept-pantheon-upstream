<?php

namespace Drupal\clientside_validation_jquery\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\File\FileSystemInterface;
use Drush\Drush;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Drush command file for clientside_validation_jquery module.
 *
 * It exposes drush commands to manage the required jquery validation library.
 */
class ClientsideValidationJqueryDrush extends DrushCommands {

  /**
   * Service for accessing filesystem.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * ClientsideValidationJqueryDrush Constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   Service for accessing filesystem.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * Show the status of library required by clientside validation jQuery module.
   *
   * @command clientside_validation_jquery:library-status
   * @aliases cvjls
   */
  public function libraryStatus() {
    $installed = file_exists(DRUPAL_ROOT . '/libraries/jquery-validation/dist/jquery.validate.min.js');
    $message = $installed ? 'installed' : 'not installed';
    $this->logger()->notice('Library required for clientside validation jQuery is {message}', ['message' => $message]);
  }

  /**
   * Download third party libraries required by this module.
   *
   * @command clientside_validation_jquery:library-download
   * @aliases cvjld
   */
  public function addLibrary() {
    // Check if library is already available.
    if (file_exists(DRUPAL_ROOT . '/libraries/jquery-validation')) {
      throw new \Exception('Library already downloaded, if you want to download again, please remove it first.');
    }

    // Download library.
    $tmp_location = $this->fileSystem->getTempDirectory();
    $download_url = 'https://github.com/jquery-validation/jquery-validation/archive/1.17.0.zip';
    $this->logger()->notice('Downloading {download_url}', ['download_url' => $download_url]);
    file_put_contents($tmp_location . '/jquery-validation.zip', fopen($download_url, 'r'));
    $path = $tmp_location . '/jquery-validation.zip';
    $destination = DRUPAL_ROOT . '/libraries';

    // Unzip the downloaded archive.
    $process = Drush::process(['unzip', $path, '-d', $destination]);
    $process->run();
    $return = $process->isSuccessful();

    if (!$return) {
      throw new \Exception(dt('Unable to extract !filename.' . PHP_EOL . implode(PHP_EOL, $process->getOutput()), ['!filename' => $path]));
    }

    // Name it properly (remove version number from directory).
    $fs = new Filesystem();
    $fs->rename(DRUPAL_ROOT . '/libraries/jquery-validation-1.17.0', DRUPAL_ROOT . '/libraries/jquery-validation', TRUE);

    // Flush all caches.
    drupal_flush_all_caches();
  }

  /**
   * Remove third party libraries required by this module.
   *
   * @command clientside_validation_jquery:library-remove
   * @aliases cvjlr
   */
  public function removeLibrary() {
    $this->logger()->notice('Beginning to remove libraries...');

    if (file_exists(DRUPAL_ROOT . '/libraries/jquery-validation')) {
      $this->logger()->notice('jQuery validation library removed.');
      $this->fileSystem->deleteRecursive(DRUPAL_ROOT . '/libraries/jquery-validation');
      drupal_flush_all_caches();
      return TRUE;
    }

    return FALSE;
  }

}
