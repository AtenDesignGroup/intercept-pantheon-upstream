<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Queue\QueueFactory;

class EventQueue {

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queueFactory;

  /**
   * Add Polaris search results to queue for grouping.
   * @param QueueFactory $queue_factory
   *   queue service.
   */
  public function __construct(QueueFactory $queue_factory) {
    $this->queueFactory = $queue_factory;
  }

  public function check($worker) {
    $queue = $this->queueFactory->get($worker);
    $number_in_queue = $queue->numberOfItems();
    return ($number_in_queue == 0);
  }

  public function add(string $nid) {
    $queue = $this->queueFactory->get('intercept_event_update_worker');
    $item = new \stdClass();
    $item->nid = $nid;
    $queue->createItem($item);
  }

}