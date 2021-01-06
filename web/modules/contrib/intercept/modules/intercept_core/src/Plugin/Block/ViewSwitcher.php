<?php

namespace Drupal\intercept_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Provides switcher links and theming for two routes.
 *
 * @Block(
 *  id = "intercept_view_switcher",
 *  admin_label = @Translation("Intercept view switcher"),
 * )
 */
class ViewSwitcher extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var RouteProviderInterface
   */
  protected $routeProvider;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeProvider = $route_provider;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['links'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    foreach (['Link 1', 'Link2'] as $key => $title) {
      $link = [
        '#title' => $this->t($title),
        '#type' => 'details',
        '#open' => TRUE,
        'title' => [
          '#title' => $this->t('Title'),
          '#type' => 'textfield',
          '#default_value' => $this->configuration['links'][$key]['title'] ?? '',
        ],
        'route' => [
          '#title' => $this->t('Route'),
          '#type' => 'textfield',
          '#default_value' => $this->configuration['links'][$key]['route'] ?? '',
        ],
      ];
      $form['links'][] = $link;
    }

    return $form;
  }

  public function blockValidate($form, FormStateInterface $form_state) {
    $links = &$form_state->getValue('links');
    foreach ($links as &$link) {
      if (empty($link['route'])) {
        continue;
      }
      $route_name = trim($link['route']);
      try {
        $this->routeProvider->getRouteByName($route_name);
      }
      catch (RouteNotFoundException $e) {
        $form_state->setErrorByName('links', $this->t('Invalid route @route_name', [
          '@route_name' => $link['route'],
        ]));
      }
    }
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $complete = $form_state->getCompleteFormState();
    $request_path = &$complete->getValue(['visibility', 'request_path']);
    $request_path['pages'] = [];
    $request_path['negate'] = FALSE;
    $configuration = $this->getConfiguration();
    $this->configuration['links'] = $form_state->getValue('links');
    foreach ($this->configuration['links'] as $link) {
      $route = $this->routeProvider->getRouteByName($link['route']);
      $request_path['pages'][] = $route->getPath();
    }
    $request_path['pages'] = implode("\n", $request_path['pages']);
  }

  public function build() {
    $build = [
      '#theme' => 'intercept_view_switcher',
    ];
    $build['#links'] = $this->configuration['links'];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.path']);
  }

}
