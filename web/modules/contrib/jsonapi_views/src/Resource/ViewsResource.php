<?php

declare(strict_types=1);

namespace Drupal\jsonapi_views\Resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\CacheableResourceResponse;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi_resources\Resource\EntityResourceBase;
use Drupal\jsonapi_views\Plugin\views\display_extender\JsonapiViews;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes a request for a collection of featured nodes.
 *
 * @internal
 */
final class ViewsResource extends EntityResourceBase implements ContainerInjectionInterface {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a ViewsResource object.
   *
   * @param \Drupal\Core\Pager\PagerManagerInterface $pagerManager
   *   The pager manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    /**
     * The pager manager.
     */
    protected PagerManagerInterface $pagerManager,
    /**
     * The renderer.
     */
    protected RendererInterface $renderer,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('pager.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Extracts exposed filter values from the request.
   *
   * @return array
   *   Key value pairs of exposed filters.
   */
  protected function getExposedFilterParams() {
    $all_params = $this->request->query->all();
    return $all_params['views-filter'] ?? [];
  }

  /**
   * Extracts exposed sort values from the request.
   *
   * @return array
   *   Key value pairs of exposed sorts.
   */
  protected function getExposedSortParams() {
    $all_params = $this->request->query->all();
    return $all_params['views-sort'] ?? [];
  }

  /**
   * Extracts view argument values from the request.
   *
   * @return array
   *   View arguments.
   */
  protected function getViewArguments() {
    $all_params = $this->request->query->all();
    return $all_params['views-argument'] ?? [];
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

    $element = $view->pager->getPagerId();
    $pager = $this->pagerManager->getPager($element);

    if (!$pager) {
      return [$pager_links, count($view->result)];
    }

    $parameters = [];
    $current = $pager->getCurrentPage();
    $total = $pager->getTotalPages();

    // Add 'prev' link.
    if ($current > 0) {
      $options = [
        'query' => $this->pagerManager->getUpdatedParameters($parameters, $element, $current - 1),
      ];
      $prev = Url::fromUri($this->request->getUri(), $options);
      $pager_links = $pager_links->withLink('prev', new Link(new CacheableMetadata(), $prev, 'prev'));
    }

    // Add 'next' link.
    if ($current < ($total - 1)) {
      $options = [
        'query' => $this->pagerManager->getUpdatedParameters($parameters, $element, $current + 1),
      ];
      $next = Url::fromUri($this->request->getUri(), $options);
      $pager_links = $pager_links->withLink('next', new Link(new CacheableMetadata(), $next, 'next'));
    }

    return [$pager_links, $pager->getTotalItems()];
  }

  /**
   * Executes a view display with url parameters.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   An executable view instance.
   * @param string $display_id
   *   A display machine name.
   *
   * @return array
   *   The preview result from the executed view.
   */
  protected function executeView(ViewExecutable &$view, string $display_id) {
    // Get params from request.
    $exposed_filter_params = $this->getExposedFilterParams();
    $exposed_sort_params = $this->getExposedSortParams();
    $exposed_params = \array_merge($exposed_filter_params, $exposed_sort_params);
    $view->setExposedInput($exposed_params);

    return $view->preview($display_id, $this->getViewArguments());
  }

  /**
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\jsonapi\CacheableResourceResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function process(Request $request): CacheableResourceResponse {
    $view_id = $request->attributes->get('view');
    assert(is_string($view_id));
    $view = Views::getView($view_id);
    assert($view instanceof ViewExecutable);

    // Set the request.
    $this->request = $request;

    $display_id = $request->attributes->get('display');
    assert(is_string($display_id));

    $view->setDisplay($display_id);
    $extenders = $view->getDisplay()->getExtenders();
    $jsonapi_extender = $extenders['jsonapi_views'] ?? NULL;
    // @todo Check access properly.
    if (!$view->access($display_id) || ($jsonapi_extender instanceof JsonapiViews && !$jsonapi_extender->isExposed())) {
      $response = $this->createJsonapiResponse($this->createCollectionDataFromEntities([]), $this->request, 403, []);
      assert($response instanceof CacheableResourceResponse);
      // Add the view's cache tags and contexts, so the denial is
      // invalidated when the view changes and isn't cached across users
      // or permissions the view's own plugins/handlers vary by. The view
      // hasn't executed yet at this point (deliberately, to avoid running
      // its query for a request that's being denied), so this reads the
      // display's cacheability directly rather than from a render array.
      $cacheable_metadata = CacheableMetadata::createFromObject($view->getDisplay()->getCacheMetadata());
      $cacheable_metadata->addCacheTags(['config:views.view.' . $view->id()]);
      $response->addCacheableDependency($cacheable_metadata);
      return $response;
    }

    $context = new RenderContext();
    $view_preview = $this->renderer->executeInRenderContext($context, function () use (&$view, $display_id) {
      return $this->executeView($view, $display_id);
    });

    // The view's own cacheability, aggregated from every plugin and
    // handler on the display (filters, arguments, sorts, exposed form,
    // query, pager, ...) and applied to $view_preview['#cache'] by
    // DisplayPluginBase::render(). Building on the approach from
    // yahyaalhamad's patch (#3202583, comment 11, Nov 2024).
    $view_cacheability = CacheableMetadata::createFromRenderArray($view_preview);

    // Merge in any additional cacheability bubbled up during render.
    if (!$context->isEmpty()) {
      $view_cacheability = $view_cacheability->merge($context->pop());
    }

    $entities = array_map(fn(ResultRow $row) => $row->_entity, $view->result);
    $data = $this->createCollectionDataFromEntities($entities);
    [$pagination_links, $total_count] = $this->getViewsPager($view);

    $response = $this->createJsonapiResponse($data, $this->request, 200, [], $pagination_links, ['count' => $total_count]);
    assert($response instanceof CacheableResourceResponse);
    $view_cacheability->addCacheContexts([
      'url.query_args:page',
      'url.query_args:views-filter',
      'url.query_args:views-sort',
      'url.query_args:views-argument',
    ]);
    $response->addCacheableDependency($view_cacheability);
    return $response;
  }

}
