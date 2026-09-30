<?php

namespace Drupal\Tests\video_embed_media\Kernel;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Tests\media\Kernel\MediaKernelTestBase;
use Drupal\media\Entity\Media;

/**
 * Test the thumbnail dimensions stored for video embed media.
 *
 * A width and height of 0 make the thumbnail invisible in the media library.
 *
 * @group video_embed_media
 */
class ThumbnailDimensionsTest extends MediaKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'video_embed_media',
    'video_embed_field',
  ];

  /**
   * Test that a saved video stores the size of its thumbnail file.
   */
  public function testSavedVideoStoresThumbnailFileSize() {
    // The URL matches no provider, so nothing is downloaded and the media
    // uses the default video icon, a 180x180 image, as its thumbnail.
    $icon_directory = 'public://media-icons/generic';
    $file_system = $this->container->get('file_system');
    $file_system->prepareDirectory($icon_directory, FileSystemInterface::CREATE_DIRECTORY);
    $module_path = $this->container->get('extension.list.module')->getPath('video_embed_media');
    $file_system->copy($module_path . '/images/icons/video.png', $icon_directory . '/video.png', FileExists::Replace);

    $media_type = $this->createMediaType('video_embed_field');
    $field_name = $media_type->getSource()->getSourceFieldDefinition($media_type)->getName();
    $media = Media::create([
      'bundle' => $media_type->id(),
      $field_name => [['value' => 'https://example.com/not-a-video']],
    ]);
    $media->save();

    $media = Media::load($media->id());
    $this->assertEquals(180, $media->get('thumbnail')->width);
    $this->assertEquals(180, $media->get('thumbnail')->height);
  }

}
