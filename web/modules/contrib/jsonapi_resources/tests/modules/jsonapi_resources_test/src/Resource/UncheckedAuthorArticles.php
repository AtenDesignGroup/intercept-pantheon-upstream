<?php

namespace Drupal\jsonapi_resources_test\Resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi_resources\Resource\EntityQueryResourceBase;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Collection of a user's articles, served without per-entity access checks.
 *
 * Demonstrates the `$check_access = FALSE` opt-out: the entity query is
 * already gated to the requested user's own content, so the additional
 * per-entity access check on each row is redundant. Only safe because the
 * query bounds match the data the caller is allowed to see.
 *
 * @internal
 */
final class UncheckedAuthorArticles extends EntityQueryResourceBase {

  /**
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\user\UserInterface $user
   *   The user whose articles to return.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function process(Request $request, UserInterface $user): ResourceResponse {
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheContexts(['url.path']);

    $entity_type = $this->entityTypeManager->getDefinition('node');
    $entity_query = $this->getEntityQuery('node')
      ->accessCheck(FALSE)
      ->condition($entity_type->getKey('bundle'), 'article')
      ->condition($entity_type->getKey('status'), NodeInterface::PUBLISHED)
      ->condition($entity_type->getKey('uid'), $user->id());

    $paginator = $this->getPaginatorForRequest($request);
    $paginator->applyToQuery($entity_query, $cacheability);

    $data = $this->loadResourceObjectDataFromEntityQuery($entity_query, $cacheability, FALSE, FALSE);
    $pagination_links = $paginator->getPaginationLinks($entity_query, $cacheability);

    $response = $this->createJsonapiResponse($data, $request, 200, [], $pagination_links);
    $response->addCacheableDependency($cacheability);

    return $response;
  }

}
