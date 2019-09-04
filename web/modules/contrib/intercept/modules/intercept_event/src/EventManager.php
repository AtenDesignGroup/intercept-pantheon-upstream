<?php

namespace Drupal\intercept_event;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Event Manager service class.
 */
class EventManager implements EventManagerInterface {

  use DependencySerializationTrait;

  use StringTranslationTrait;

  /**
   * Active current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EventManager object.
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  public function deleteRegisterAlias(NodeInterface $node) {
    $alias = $node->path->alias . '/register';
    $storage = \Drupal::service('path.alias_storage');
    $conditions = [
      'source' => '/event/' . $node->id() . '/register',
    ];
    if ($path = $storage->load($conditions)) {
      $storage->delete($conditions);
    }
  }

  public function addRegisterAlias(NodeInterface $node, $alias = NULL) {
    $alias = $alias ?: $node->path->alias;
    if (empty($alias)) {
      return;
    }
    $alias .= '/register';
    $storage = \Drupal::service('path.alias_storage');
    $conditions = [
      'source' => '/event/' . $node->id() . '/register',
    ];
    if ($path = $storage->load($conditions)) {
      $storage->save($conditions['source'], $alias, $path['langcode'], $path['pid']);
    }
    else {
      $storage->save($conditions['source'], $alias);
    }
  }

  /**
   * Create an event node clone with certain changes.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return \Drupal\node\NodeInterface
   */
  private function cloneify(NodeInterface $node) {
    $new_node = $node->createDuplicate();
    $new_node->set('field_event_is_template', 0);
    foreach (['vid', 'field_date_time', 'field_event_register_period'] as $field) {
      $new_node->set($field, NULL);
    }

    $new_node->setOwnerId(\Drupal::currentUser()->id());
    return $new_node;
  }

  /**
   * View a node cloned from a template.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array
   */
  public function previewFromTemplate(NodeInterface $node) {
    $new_node = $this->cloneify($node);
    drupal_set_message($this->t('This is a preview. @use_link.', [
      '@use_link' => Link::createFromRoute('Use this template', 'entity.node.template', [
        'node' => $node->id(),
      ])->toString(),
    ]), 'warning');
    return $this->entityTypeManager->getViewBuilder('node')->view($new_node, 'full');
  }

  /**
   * Edit a node cloned from a template.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return mixed
   */
  public function addFromTemplate(NodeInterface $node) {
    $form = \Drupal::service('entity.form_builder')->getForm($this->cloneify($node));
    return $form;
  }

  /**
   * Alter both node edit and node add forms for events.
   */
  public function nodeFormAlter(&$form, FormStateInterface $form_state) {
    $display = $form_state->getFormObject()->getFormDisplay($form_state);
    if (!$display->getComponent('field_location') || !$display->getComponent('field_room')) {
      return;
    }
    // Add in helper ajax functionality to change room field values
    // depending on location.
    $form['#attached']['library'][] = 'intercept_event/event_form_helper';
    $form['field_location']['widget']['#ajax'] = [
      'callback' => [$this, 'fieldRoomAjaxCallback'],
      'wrapper' => 'event-node-field-room-ajax-wrapper',
    ];
    if (!$location = $form_state->getValue('field_location')) {
      $location = $form['field_location']['widget']['#default_value'];
    }
    $options = &$form['field_room']['widget']['#options'];

    if (empty($location)) {
      $form['field_room']['widget']['#options'] = ['_none' => $this->t('- Select a location -')];
    }
    else {
      $rooms = $this->entityTypeManager->getStorage('node')->loadByProperties([
        'type' => 'room',
        'field_location' => $location[0],
      ]);
      foreach ($options as $id => $label) {
        if ($id == '_none') {
          continue;
        }
        if (!empty($rooms[$id])) {
          continue;
        }
        unset($options[$id]);
      }
    }
    $form['field_room']['#prefix'] = '<div id="event-node-field-room-ajax-wrapper">';
    $form['field_room']['#suffix'] = '</div>';

    $meeting_required_state = [
      'required' => [
        ':input[name="field_event_designation"]' => [
          'value' => 'events',
        ],
      ],
    ];
    $form['field_event_type']['widget']['#states'] = $meeting_required_state;
    $form['field_event_type_primary']['widget']['#states'] = $meeting_required_state;
    $form['field_event_audience']['widget']['#states'] = $meeting_required_state;
    $form['field_audience_primary']['widget']['#states'] = $meeting_required_state;
  }

