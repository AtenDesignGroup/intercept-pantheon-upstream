<?php

namespace Drupal\flag_link_test\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\flag\Entity\Flag;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test controller class.
 */
class FlagLinkTest implements ContainerInjectionInterface {

  /**
   * The flag link builder.
   *
   * @var \Drupal\flag\FlagLinkBuilderInterface
   */
  protected $flagLinkBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = new static();
    $instance->flagLinkBuilder = $container->get('flag.link_builder');

    return $instance;
  }

  /**
   * Displays a page with a single flag link for a specific entity.
   *
   * @param string $entity_type_id
   *   The flagged entity's type.
   * @param int|string $entity_id
   *   The flagged entity ID.
   * @param \Drupal\flag\Entity\Flag $flag
   *   The flag.
   *
   * @return array
   *   A render array containing the flag link.
   */
  public function page(string $entity_type_id, $entity_id, Flag $flag): array {
    return [
      '#type' => 'container',
      '#cache' => ['max-age' => 0],
      'flag' => $this->flagLinkBuilder->build($entity_type_id, $entity_id, $flag->id()),
    ];
  }

}
