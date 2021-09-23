<?php

namespace Drupal\jsonapi_views\Resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi_resources\Resource\EntityResourceBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes a request for a collection of featured nodes.
 *
 * @internal
 */
final class ViewsResource extends EntityResourceBase {

  /**
   * Extracts exposed filter values from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   Key value pairs of exposed filters.
   */
  protected function getExposedFilterParams(Request $request) {
    $all_params = $request->query->all();
    $exposed_filter_params = isset($all_params['views-filter'])
      ? $all_params['views-filter']
      : [];
    return $exposed_filter_params;
  }

  /**
   * Extracts exposed sort values from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   Key value pairs of exposed sorts.
   */
  protected function getExposedSortParams(Request $request) {
    $all_params = $request->query->all();
    $exposed_sort_params = isset($all_params['views-sort'])
      ? $all_params['views-sort']
      : [];
    return $exposed_sort_params;
  }

  /**
   * Get views pager.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View executable.
   *
   * @return array
   *   Navigation links and total count.
   */
  public function getViewsPager(ViewExecutable $view) : array {
    $pager_links = new LinkCollection([]);

    if (!$view->pager) {
      return [$pager_links, count($view->result)];
    }

    /** @var \Drupal\Core\Pager\PagerManagerInterface $pager_manager */
    $pager_manager = \Drupal::service('pager.manager');
    $element = $view->pager->getPagerId();
    $pager = $pager_manager->getPager($element);

    if (!$pager) {
      return [$pager_links, count($view->result)];
    }

    $parameters = [];
    $current = $pager->getCurrentPage();
    $total = $pager->getTotalPages();

    // Add 'prev' link.
    if ($current > 0) {
      $options = [
        'query' => $pager_manager->getUpdatedParameters($parameters, $element, $current - 1),
      ];
      $prev = Url::fromRoute('<current>', [], $options);
      $pager_links = $pager_links->withLink('prev', new Link(new CacheableMetadata(), $prev, 'prev'));
    }

    // Add 'next' link.
    if ($current < ($total - 1)) {
      $options = [
        'query' => $pager_manager->getUpdatedParameters($parameters, $element, $current + 1),
      ];
      $next = Url::fromRoute('<current>', [], $options);
      $pager_links = $pager_links->withLink('next', new Link(new CacheableMetadata(), $next, 'next'));
    }

    return [$pager_links, $pager->getTotalItems()];
  }

  /**
   * Executes a view display with url parameters.
   *
   * @param \Drupal\views\ViewExecutable\ViewExecutable $view
   *   An executable view instance.
   * @param string $display_id
   *   A display machine name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\views\ViewExecutable\ViewExecutable
   *   The executed view with query parameters applied as exposed filters.
   */
  protected function executeView(ViewExecutable &$view, string $display_id, Request $request) {
    // Get params from request.
    $exposed_filter_params = $this->getExposedFilterParams($request);
    $exposed_sort_params = $this->getExposedSortParams($request);
    $exposed_params = \array_merge($exposed_filter_params, $exposed_sort_params);
    $view->setExposedInput($exposed_params);

    return $view->preview($display_id);
  }

  /**
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function process(Request $request): ResourceResponse {
    $view = Views::getView($request->get('view'));
    assert($view instanceof ViewExecutable);
    $display_id = $request->get('display');

    // @todo Check access properly.
    if (!$view->access([$display_id])) {
      return $this->createJsonapiResponse($this->createCollectionDataFromEntities([]), $request, 403, []);
    }

    $context = new RenderContext();
    \Drupal::service('renderer')->executeInRenderContext($context, function () use (&$view, $display_id, $request) {
      return $this->executeView($view, $display_id, $request);
    });

    // Handle any bubbled cacheability metadata.
    if (!$context->isEmpty()) {
      $bubbleable_metadata = $context->pop();
      BubbleableMetadata::createFromObject($view->result)
        ->merge($bubbleable_metadata);
    }
    else {
      $bubbleable_metadata = BubbleableMetadata::createFromObject($view->result);
    }
    $bubbleable_metadata->addCacheTags($view->getCacheTags());

    $entities = array_map(function (ResultRow $row) {
      return $row->_entity;
    }, $view->result);
    $data = $this->createCollectionDataFromEntities($entities);
    list($pagination_links, $total_count) = $this->getViewsPager($view);

    $response = $this->createJsonapiResponse($data, $request, 200, [], $pagination_links, ['count' => $total_count]);
    if (isset($bubbleable_metadata)) {
      $bubbleable_metadata->addCacheContexts([
        'url.query_args:page',
        'url.query_args:views-filter',
        'url.query_args:views-sort',
      ]);
      $response->addCacheableDependency($bubbleable_metadata);
    }
    return $response;
  }

}
