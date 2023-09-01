<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\intercept_event\CheckinPeriodInvalidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class EventCheckinPeriodController.
 */
class EventCheckinPeriodController extends ControllerBase {

  /**
   * The checkin period invalidator service.
   *
   * @var \Drupal\intercept_event\CheckinPeriodInvalidatorInterface
   */
  protected $checkinPeriodInvalidator;

  /**
   * EventCheckinPeriodController constructor.
   *
   * @param \Drupal\intercept_event\CheckinPeriodInvalidatorInterface $checkin_period_invalidator
   *   The checkin period invalidator service.
   */
  public function __construct(
    CheckinPeriodInvalidatorInterface $checkin_period_invalidator
  ) {
    $this->checkinPeriodInvalidator = $checkin_period_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('intercept_event.checkin_period_invalidator'),
    );
  }

  /**
   * Invalidates the cache of any checkin periods that have started or ended since the last check.
   * This is necessary since the check-in period status is time-sensitive information available to
   * anonymous users. Since max-age is not reliable when using Page Cache, we need an alternative
   * approach to invalidating node cache tags.
   *
   * See: [Limitations of max-age](https://www.drupal.org/docs/drupal-apis/cache-api/cache-max-age#s-limitations-of-max-age)
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JsonResponse object with event registration IDs.
   */
  public function invalidateCheckinPeriods() {

    return new JsonResponse([
      'invalidated' => $this->checkinPeriodInvalidator->invalidateCheckinPeriods(),
    ]);
  }

}
