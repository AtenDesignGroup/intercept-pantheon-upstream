<?php

namespace Drupal\intercept_room_reservation;

use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Theme\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the entity view builder for room reservations.
 */
class RoomReservationViewBuilder extends EntityViewBuilder {

  use AjaxHelperTrait;

  /**
   * The 'intercept_room_reservation.settings' config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a new RoomReservationViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Config\Config $config
   *   The 'intercept_room_reservation.settings' config.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, Registry $theme_registry, EntityDisplayRepositoryInterface $entity_display_repository, Config $config) {
    parent::__construct($entity_type, $entity_repository, $language_manager, $theme_registry, $entity_display_repository);
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('theme.registry'),
      $container->get('entity_display.repository'),
      $container->get('config.factory')->get('intercept_room_reservation.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    if ($this->getRequestWrapperFormat() == 'drupal_dialog.off_canvas') {
      $view_mode = $this->config->get('off_canvas_view_mode') ?: $view_mode;
    }
    $build = parent::getBuildDefaults($entity, $view_mode);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $room_reservation, EntityViewDisplayInterface $display, $view_mode) {
    parent::alterBuild($build, $room_reservation, $display, $view_mode);

    if ($display->getComponent('location_full')) {
      $build['location_full'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => 'field-location-full'],
        '#value' => $room_reservation->location(),
      ];
    }

    if ($display->getComponent('attendees_full')) {
      $count = $room_reservation->field_attendee_count->getString();
      $build['attendees_full'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => 'field-attendees-full'],
        '#value' => !empty($count) ? $this->t('@count Attendees', [
          '@count' => $count,
        ]) : '',
      ];
    }

    if ($display->getComponent('action_button')) {
      $build['action_button'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'js--room-reservation-action',
          'data-reservation-uuid' => $room_reservation->uuid(),
          'data-status' => $room_reservation->field_status->value,
        ],
        '#attached' => [
          'library' => ['intercept_room_reservation/roomReservationActionButton', 'intercept_core/moment'],
        ],
      ];
    }
  }

}
