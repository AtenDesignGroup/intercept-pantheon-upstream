<?php

namespace Drupal\jsonapi_resources_meta_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Registers the test impostor normalizer namespace.
 *
 * Mirrors \Drupal\jsonapi_resources\JsonapiResourcesServiceProvider so the
 * test's second resource object normalizer decorator can live in the
 * Drupal\jsonapi\Normalizer namespace that the JSON:API serializer requires.
 *
 * @see \Drupal\jsonapi\Serializer\Serializer::__construct()
 * @see https://www.drupal.org/project/jsonapi_resources/issues/3599417
 */
class JsonapiResourcesMetaTestServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if (!$container->hasDefinition('jsonapi_resources_meta_test.normalizer.resource_object')) {
      return;
    }

    $container_namespaces = $container->getParameter('container.namespaces');
    $container_modules = $container->getParameter('container.modules');

    $module_path = dirname($container_modules['jsonapi_resources_meta_test']['pathname']);
    $impostor_path = $module_path . '/src-impostor-normalizers';

    $container_namespaces['Drupal\jsonapi\Normalizer\ImpostorFrom\jsonapi_resources_meta_test'][] = $impostor_path;
    $container->setParameter('container.namespaces', $container_namespaces);

    $container->getDefinition('jsonapi_resources_meta_test.normalizer.resource_object')
      ->setFile($impostor_path . '/MetaShimResourceObjectNormalizer.php');
  }

}
