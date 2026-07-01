<?php

namespace Drupal\jsonapi_resources;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Service Provider for JSON:API Resources.
 */
class JsonapiResourcesServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container_namespaces = $container->getParameter('container.namespaces');
    $container_modules = $container->getParameter('container.modules');

    $jsonapi_resources_path = dirname($container_modules['jsonapi_resources']['pathname']);
    $jsonapi_impostor_path = $jsonapi_resources_path . '/src-impostor-normalizers';

    $container_namespaces['Drupal\jsonapi\Normalizer\ImpostorFrom\jsonapi_resources'][] = $jsonapi_impostor_path;
    $container->setParameter('container.namespaces', $container_namespaces);

    $container->getDefinition('jsonapi_resources.normalizer.resource_object')
      ->setFile($jsonapi_impostor_path . '/ResourceObjectNormalizerImpostor.php');
  }

}
