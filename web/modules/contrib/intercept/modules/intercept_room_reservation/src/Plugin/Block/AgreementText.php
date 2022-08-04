<?php

namespace Drupal\intercept_room_reservation\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides the agreement text block as seen on /room-reservations.
 * The body text of this is built by configuring the agreement at:
 * /admin/structure/intercept/room_reservation/settings
 *
 * @Block(
 *   id = "intercept_room_reservation_agreement_text",
 *   admin_label = @Translation("Room Reservation Agreement"),
 * )
 */
class AgreementText extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $room_reservation_settings = \Drupal::config('intercept_room_reservation.settings');
    // Add room reservation agreement text.
    $agreement_text = $room_reservation_settings->get('agreement_text', '');

    // Add horizontal rules to the headers.
    $output = str_replace('<h', '<hr><h', $agreement_text['value']);
    // Add a link to the end.
    $output .= '<h2><a href="/reserve-room">Reserve a Room→</a></h2>';
    
    return [
      '#markup' => $output,
      '#format' => $agreement_text['format']
    ];
    
  }

}
