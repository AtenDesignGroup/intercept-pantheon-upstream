<?php

namespace Drupal\intercept_event;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\intercept_event\EventEvaluationManager;
use Drupal\node\NodeInterface;
use Drupal\votingapi\VoteStorageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class EventEvaluation {

  use DependencySerializationTrait;

  use StringTranslationTrait;

  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var EventEvaluationManager
   */
  protected $manager;

  /**
   * EventEvaluation constructor.
   */
  public function __construct($vote) {
    $this->vote = $vote;
  }

  /**
   * Set the EventEvaluationManager service.
   *
   * @param EventEvaluationManager $manager
   *
   * @return $this
   */
  public function setManager($manager) {
    $this->manager = $manager;
    return $this;
  }

  /**
   * The main evaluation callback to cast a vote.
   *
   * @param $value
   * @param array $data
   *
   * @return $this
   */
  public function evaluate($value, $data = []) {
    $this->vote->setValue($value)
      ->set('vote_criteria', $data)
      ->save();
      // TODO: Finish calculating results.
      //$this->resultManager->recalculateResults($entity_type_id, $entity_id, $vote_type_id);
    return $this;
  }

  public function view($view_mode = 'default') {
    $manager = \Drupal::service('entity_type.manager');
    return $manager->getViewBuilder('vote')->view($this->vote, $view_mode);
  }

  /**
   * Get voteapi staff feedback value.
   *
   * @return string
   */
  public function getFeedback() {
    $feedback = $this->vote->feedback;
    return !empty($feedback) ? $feedback->getString() : '';
  }

  /**
   * Set voteapi staff feedback value.
   *
   * @return $this
   */
  public function setFeedback($text) {
    $this->vote->setValue(-1)
      ->set('feedback', $text)
      ->save();
    return $this;
  }

  public function setOwner(\Drupal\user\UserInterface $account) {
    $this->vote->setOwner($account);
    return $this;
  }

  public function setOwnerId($id) {
    $this->vote->setOwnerId($id);
    return $this;
  }

  public function getOwner() {
    return $this->vote->getOwner();
  }

  public function getOwnerId() {
    return $this->vote->getOwnerId();
  }

  /**
   * Delete this EventEvaluation and votingapi entity.
   */
  public function delete() {
    $this->vote->delete();
  }

  /**
   * Get the votingapi vote value.
   */
  public function getVote() {
    return $this->vote->get('value')->getString();
  }

  /**
   * Get the event node being voted on.
   *
   * @return NodeInterface
   */
  public function getEvent() {
    return $this->vote->get('entity_id')->entity;
  }

  /**
   * Get the criteria terms for this evaluation.
   *
   * @return array
   */
  public function getVoteCriteria() {
    return $this->vote->get('vote_criteria')->taxonomy_term;
  }

  /**
   * Are there criteria set for this event type.
   *
   * @return bool
   */
  public function hasCriteria() {
    $event = $this->getEvent();
    return $this->manager->getPrimaryEventType($event)
      && !empty($this->manager->getCriteria($event));
  }

  public function getPrimaryEventType() {
    if (!$event = $this->getEvent()) {
      return FALSE;
    }
    return $this->manager->getPrimaryEventType($event);
  }

  public function getNegativeCriteria() {
    if (!$event = $this->getEvent()) {
      return FALSE;
    }
    return $this->manager->getNegativeCriteria($event);
  }

  public function getNegativeCriteriaOptions() {
    if (!$event = $this->getEvent()) {
      return FALSE;
    }
    return $this->manager->getNegativeCriteriaOptions($event);
  }

  public function getPositiveCriteria() {
    if (!$event = $this->getEvent()) {
      return FALSE;
    }
    return $this->manager->getPositiveCriteria($event);
  }

  public function getPositiveCriteriaOptions() {
    if (!$event = $this->getEvent()) {
      return FALSE;
    }
    return $this->manager->getPositiveCriteriaOptions($event);
  }

  public function getCriteria() {
    if (!$event = $this->getEvent()) {
      return FALSE;
    }
    return $this->manager->getCriteria($event);
  }

  public function access(\Drupal\Core\Session\AccountInterface $account = NULL) {
    if (!$account) {
      $account = \Drupal::service('current_user');
    }
    if ($account->hasPermission('evaluate any event')) {
      return AccessResult::allowed();
    }
    if (!$account->hasPermission('evaluate own events')) {
      return AccessResult::neutral();
    }
    // TODO: Move this to the event manager.
    $flaggings = \Drupal::service('entity_type.manager')
      ->getStorage('flagging')
      ->loadByProperties([
        'entity_id' => $this->vote->get('entity_id')->entity->id(),
        'uid' => $account->id(),
        'flag_id' => 'saved_event',
      ]);

    if (!empty($flaggings)) {
      return AccessResult::allowed();
    }

    $attendance = \Drupal::service('entity_type.manager')
      ->getStorage('event_attendance')
      ->loadByProperties([
        'field_user' => $account->id(),
        'field_event' => $this->vote->get('entity_id')->entity->id(),
      ]);
    if (!empty($attendance)) {
      return AccessResult::allowed();
    }

    $registrations = \Drupal::service('entity_type.manager')
      ->getStorage('event_registration')
      ->loadByProperties([
        'field_user' => $account->id(),
        'field_event' => $this->vote->get('entity_id')->entity->id(),
      ]);

    if (!empty($registrations)) {
      return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }
}
