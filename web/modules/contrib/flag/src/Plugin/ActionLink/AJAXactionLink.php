<?php

namespace Drupal\flag\Plugin\ActionLink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the AJAX link type.
 *
 * This class is an extension of the Reload link type, but modified to
 * provide AJAX links.
 *
 * @ActionLinkType(
 *   id = "ajax_link",
 *   label = @Translation("AJAX link"),
 *   description = @Translation("An AJAX JavaScript request will be made without reloading the page.")
 * )
 */
class AJAXactionLink extends Reload {

  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    AccountInterface $current_user,
    protected Request $request,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $current_user);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getDestination() {
    if ($destination = $this->request->query->get('destination')) {
      // Workaround the default behavior so we keep the GET[destination] value
      // no matter how many times the flag is clicked.
      return $destination;
    }
    return parent::getDestination();
  }

  /**
   * {@inheritdoc}
   */
  public function getAsFlagLink(FlagInterface $flag, EntityInterface $entity, ?string $view_mode = NULL): array {
    $build = parent::getAsFlagLink($flag, $entity, $view_mode);
    $build['#attached']['library'][] = 'flag/flag.link_ajax';
    $build['#attributes']['class'][] = 'use-ajax';
    return $build;

  }

}
