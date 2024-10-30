<?php

namespace Drupal\consumer_image_styles\Normalizer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Url;
use Drupal\consumer_image_styles\ImageStylesProvider;
use Drupal\consumer_image_styles\ImageStylesProviderInterface;
use Drupal\consumers\Entity\Consumer;
use Drupal\consumers\MissingConsumer;
use Drupal\consumers\Negotiator;
use Drupal\image\ImageStyleInterface;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Custom normalizer that add the derivatives to image entities.
 */
class LinkCollectionNormalizer implements NormalizerInterface {

  /**
   * Consumer Negotiator service.
   *
   * @var \Drupal\consumers\Negotiator
   */
  protected $consumerNegotiator;

  /**
   * Image Styles provider service.
   *
   * @var \Drupal\consumer_image_styles\ImageStylesProviderInterface
   */
  protected $imageStylesProvider;

  /**
   * The Image Factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The Normalizer Serializer.
   *
   * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface
   */
  protected $inner;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a LinkCollectionNormalizer object.
   *
   * @param \Symfony\Component\Serializer\Normalizer\NormalizerInterface $inner
   *   The decorated service.
   * @param \Drupal\consumers\Negotiator $consumer_negotiator
   *   The consumer negotiator.
   * @param \Drupal\consumer_image_styles\ImageStylesProviderInterface $imageStylesProvider
   *   Image styles utility.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(NormalizerInterface $inner, Negotiator $consumer_negotiator, ImageStylesProviderInterface $imageStylesProvider, ImageFactory $image_factory, RequestStack $request_stack, FileUrlGeneratorInterface $file_url_generator) {
    $this->inner = $inner;
    $this->consumerNegotiator = $consumer_negotiator;
    $this->imageStylesProvider = $imageStylesProvider;
    $this->imageFactory = $image_factory;
    $this->requestStack = $request_stack;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL, $context = []): bool {
    return $this->inner->supportsNormalization($data, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($link_collection, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    assert($link_collection instanceof LinkCollection);
    if ($this->decorationApplies($link_collection) && ($consumer = $this->getConsumer())) {
      $variant_links = $this->buildVariantLinks($link_collection->getContext(), $consumer);
      $normalization = $this->inner->normalize(
        LinkCollection::merge($link_collection, $variant_links),
        $format,
        $context
      );
      return static::addLinkRels($normalization, $variant_links)
        ->withCacheableDependency($consumer);
    }
    return $this->inner->normalize($link_collection, $format, $context);
  }

  /**
   * Builds links for the consumer variant.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $resource_object
   *   The Resource Object.
   * @param \Drupal\consumers\Entity\Consumer $consumer
   *   The attached consumer entity.
   *
   * @return \Drupal\jsonapi\JsonApiResource\LinkCollection
   *   The variant links.
   */
  protected function buildVariantLinks(ResourceObject $resource_object, Consumer $consumer) {
    // Prepare some utils.
    $uri = $resource_object->getField($resource_object->getResourceType()->getPublicName('uri'))->value;
    // Generate derivatives only for the found ones.
    $image_styles = $this->imageStylesProvider->loadStyles($consumer);
    return array_reduce($image_styles, function (LinkCollection $decorated, ImageStyleInterface $image_style) use ($uri) {
      $link = $this->imageStylesProvider->buildDerivativeLink($uri, $image_style);
      $dimensions = [];
      if (isset($link['meta']['width'])) {
        $dimensions['width'] = $link['meta']['width'];
      }
      if (isset($link['meta']['height'])) {
        $dimensions['width'] = $link['meta']['height'];
      }
      $variant_link = new Link(
        CacheableMetadata::createFromObject($image_style),
        Url::fromUri($link['href']),
        ImageStylesProvider::DERIVATIVE_LINK_REL,
        // Target attributes can only be strings, but dimensions are links.
        array_map(function (?int $dimension): string {
          return sprintf('%d', $dimension);
        }, $dimensions)
      );
      return $decorated->withLink($image_style->id(), $variant_link);
    }, (new LinkCollection([]))->withContext($resource_object));
  }

  /**
   * Whether this decorator applies to the current data.
   *
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $link_collection
   *   The link collection to be normalized.
   *
   * @return bool
   *   TRUE if the link collection belongs to an image file resource object,
   *   FALSE otherwise.
   */
  protected function decorationApplies(LinkCollection $link_collection) {
    $link_context = $link_collection->getContext();
    if (!$link_context instanceof ResourceObject) {
      return FALSE;
    }
    $resource_type = $link_context->getResourceType();
    if ($resource_type->getEntityTypeId() !== 'file') {
      return FALSE;
    }
    $uriField = $link_context->getField($resource_type->getPublicName('uri'));
    return $uriField && in_array(
        mb_strtolower(pathinfo($uriField->value, PATHINFO_EXTENSION)),
        $this->imageFactory->getSupportedExtensions()
      );
  }

  /**
   * Gets the current consumer.
   *
   * @return \Drupal\consumers\Entity\Consumer
   *   The current consumer or NULL if one cannot be negotiated.
   */
  protected function getConsumer() {
    try {
      return $this->consumerNegotiator->negotiateFromRequest($this->requestStack->getCurrentRequest());
    }
    catch (MissingConsumer $e) {
      return NULL;
    }
  }

  /**
   * Adds the derivative link relation type to the normalized link collection.
   *
   * @param \Drupal\jsonapi\Normalizer\Value\CacheableNormalization $cacheable_normalization
   *   The cacheable normalization to which link relations need to be added.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $link_collection
   *   The un-normalized link collection.
   *
   * @return \Drupal\jsonapi\Normalizer\Value\CacheableNormalization
   *   The links normalization with meta.rel added.
   */
  protected static function addLinkRels(CacheableNormalization $cacheable_normalization, LinkCollection $link_collection) {
    $normalization = $cacheable_normalization->getNormalization();
    foreach ($normalization as $key => &$normalized_link) {
      $links = iterator_to_array($link_collection);
      if (isset($links[$key])) {
        $normalized_link['meta']['rel'] = array_reduce($links[$key], function (array $relations, Link $link) {
          $relations[] = $link->getLinkRelationType();
          return array_unique($relations);
        }, []);
      }
    }
    return new CacheableNormalization($cacheable_normalization, $normalization);
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      LinkCollection::class => TRUE,
    ];
  }

}
