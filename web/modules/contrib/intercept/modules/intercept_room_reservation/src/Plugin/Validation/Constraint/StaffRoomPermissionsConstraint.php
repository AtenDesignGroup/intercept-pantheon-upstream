<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a room reservation constraint for maximum duration.
 *
 * @Constraint(
 *   id = "StaffRoomPermissions",
 *   label = @Translation("Verify that a user can reserve a staff room", context = "Validation"),
 *   type = "entity",
 * )
 */
class StaffRoomPermissionsConstraint extends Constraint {

  /**
   * The error message to display after failing validation.
   *
   * @var string
   */
  public $errorMessage = "You do not have permission to reserve staff rooms.";

}
