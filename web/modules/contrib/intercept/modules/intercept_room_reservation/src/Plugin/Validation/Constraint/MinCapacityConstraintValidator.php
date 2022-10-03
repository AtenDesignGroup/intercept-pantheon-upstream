<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Drupal\intercept_room_reservation\Plugin\Validation\Constraint\ReservationMinDurationConstraintValidator;

/**
 * Validates the MinCapacity constraint.
 */
class MinCapacityConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new MinCapacityConstraintValidator.
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

    if ($this->currentUser->hasPermission('bypass room reservation minimum capacity constraints') && !$entity->__get('warning')) {
      return;
    }

    $minCapacity = NULL;
    if (!empty($entity->field_room->target_id)) {
      // Load the room to obtain its min capacity.
      $roomId = $entity->field_room->target_id;
      $room = Node::load($roomId);
      $minCapacity = (!empty($room->field_capacity_min->value)) ? $room->field_capacity_min->value : NULL;
    }
    // Validate the entity here.
    if (!empty($entity->field_attendee_count->value) && !is_null($minCapacity) && $entity->field_attendee_count->value < $minCapacity) {
      $this->context->buildViolation($constraint->minCapacityMessage, [
        '%value' => $entity->field_attendee_count->value,
        '%min' => $minCapacity
        ])
        // The path depends on entity type. It can be title, name, etc.
        ->atPath('field_capacity_min')
        ->addViolation();
    }

  }

}