  /**
   * Ajax form callback to re-populate the room field element.
   */
  public function fieldRoomAjaxCallback(&$form, $form_state) {
    return $form['field_room'];
  }

  /**
   * Alter a node edit form to add template functionality.
   */
  public function nodeEditFormAlter(&$form, FormStateInterface $form_state) {
    if (!$this->currentUser->hasPermission('edit event field field_event_is_template')) {
      return;
    }
    $node = $form_state->getFormObject()->getEntity();
    $is_template = $node->field_event_is_template->getString();
    $form['actions']['template_create'] = [
      '#type' => 'submit',
      '#value' => t('Use as template'),
      '#access' => empty($is_template),
      '#weight' => 15,
      '#submit' => array_merge($form['actions']['submit']['#submit'], [[static::class, 'nodeEditFormSubmit']]),
    ];

    if ($is_template) {
      $form['actions']['submit']['#value'] = t('Save template');
    }
  }

  public static function nodeEditFormSubmit(&$form, FormStateInterface $form_state) {
    $event = $form_state->getFormObject()->getEntity();
    $event_template = $event->createDuplicate();
    // This is to separate it from other events in the admin/content menu.
    $event_template->field_event_is_template->setValue(1);
    $event_template->event_recurrence->setValue(NULL);
    $event_template->save();
    // TODO: Use the message service.
    drupal_set_message(t('Event template @link has been created.', [
      '@link' => $event_template->link(),
    ]));
    // TODO: Fix this so that this overrides the admin/content destination.
    $form_state->setRedirect('entity.node.edit_form', [
      'node' => $event_template->id()
    ]);
  }

  public function load($id) {
    // First try to see if the id provided is a uuid.
    if ($entities = $this->entityTypeManager->getStorage('node')->loadByProperties(['uuid' => $id])) {
      return reset($entities);
    }
    return Node::load($id);
  }

  public function updateAttendance(UserInterface $user = NULL, Request $request) {
    $response = NULL;
    $event_id = $this->getRequestData($request, 'event');
    if ($event = $this->load($event_id)) {
      $data = $this->getRequestData($request, 'attendance');
      array_walk($data, function (&$v, $k) {
        $v = [
          'target_id' => (string) $k,
          'count' => (int) $v,
        ];
      });
      $data = array_values($data);
      $event->field_attendees->setValue($data);
      $event->save();
      $jsonapi = \Drupal::service('jsonapi.entity.to_jsonapi');
      $response = $jsonapi->normalize($event);
    }
    return $this->jsonResponse(['response' => $response]);
  }

  public function createAttendee(UserInterface $user = NULL, Request $request) {
    $response = NULL;
    if ($barcode = $this->getRequestData($request, 'barcode')) {
      $user = \Drupal::service('intercept_ils.mapping_manager')->loadByBarcode($barcode);
      if ($user) {
        $jsonapi = \Drupal::service('jsonapi.entity.to_jsonapi');
        $response = $jsonapi->normalize($user);
      }
    }
    return $this->jsonResponse(['response' => $response]);
  }

  /**
   * Get the start value of the event date.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return string|null
   *   The date string for the event start value.
   */
  protected function getEventStart(NodeInterface $node) {
    if ($node->bundle() !== 'event') {
      return NULL;
    }
    return $node->get('field_date_time')->value;
  }

  /**
   * Get the start value of the event registration period.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return string|null
   *   The date string for the event registration start value.
   */
  protected function getEventRegistrationStart(NodeInterface $node) {
    if ($node->bundle() !== 'event') {
      return NULL;
    }
    return $node->get('field_event_register_period')->value;
  }

  /**
   * Get the end value of the event registration period.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return string|null
   *   The date string for the event registration end value.
   */
  protected function getEventRegistrationEnd(NodeInterface $node) {
    if ($node->bundle() !== 'event') {
      return NULL;
    }
    return $node->get('field_event_register_period')->end_value;
  }

  /**
   * Determines whether the event's start time is in the past.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return bool
   *   Whether the event has passed.
   */
  public function isEventStarted(NodeInterface $node) {
    if ($node->bundle() !== 'event') {
      return FALSE;
    }
    if ($start_date = $this->getEventStart($node)) {
      $now = new DrupalDateTime();
      $start_datetime = new DrupalDateTime($start_date);
      return $now->diff($start_datetime)->invert;
    }
    return FALSE;
  }

