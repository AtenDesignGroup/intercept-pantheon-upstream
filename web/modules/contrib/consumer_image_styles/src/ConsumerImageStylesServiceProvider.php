<?php

namespace Drupal\consumer_image_styles;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Replace the resource type repository for our own configurable version.
 */
class ConsumerImageStylesServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('serializer.normalizer.link_collection.jsonapi')) {
      $container->getDefinition('serializer.normalizer.link_collection.jsonapi')->clearTags();
    }
  }

}
