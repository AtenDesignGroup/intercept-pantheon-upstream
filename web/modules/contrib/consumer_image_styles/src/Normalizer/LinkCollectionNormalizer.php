<?php

namespace Drupal\consumer_image_styles\Normalizer;

use Drupal\consumer_image_styles\ImageStylesProvider;
use Drupal\consumers\Entity\Consumer;
use Drupal\consumers\MissingConsumer;
use Drupal\consumers\Negotiator;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
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
   * @var \Drupal\consumers\Negotiator
   */
  protected $consumerNegotiator;

  /**
   * @var \Drupal\consumer_image_styles\ImageStylesProvider
   */
  protected $imageStylesProvider;

  /**
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
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
   * Constructs a LinkCollectionNormalizer object.
   *
   * @param \Symfony\Component\Serializer\Normalizer\NormalizerInterface $inner
   *   The decorated service.
   * @param \Drupal\consumers\Negotiator $consumer_negotiator
   *   The consumer negotiator.
   * @param \Drupal\consumer_image_styles\ImageStylesProvider
   *   Image styles utility.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(NormalizerInterface $inner, Negotiator $consumer_negotiator, ImageStylesProvider $imageStylesProvider, ImageFactory $image_factory, RequestStack $request_stack) {
    $this->inner = $inner;
    $this->consumerNegotiator = $consumer_negotiator;
    $this->imageStylesProvider = $imageStylesProvider;
    $this->imageFactory = $image_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return $this->inner->supportsNormalization($data, $format);
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($link_collection, $format = NULL, array $context = []) {
    assert($link_collection instanceof LinkCollection);
    if ($this->decorationApplies($link_collection) && ($consumer = $this->getConsumer())) {
      $variant_links = $this->buildVariantLinks($link_collection->getContext(), $consumer);
      $normalization = $this->inner->normalize(LinkCollection::merge($link_collection, $variant_links), $format, $context);
      return static::addLinkRels($normalization, $variant_links);
    }
    return $this->inner->normalize($link_collection, $format, $context);
  }

  /**
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $resource_object
   * @param \Drupal\consumers\Entity\Consumer
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
      $variant_link = new Link(CacheableMetadata::createFromObject($image_style), Url::fromUri(file_create_url($image_style->buildUrl($uri))), [ImageStylesProvider::DERIVATIVE_LINK_REL]);
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
    return $this->imageFactory->get($link_context->getField($resource_type->getPublicName('uri'))->value)->isValid();
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
          return array_unique(array_merge($relations, $link->getLinkRelationTypes()));
        }, []);
      }
    }
    return new CacheableNormalization($cacheable_normalization, $normalization);
  }

}