  /**
   * Determines whether the event's registration end time is in the past.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return bool
   *   Whether the event has passed.
   */
  public function isEventRegistrationEnded(NodeInterface $node) {
    if ($node->bundle() !== 'event') {
      return FALSE;
    }
    if ($end_date = $this->getEventRegistrationEnd($node)) {
      $now = new DrupalDateTime();
      $end_datetime = new DrupalDateTime($end_date);
      return $now->diff($end_datetime)->invert;
    }
    return FALSE;
  }

  /**
   * Determines whether the event node's waitlist option is enabled.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return bool
   *   Whether the event has a waitlist.
   */
  public function allowsWaitlist(NodeInterface $node) {
    if ($node->bundle() !== 'event') {
      return FALSE;
    }
    $has_waitlist = $node->get('field_has_waitlist');
    return !($has_waitlist->isEmpty() || $has_waitlist->getValue() == 0);
  }

  /**
   * Gets registrations for an event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   * @param string $status
   *   The status of the event registration.
   *
   * @return array
   *   An array of waitlisted registration entities.
   */
  public function getEventRegistrations(NodeInterface $node, $status = '') {
    if ($node->bundle() !== 'event') {
      return [];
    }
    $event_registration_storage = $this->entityTypeManager->getStorage('event_registration');
    $query = $event_registration_storage
      ->getQuery()
      ->condition('field_event.target_id', $node->id())
      ->sort('created', 'ASC');
    if ($status) {
      $query->condition('status', $status);
    }
    $registrations = $query->execute();
    return $event_registration_storage->loadMultiple($registrations);
  }

  /**
   * Gets the event's active registrants.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return int
   *   A whole number of attendees.
   */
  public function getEventActiveRegistrants(NodeInterface $node) {
    if ($node->bundle() !== 'event') {
      return 0;
    }
    if ($active_registrations = $this->getEventRegistrations($node, 'active')) {
      return array_reduce($active_registrations, function (&$total, $reg) {
        $total += $reg->total();
        return $total;
      }, 0);
    }
    return 0;
  }

  /**
   * Gets the event's max capacity.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return int
   *   The max capacity of the event.
   */
  public function getEventCapacity(NodeInterface $node) {
    if ($node->bundle() !== 'event') {
      return 0;
    }
    if (!$node->get('field_capacity_max')->isEmpty()) {
      return $node->field_capacity_max->value;
    }
    return 0;
  }

  /**
   * Gets the total number of attendees that can be added to an event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return int
   *   A whole number of attendees.
   */
  public function getEventOpenCapacity(NodeInterface $node) {
    if ($node->bundle() !== 'event') {
      return 0;
    }
    if ($max_capacity = $this->getEventCapacity($node)) {
      $active_registrants = $this->getEventActiveRegistrants($node);
      return $max_capacity - $active_registrants;
    }
    return 0;
  }

  /**
   * Gets the total number of attendees that can be added to an event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   */
  public function fillEventOpenCapacity(NodeInterface $node) {
    if ($node->bundle() !== 'event') {
      return;
    }
    if ($this->allowsWaitlist($node) && !$this->isEventRegistrationEnded($node) && !$this->isEventStarted($node) && ($open_capacity = $this->getEventOpenCapacity($node)) && ($waitlist = $this->getEventRegistrations($node, 'waitlist'))) {
      foreach ($waitlist as $waitlist_registrant) {
        $open_capacity = $this->getEventOpenCapacity($node);
        if ($waitlist_registrant->total() <= $open_capacity) {
          $waitlist_registrant->set('status', 'active');
          $waitlist_registrant->save();
        }
      }
    }
  }

  private function getRequestData(Request $request, $key) {
    $data = $request->getContent();
    if (!empty($data) && ($data = Json::decode($data))) {
      return !empty($data[$key]) ? $data[$key] : NULL;
    }
    return $request->get($key);
  }

  /**
   * Respond with json, check the response for errors and return 400.
   *
   * Otherwise return response with 200.
   *
   * @param array $data
   *   Array ['errors' => [], 'response' => []].
   */
  protected function jsonResponse(array $data) {
    if (isset($data['errors']) && !empty($data['errors'])) {
      return JsonResponse::create($data['errors'], 400);
    }

    return JsonResponse::create($data['response'], 200);
  }

}
