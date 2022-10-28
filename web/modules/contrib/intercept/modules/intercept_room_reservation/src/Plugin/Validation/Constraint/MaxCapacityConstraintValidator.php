<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Validates the MaxCapacity constraint.
 */
class MaxCapacityConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new MaxCapacityConstraintValidator.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }

    if ($this->currentUser->hasPermission('bypass room reservation maximum capacity constraints') && !$entity->__get('warning')) {
      return;
    }

    $maxCapacity = NULL;
    if (!empty($entity->field_room->target_id)) {
      // Load the room to obtain its max capacity.
      $roomId = $entity->field_room->target_id;
      $room = Node::load($roomId);
      $maxCapacity = (!empty($room->field_capacity_max->value)) ? $room->field_capacity_max->value : NULL;
    }
    // Validate the entity here.
    if (!empty($entity->field_attendee_count->value) && !is_null($maxCapacity) && $entity->field_attendee_count->value > $maxCapacity) {
      $this->context->buildViolation($constraint->maxCapacityMessage, [
        '%value' => $entity->field_attendee_count->value,
        '%max' => $maxCapacity,
      ])
        // The path depends on entity type. It can be title, name, etc.
        ->atPath('field_capacity_max')
        ->addViolation();
    }

  }

}
