<?php

namespace Drupal\intercept_certification\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a room reservation constraint for maximum duration.
 *
 * @Constraint(
 *   id = "RoomCertification",
 *   label = @Translation("Verify that is certified to reserve a room", context = "Validation"),
 *   type = "entity",
 * )
 */
class RoomCertificationConstraint extends Constraint {

  /**
   * The error message to display after failing validation.
   *
   * @var string
   */
  public $certErrorMessage = "You must be certified to reserve this room.";

  /**
   * The error message to display after failing validation.
   *
   * @var string
   */
  public $staffErrorMessage = "You do not have permission to reserve staff rooms.";

}
