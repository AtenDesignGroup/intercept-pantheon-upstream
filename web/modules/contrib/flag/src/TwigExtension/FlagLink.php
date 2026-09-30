<?php

namespace Drupal\flag\TwigExtension;

use Drupal\flag\FlagLinkBuilderInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig extension to build flag/unflag links.
 */
class FlagLink extends AbstractExtension {

  public function __construct(
    protected FlagLinkBuilderInterface $flagLinkBuilder,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('flaglink', [$this, 'build'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'flag.twig.link';
  }

  /**
   * Builds a flag/unflag link for an entity.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   * @param string $flag_id
   *   The flag ID.
   *
   * @return array
   *   The render array for the flag link.
   */
  public function build($entity_type, $entity_id, $flag_id) {
    return $this->flagLinkBuilder->build($entity_type, $entity_id, $flag_id, 'default');
  }

}
