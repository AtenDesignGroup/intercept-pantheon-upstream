<?php

namespace Drupal\consumer_image_styles;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\consumers\Entity\Consumer;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\image\ImageStyleInterface;

/**
 * Class ImageStylesProvider.
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

  public function __construct(EntityTypeManagerInterface $entity_type_manager, ImageFactory $image_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->imageFactory = $image_factory;
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
  public function buildDerivativeLink($uri, ImageStyleInterface $image_style) {
    $image = $this->imageFactory->get($uri);
    $dimensions = [
      'width' => $image->getWidth(),
      'height' => $image->getHeight(),
    ];
    $image_style->transformDimensions($dimensions, $uri);
    return [
      'href' => file_create_url($image_style->buildUrl($uri)),
      'title' => $this->t('Image Style: @name', ['@name' => $image_style->label()]),
      'type' => $image->getMimeType(),
      'meta' => [
        'rel' => static::DERIVATIVE_LINK_REL,
      ] + $dimensions,
    ];
  }

  /**
   * {inheritdoc}
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
