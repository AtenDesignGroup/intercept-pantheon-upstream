<?php

namespace Drupal\intercept_guest;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Intercept Guest entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup intercept_guest
 */
interface InterceptGuestInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
