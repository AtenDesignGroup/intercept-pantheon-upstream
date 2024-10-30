<?php

namespace Drupal\consumer_image_styles;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\consumers\Entity\Consumer;
use Drupal\file\Entity\File;
use Drupal\image\ImageStyleInterface;

/**
 * Image Styles Provider service.
 *
 * @package Drupal\consumer_image_styles
 */
class ImageStylesProvider implements ImageStylesProviderInterface {

  use StringTranslationTrait;

  const DERIVATIVE_LINK_REL = 'drupal://jsonapi/extensions/consumer_image_styles/links/relation-types/#derivative';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  private $imageFactory;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Stream Wrapper Manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  private StreamWrapperManagerInterface $streamWrapperManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   Image factory.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   Stream wrapper manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ImageFactory $image_factory,
    FileUrlGeneratorInterface $file_url_generator,
    ?StreamWrapperManagerInterface $stream_wrapper_manager = NULL,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->imageFactory = $image_factory;
    $this->fileUrlGenerator = $file_url_generator;
    if (!$stream_wrapper_manager) {
      @trigger_error(sprintf('Invoking %s without $stream_wrapper_manager is deprecated in consumer_image_styles:4.0.6 and is unsupported in consumer_image_styles:5.0.0. See https://www.drupal.org/project/consumer_image_styles/issues/3252023', __FUNCTION__), E_USER_DEPRECATED);
      // @phpstan-ignore-next-line as used on purpose.
      $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    }
    $this->streamWrapperManager = $stream_wrapper_manager;

  }

  /**
   * {@inheritdoc}
   */
  public function loadStyles(Consumer $consumer) {
    $consumer_config = $consumer->get('image_styles')->getValue();
    $image_style_ids = array_map(function ($field_value) {
      return $field_value['target_id'];
    }, $consumer_config);

    // Load image style entities in bulk.
    try {
      $image_styles = $this->entityTypeManager
        ->getStorage('image_style')
        ->loadMultiple($image_style_ids);
    }
    catch (PluginException $e) {
      $image_styles = [];
    }

    return $image_styles;
  }

  /**
   * {@inheritdoc}
   */
  public function buildDerivativeLink($uri, ImageStyleInterface $image_style, ?CacheableMetadata $cacheable_metadata = NULL) {
    if (is_null($cacheable_metadata)) {
      @trigger_error(sprintf('Calling %s without $cacheable_metadata is deprecated in consumer_image_styles:4.0.6 and is required in consumer_image_styles:5.0.0. See https://www.drupal.org/project/consumer_image_styles/issues/3252023', __METHOD__), E_USER_DEPRECATED);
      $cacheable_metadata = new CacheableMetadata();
    }
    $cacheable_metadata->addCacheableDependency($image_style);
    $info = [
      'href' => $this->fileUrlGenerator->generateAbsoluteString($image_style->buildUrl($uri)),
      'title' => $this->t('Image Style: @name', ['@name' => $image_style->label()]),
      'meta' => ['rel' => static::DERIVATIVE_LINK_REL],
      // This is json:api 1.1 compatible.
      'rel' => static::DERIVATIVE_LINK_REL,
    ];
    // Sites with external images cannot afford to download the image to the
    // webserver in order to inspect the image dimensions.
    if ($this->streamWrapperManager->getViaScheme(StreamWrapperManager::getScheme($uri))->getType() & ~StreamWrapperInterface::LOCAL_NORMAL) {
      return $info;
    }
    $image = $this->imageFactory->get($uri);
    $dimensions = [
      'width' => $image->getWidth(),
      'height' => $image->getHeight(),
    ];
    $image_style->transformDimensions($dimensions, $uri);
    $info['meta'] += $dimensions;
    $info['type'] = $image->getMimeType();

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function entityIsImage(EntityInterface $entity) {
    if (!$entity instanceof File) {
      return FALSE;
    }

    return in_array(
      mb_strtolower(pathinfo($entity->getFileUri(), PATHINFO_EXTENSION)),
      $this->imageFactory->getSupportedExtensions()
    );
  }

}
