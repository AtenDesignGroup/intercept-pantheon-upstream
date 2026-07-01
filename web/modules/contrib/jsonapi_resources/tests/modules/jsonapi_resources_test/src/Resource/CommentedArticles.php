<?php

declare(strict_types=1);

namespace Drupal\jsonapi_resources_test\Resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi_resources\Resource\EntityQueryResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns articles ordered by comment count, joined to the comment stats table.
 *
 * Exercises the SelectInterface path: the JOIN against
 * `comment_entity_statistics` is something the entity query API cannot
 * express, which is the whole motivation for #3132728.
 *
 * @internal
 */
final class CommentedArticles extends EntityQueryResourceBase implements ContainerInjectionInterface {

  public function __construct(protected Connection $database) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self($container->get('database'));
  }

  /**
   * Process the resource request.
   */
  public function process(Request $request): ResourceResponse {
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheContexts(['url.query_args:page']);
    $cacheability->addCacheTags(['node_list:article', 'comment_list']);

    $query = $this->database->select('node_field_data', 'n');
    $query->join('comment_entity_statistics', 'c', "[c].[entity_id] = [n].[nid] AND [c].[entity_type] = 'node'");
    $query->fields('n', ['nid']);
    $query->condition('n.type', 'article');
    $query->condition('n.status', 1);
    $query->condition('c.comment_count', 0, '>');
    $query->orderBy('c.comment_count', 'DESC');
    $query->orderBy('n.nid', 'ASC');

    $paginator = $this->getPaginatorForRequest($request);
    $paginator->applyToQuery($query, $cacheability);

    $data = $this->loadResourceObjectDataFromSelectQuery('node', $query, $cacheability, 'nid');

    // Opt into meta.count and the `last` link, both of which run the select
    // count query — the SelectInterface total must reflect every match, not
    // just the current page.
    $meta = $this->buildCountMeta($paginator, $query, $cacheability);
    $pagination_links = $paginator->getPaginationLinks($query, $cacheability, TRUE);

    $response = $this->createJsonapiResponse($data, $request, 200, [], $pagination_links, $meta);
    $response->addCacheableDependency($cacheability);

    return $response;
  }

}
