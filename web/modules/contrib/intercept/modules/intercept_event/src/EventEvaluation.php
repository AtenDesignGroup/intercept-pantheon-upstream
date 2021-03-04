<?php

namespace Drupal\intercept_event;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Manager functions for a single Event Evaluation.
 */
class EventEvaluation {

  use DependencySerializationTrait;

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event evaluation manager.
   *
   * @var \Drupal\intercept_event\EventEvaluationManager
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
   * @param \Drupal\intercept_event\EventEvaluationManager $manager
   *   The Event evaluation manager service.
   *
   * @return $this
   */
  public function setManager(EventEvaluationManager $manager) {
    $this->manager = $manager;
    return $this;
  }

  /**
   * The main evaluation callback to cast a vote.
   *
   * @param string $value
   *   The vote value.
   * @param array $data
   *   The vote_criteria data.
   *
   * @return $this
   */
  public function evaluate($value, array $data = []) {
    $this->vote->setValue($value)
      ->set('vote_criteria', $data)
      ->save();
    // @TODO: Finish calculating results.
    return $this;
  }

  /**
   * Builds the vote render array.
   *
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the entity.
   *
   * @return array
   *   A render array for the entity.
   */
  public function view($view_mode = 'default') {
    $manager = \Drupal::service('entity_type.manager');
    return $manager->getViewBuilder('vote')->view($this->vote, $view_mode);
  }

  /**
   * Get voteapi staff feedback value.
   *
   * @return string
   *   The voteapi staff feedback value.
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

  /**
   * Sets the vote owner.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user to set as owner.
   *
   * @return $this
   */
  public function setOwner(UserInterface $account) {
    $this->vote->setOwner($account);
    return $this;
  }

  /**
   * Sets the vote owner ID.
   *
   * @param int $id
   *   The user ID to set as owner.
   *
   * @return $this
   */
  public function setOwnerId($id) {
    $this->vote->setOwnerId($id);
    return $this;
  }

  /**
   * Sets the vote owner.
   *
   * @return \Drupal\user\UserInterface
   *   The vote owner.
   */
  public function getOwner() {
    return $this->vote->getOwner();
  }

  /**
   * Sets the vote owner.
   *
   * @return int
   *   The vote owner ID.
   */
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
   * @return \Drupal\node\NodeInterface
   *   The event node.
   */
  public function getEvent() {
    return $this->vote->get('entity_id')->entity;
  }

  /**
   * Get the criteria terms for this evaluation.
   *
   * @return array
   *   The criteria terms.
   */
  public function getVoteCriteria() {
    return $this->vote->get('vote_criteria')->taxonomy_term;
  }

  /**
   * Are there criteria set for this event type.
   *
   * @return bool
   *   Whether criteria are set for this event type.
   */
  public function hasCriteria() {
    $event = $this->getEvent();
    return $this->manager->getPrimaryEventType($event)
      && !empty($this->manager->getCriteria($event));
  }

  /**
   * Gets the primary event type.
   *
   * @return object
   *   The primary event type.
   */
  public function getPrimaryEventType() {
    if (!$event = $this->getEvent()) {
      return FALSE;
    }
    return $this->manager->getPrimaryEventType($event);
  }

  /**
   * Gets an array of negative criteria taxonomy Terms.
   *
   * @return array
   *   The array of negative criteria taxonomy Terms.
   */
  public function getNegativeCriteria() {
    if (!$event = $this->getEvent()) {
      return FALSE;
    }
    return $this->manager->getNegativeCriteria($event);
  }

  /**
   * Gets an array of negative criteria taxonomy Term names.
   *
   * @return array
   *   The array of negative criteria taxonomy Term names.
   */
  public function getNegativeCriteriaOptions() {
    if (!$event = $this->getEvent()) {
      return FALSE;
    }
    return $this->manager->getNegativeCriteriaOptions($event);
  }

  /**
   * Gets an array of positive criteria taxonomy Terms.
   *
   * @return array
   *   The array of positive criteria taxonomy Terms.
   */
  public function getPositiveCriteria() {
    if (!$event = $this->getEvent()) {
      return FALSE;
    }
    return $this->manager->getPositiveCriteria($event);
  }

  /**
   * Gets an array of positive criteria taxonomy Term names.
   *
   * @return array
   *   The array of positive criteria taxonomy Term names.
   */
  public function getPositiveCriteriaOptions() {
    if (!$event = $this->getEvent()) {
      return FALSE;
    }
    return $this->manager->getPositiveCriteriaOptions($event);
  }

  /**
   * Gets an array of criteria taxonomy Terms.
   *
   * @return array
   *   The array of criteria taxonomy Terms.
   */
  public function getCriteria() {
    if (!$event = $this->getEvent()) {
      return FALSE;
    }
    return $this->manager->getCriteria($event);
  }

  /**
   * Access callback for an Event Evaluation.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  public function access(AccountInterface $account = NULL) {
    if (!$account) {
      $account = \Drupal::service('current_user');
    }
    if ($account->hasPermission('evaluate any event')) {
      return AccessResult::allowed();
    }
    if (!$account->hasPermission('evaluate own events')) {
      return AccessResult::neutral();
    }
    // @TODO: Move this to the event manager.
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
