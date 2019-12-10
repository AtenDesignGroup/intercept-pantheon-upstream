<?php

namespace Drupal\intercept_event;

use Drupal\Core\Queue\QueueFactory;

/**
 * Queue worker for Events.
 */
class EventQueue {

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queueFactory;

  /**
   * Add Polaris search results to queue for grouping.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   Queue service.
   */
  public function __construct(QueueFactory $queue_factory) {
    $this->queueFactory = $queue_factory;
  }

  /**
   * Whether if the queue is empty.
   */
  public function check($worker) {
    $queue = $this->queueFactory->get($worker);
    $number_in_queue = $queue->numberOfItems();
    return ($number_in_queue == 0);
  }

  /**
   * Adds an item to the queue.
   */
  public function add(string $nid) {
    $queue = $this->queueFactory->get('intercept_event_update_worker');
    $item = new \stdClass();
    $item->nid = $nid;
    $queue->createItem($item);
  }

}
