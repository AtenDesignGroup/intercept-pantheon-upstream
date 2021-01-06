<?php

namespace Drupal\intercept_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Component\Utility\SortArray;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * The management controller base class.
 */
class ManagementControllerBase extends ControllerBase {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Drupal\Core\DependencyInjection\ClassResolverInterface definition.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
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
   * Default page.
   *
   * @return array
   *   Rendered array for admin page.
   */
  public function view(Request $request, AccountInterface $user = NULL) {
    if (!$user) {
      $user = $this->currentUser();
    }
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

  /**
   * Allow intercept_management plugins to alter the admin page content.
   *
   * @param array $build
   *   The admin page build array.
   *
   * @return array
   *   The altered admin page build array.
   */
  protected function doAlter(array $build) {
    $definitions = \Drupal::service('plugin.manager.intercept_management')->getDefinitions();
    $machine_name = $this->getMachineName();
    foreach ($definitions as $definition) {
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

  /**
   * The machine name of the current route object page.
   *
   * @return string|false
   *   The machine name, or FALSE if no matching route object.
   */
  protected function getMachineName() {
    if ($name = $this->routeMatch->getRouteObject()->getOption('_page_name')) {
      return $name;
    }
    return FALSE;
  }

  /**
   * Alter the management page build array.
   *
   * @param array $build
   *   The current page build array.
   * @param string $page_name
   *   The machine name of the management page route.
   */
  public function alter(array &$build, $page_name) {}

  /**
   * Convert snake case page name to camel case and return if exists.
   *
   * @return bool|string
   *   The camel case name for the method, or FALSE if the method does not
   *   exist.
   */
  protected function getMethodName($page_name) {
    $method = $this->convertToSnakeCase("view_{$page_name}");
    return method_exists($this, $method) ? $method : FALSE;
  }

  /**
   * Convert a string from camel case to snake case.
   *
   * @param string $name
   *   The string to convert to snake case.
   *
   * @return string
   *   The string in snake case.
   */
  protected function convertToSnakeCase($name) {
    $converter = new CamelCaseToSnakeCaseNameConverter();
    return $converter->denormalize($name);
  }

  /**
   * The current module name.
   *
   * @return string
   *   The current module name.
   */
  protected function getModuleName() {
    return explode('\\', get_class($this))[1];
  }

  /**
   * Generates the table render array for a given taxonomy vocabulary ID.
   *
   * @param array $ids
   *   The machine name of the vocabulary.
   * @param string $title
   *   The title of the table.
   *
   * @return array
   *   The render array representation of the vocabulary table.
   */
  protected function getTaxonomyVocabularyTable(array $ids = [], $title = 'Taxonomies') {
    $taxonomy_storage = $this->entityTypeManager()->getStorage('taxonomy_vocabulary');

    $output = [
      '#title' => $this->t('@title', ['@title' => $title]),
      '#content' => [
        'table' => [
          '#type' => 'table',
          '#header' => [
            'Link',
            'Description',
          ],
        ],
      ],
    ];
    foreach ($ids as $id) {
      $vocabulary = $taxonomy_storage->load($id);
      $output['#content']['table'][] = [
        'name' => [
          '#markup' => $vocabulary->toLink(NULL, 'overview-form')->toString(),
        ],
        'description' => [
          '#markup' => $vocabulary->get('description'),
        ],
      ];
    }
    return $output;
  }

  /**
   * Gets the button for a management route.
   *
   * @param string $title
   *   The text of the link.
   * @param string $name
   *   The intercept_management name.
   * @param array $params
   *   (optional) An associative array of parameter names and values.
   *
   * @return array
   *   The render array representation of the Link.
   */
  protected function getManagementButton($title, $name, array $params = []) {
    $params = array_merge($params, [
      'user' => $this->currentUser()->id(),
    ]);
    $route = "{$this->getModuleName()}.management.$name";
    return $this->getButton($title, $route, $params);
  }

  /**
   * Generates a rendered Link from a title and route.
   *
   * @param string $title
   *   The text of the link.
   * @param string $route
   *   The name of the route.
   * @param array $params
   *   (optional) An associative array of parameter names and values.
   *
   * @return array
   *   The render array representation of the Link.
   */
  protected function getButton($title, $route, array $params = [], array $options = []) {
    $button = Link::createFromRoute($title, $route, $params, $options)->toRenderable();
    $button['#access'] = $button['#url']->access($this->currentUser());
    return $button;
  }

  /**
   * Gets the rendered Link for a subpage of the current route.
   *
   * @param string $query
   *   The 'view' GET parameter.
   * @param string $title
   *   The text of the link.
   *
   * @return array
   *   The render array representation of the Link.
   */
  protected function getButtonSubpage($query, $title) {
    $current_route = $this->routeMatch->getRouteName();
    $link = $this->getButton($title, $current_route, [
      'view' => $query,
      'user' => $this->currentUser()->id(),
    ]);
    return $link;
  }

  /**
   * Generates a render array for an entity type list.
   *
   * @param mixed $class
   *   The handler class to instantiate.
   * @param string $entity_type
   *   The machine name of the entity type.
   *
   * @return array
   *   The render array representation of an entity list builder class.
   */
  protected function getList($class, $entity_type = 'node') {
    $entity_type = $this->entityTypeManager()->getDefinition($entity_type);
    return $this->entityTypeManager()
      ->createHandlerInstance($class, $entity_type)
      ->render();
  }

  /**
   * Creates an object for generating a table render array.
   *
   * @return object
   *   An instantiated table object.
   */
  protected function table() {
    return new class {

      /**
       * The render array representation of a management table.
       *
       * @var array
       */
      private $table = [
        '#type' => 'table',
        '#rows' => [],
        '#header' => [
          'Link',
          'Description',
        ],
      ];

      /**
       * Creates a table row with a link and description.
       *
       * @param array $link
       *   The render array representation of a Link.
       * @param string $description
       *   The row description.
       */
      public function row(array $link, $description) {
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
       *   The render array representation of the table.
       */
      public function toRenderable() {
        return $this->toArray();
      }

      /**
       * To renderable array.
       *
       * @return array
       *   The render array representation of the table.
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
   *   Drupal form object.
   * @param array $keep
   *   Array of form keys to keep visible.
   */
  protected function hideElements(array &$form, array $keep = []) {
    $keep = array_merge([
      'actions',
      'form_build_id',
      'form_token',
      'form_id',
    ], $keep);
    $children = Element::children($form);
    foreach ($children as $name) {
      if (in_array($name, $keep)) {
        continue;
      }
      $form[$name]['#access'] = FALSE;
    }
  }

  /**
   * Gets the render array for title text.
   *
   * @param string $text
   *   The title text string.
   *
   * @return array
   *   The render array representation of the title.
   */
  protected function title($text) {
    return [
      '#markup' => $text,
    ];
  }

}
