<?php

namespace Drupal\intercept_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Component\Utility\SortArray;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class ManagementControllerBase extends ControllerBase {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * @var ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Constructs a new AdminController object.
   */
  public function __construct(AccountProxyInterface $current_user, CurrentRouteMatch $route_match, ClassResolverInterface $class_resolver) {
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('class_resolver')
    );
  }

  /**
   * Defaultpage.
   *
   * @return array
   *   Rendered array for admin page.
   */
  public function view(AccountInterface $user, Request $request) {
    $page_name = $this->routeMatch->getRouteObject()->getOption('_page_name');
    $subpage = $request->query->get('view');
    // First check if there is a subpage from the query string.
    if (!$method = $this->getMethodName("{$page_name}_{$subpage}")) {
      // Otherwise just check if there is a regular callback.
      $method = $this->getMethodName($page_name);
    }
    if ($method) {
      $build = $this->{$method}($user, $request);
      $build = [
        '#theme' => 'intercept_management',
        '#content' => $this->doAlter($build),
      ];
      return $build;
    }
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Controller not found.'),
    ];
  }

  protected function doAlter(array $build) {
    $definitions = \Drupal::service('plugin.manager.intercept_management')->getDefinitions();
    $machine_name = $this->getMachineName();
    foreach ($definitions as $module => $definition) {
      list($callable, $method) = \Drupal::service('controller_resolver')->getControllerFromDefinition($definition['controller']);
      $callable->alter($build, $machine_name);
    }

    if (isset($build['sections'])) {
      foreach ($build['sections'] as $section => $definition) {
        $build['sections'][$section]['#theme'] = 'intercept_management_section';

        if (isset($definition['#actions'])) {
          foreach ($definition['#actions'] as $action => $action_definition) {
            $build['sections'][$section]['#actions'][$action]['#theme'] = 'intercept_management_action';
          }
          uasort($build['sections'][$section]['#actions'], [SortArray::class, 'sortByWeightProperty']);
        }
      }
    }
    return $build;
  }

  protected function getMachineName() {
    if ($name = $this->routeMatch->getRouteObject()->getOption('_page_name')) {
      return $name;
    }
    return FALSE;
  }

  public function alter(array &$build, $page_name) {}

  /**
   * Convert snake case page name to camel case and return if exists.
   *
   * @return bool|string
   */
  protected function getMethodName($page_name) {
    $method = $this->convertToSnakeCase("view_{$page_name}");
    return method_exists($this, $method) ? $method : FALSE;
  }

  protected function convertToSnakeCase($name) {
    $converter = new CamelCaseToSnakeCaseNameConverter();
    return $converter->denormalize($name);
  }

  protected function getModuleName() {
    return explode('\\', get_class($this))[1];
  }

  protected function getTaxonomyVocabularyTable($ids = [], $title = 'Taxonomies') {
    $taxonomy_storage = $this->entityTypeManager()->getStorage('taxonomy_vocabulary');

    $output = [
      '#title' => $this->t($title),
      '#content' => [
        'table' => [
          '#type' => 'table',
          '#header' => [
            'Link',
            'Description',
          ]
        ],
      ],
    ];
    foreach ($ids as $id) {
      $vocabulary = $taxonomy_storage->load($id);
      $output['#content']['table'][] = [
        'name' => [
          '#markup' => $vocabulary->link(NULL, 'overview-form')->__toString()
        ],
        'description' => [
          '#markup' => $vocabulary->get('description')
        ],
      ];
    }
    return $output;
  }

  protected function getManagementButton($title, $name, $params = []) {
    $route = "{$this->getModuleName()}.management.$name";
    return $this->getButton($title, $route, [
      'user' => $this->currentUser()->id(),
    ]);
  }

  protected function getButton($title, $route, $params = []) {
    $button = \Drupal\Core\Link::createFromRoute($title, $route, $params)->toRenderable();
    $button['#access'] = $button['#url']->access($this->currentUser());
    return $button;
  }

  protected function getButtonSubpage($query, $title) {
    $current_route = $this->routeMatch->getRouteName();
    $link = $this->getButton($title, $current_route, [
      'view' => $query,
      'user' => $this->currentUser()->id(),
    ]);
    return $link;
  }

  protected function getList($class, $entity_type = 'node') {
    $entity_type = $this->entityTypeManager()->getDefinition($entity_type);
    return $this->entityTypeManager()
      ->createHandlerInstance($class, $entity_type)
      ->render();
  }

  protected function table() {
    return new class {
      private $table = [
        '#type' => 'table',
        '#rows' => [],
        '#header' => [
          'Link',
          'Description',
        ],
      ];
      public function row($link, $description) {
        $row = [];
        $row[] = [
          'data' => $link,
        ];
        $row[] = [
          'data' => $description,
        ];
        $this->table['#rows'][] = $row;
      }

      /**
       * Alias of toArray for consistency.
       *
       * @return array
       */
      public function toRenderable() {
        return $this->toArray();
      }

      /**
       * To renderable array.
       *
       * @return array
       */
      public function toArray() {
        return $this->table;
      }
    };
  }

  /**
   * Hide all elements in a form except specified keys.
   *
   * TODO: Rename this to isolateElements, or something similar.
   *
   * @param array $form
   *   Drupal form object
   * @param array $keep
   *   Array of form keys to keep visible
   */
  protected function hideElements(array &$form, array $keep = []) {
    $keep = array_merge([
      'actions',
      'form_build_id',
      'form_token',
      'form_id',
    ], $keep);
    $children = \Drupal\Core\Render\Element::children($form);
    foreach ($children as $name) {
      if (in_array($name, $keep)) {
        continue;
      }
      $form[$name]['#access'] = FALSE;
    }
  }

  protected function title($text, $replacements = []) {
    return [
      '#markup' => $this->t($text, $replacements),
    ];
  }

  protected function h2($text, $replacements = []) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t($text, $replacements),
    ];
  }

}
