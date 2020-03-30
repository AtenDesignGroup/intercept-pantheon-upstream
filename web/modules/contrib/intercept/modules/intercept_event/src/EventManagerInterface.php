<?php

namespace Drupal\intercept_event;

use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Event Manager service class interface.
 */
interface EventManagerInterface {

  /**
   * Deletes an event register alias.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Event node.
   */
  public function deleteRegisterAlias(NodeInterface $node);

  /**
   * Adds an event register alias.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Event node.
   * @param string $alias
   *   The register alias.
   */
  public function addRegisterAlias(NodeInterface $node, $alias = NULL);

  /**
   * View a node cloned from a template.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Event Node.
   *
   * @return array
   *   The Event Node render array.
   */
  public function previewFromTemplate(NodeInterface $node);

  /**
   * Edit a node cloned from a template.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Event Node.
   *
   * @return mixed
   *   The Event edit form.
   */
  public function addFromTemplate(NodeInterface $node);

  /**
   * Loads a Node entity by ID or UUID.
   *
   * @param int $id
   *   The node ID or UUID.
   *
   * @return \Drupal\node\NodeInterface
   *   The loaded Node.
   */
  public function load($id);

  /**
   * Updates the attendance for an Event.
   *
   * @param \Drupal\user\UserInterface $user
   *   Deprecated.  The user.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   */
  public function updateAttendance(UserInterface $user = NULL, Request $request);

  /**
   * Creates an attendee for an event.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to create as an attendee.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   */
  public function createAttendee(UserInterface $user = NULL, Request $request);

  /**
   * Determines whether the event's start time is in the past.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return bool
   *   Whether the event has passed.
   */
  public function isEventStarted(NodeInterface $node);

  /**
   * Determines whether the event's registration end time is in the past.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return bool
   *   Whether the event has passed.
   */
  public function isEventRegistrationEnded(NodeInterface $node);

  /**
   * Determines whether the event node's waitlist option is enabled.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return bool
   *   Whether the event has a waitlist.
   */
  public function allowsWaitlist(NodeInterface $node);

  /**
   * Gets registrations for an event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   * @param string $status
   *   The status of the event registration.
   *
   * @return \Drupal\intercept_event\Entity\EventRegistrationInterface[]
   *   An array of waitlisted registration entities.
   */
  public function getEventRegistrations(NodeInterface $node, $status = '');

  /**
   * Gets the event's active registrants.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return int
   *   A whole number of attendees.
   */
  public function getEventActiveRegistrants(NodeInterface $node);

  /**
   * Gets the event's max capacity.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return int
   *   The max capacity of the event.
   */
  public function getEventCapacity(NodeInterface $node);

  /**
   * Gets the total number of attendees that can be added to an event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return int
   *   A whole number of attendees.
   */
  public function getEventOpenCapacity(NodeInterface $node);

  /**
   * Updates eligible waitlisted registrants to active.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   */
  public function fillEventOpenCapacity(NodeInterface $node);

}
