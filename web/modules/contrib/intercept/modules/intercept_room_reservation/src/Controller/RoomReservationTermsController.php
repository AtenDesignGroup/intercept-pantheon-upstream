<?php

namespace Drupal\intercept_room_reservation\Controller;

use Drupal\Core\Config\Config;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RoomReservationTermsController.
 */
class RoomReservationTermsController extends ControllerBase {

  /**
   * Creates a NodeViewController object.
   *
   * @param \Drupal\Core\Config\Config $config
   *
   */
  public function __construct(Config $config) {
    $this->config = $config;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')->get('intercept_room_reservation.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view() {
    $build = [];
    $terms = $this->config->get('agreement_text');
    $build['terms'] = [
      '#type' => 'processed_text',
      '#text' => $terms['value'],
      '#format' => $terms['format'],
    ];
    return $build;
  }

}
