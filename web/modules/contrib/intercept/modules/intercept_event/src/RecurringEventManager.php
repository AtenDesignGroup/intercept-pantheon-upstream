<?php

namespace Drupal\intercept_event;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\intercept_core\Utility\Dates;

/**
 * Recurring event manager.
 */
class RecurringEventManager {

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
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The Intercept Dates utility.
   *
   * @var \Drupal\intercept_core\Utility\Dates
   */
  protected $dateUtility;

  /**
   * Constructs a new EventManager object.
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, Dates $date_utility) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->dateUtility = $date_utility;
  }

  /**
   * The messenger service.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface
   *   The messenger service.
   */
  public function messenger() {
    return $this->messenger;
  }

  /**
   * The Intercept Dates utility.
   *
   * @return \Drupal\intercept_core\Utility\Dates
   *   The Intercept Dates utility.
   */
  public function dateUtility() {
    return $this->dateUtility;
  }

  /**
   * Check bundle access and permissions.
   */
  public function isRecurrenceBaseEvent(NodeInterface $node) {
    return AccessResult::allowedIf($this->getBaseEventRecurrence($node));
  }

  /**
   * Gets the event recurrence if this event is a base event.
   */
  public function getBaseEventRecurrence(NodeInterface $node) {
    if (!$node->id() || $node->bundle() != 'event') {
      return FALSE;
    }
    $event_recurrences = $node->get('event_recurrence')->referencedEntities();
    if (empty($event_recurrences)) {
      return FALSE;
    }
    foreach ($event_recurrences as $recurrence) {
      /** @var \Drupal\intercept_event\Entity\EventRecurrenceInterface $recurrence */
      if ($recurrence->getBaseEventId() == $node->id()) {
        return $recurrence;
      }
    }
    return FALSE;
  }

  /**
   * Determines whether the event is part of a recurring event or not.
   */
  public function isRecurringEvent(NodeInterface $node) {
    $nid = $node->id();
    // Join the event recurrence id of the node to table event_recurrence and
    // make sure that there's a value under the event column in that table.
    // If there isn't one, then we should return false.
    $database = \Drupal::database();
    $query = $database->select('node_field_data', 'n');
    $query->fields('n', ['nid']);
    $query->condition('n.type', 'event');
    $query->condition('n.nid', $nid);
    $query->join('event_recurrence', 'er', 'er.id = n.event_recurrence');
    $query->isNotNull('er.event');
    $result = $query->countQuery()->execute()->fetchField();

    if (!empty($result) && $result == '1') {
      return TRUE;
    }
    return FALSE;
}

  /**
   * Form submit handler for the event.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\intercept_event\RecurringEventManager::iefEntityFormAlter()
   */
  public static function nodeFormSubmit(array &$form, FormStateInterface $form_state) {
    if ($event_recurrence = $form_state->get('save_base_event')) {
      $node = $form_state->getFormObject()->getEntity();
      $event_recurrence->set('event', $node);
      $event_recurrence->save();
    }
  }

  /**
   * Alters widget form for $node['event_recurrence'][cardinality]['widget'].
   *
   * @param array $elements
   *   EventRecurrence entity reference field widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Base node event form $form_state.
   * @param array $context
   *   Array of context including field info.
   */
  public function entityReferenceWidgetFormAlter(array &$elements, FormStateInterface $form_state, array &$context) {
    // We add recurring functionality here so that we don't hardcode the field
    // name for the entity reference.
    $form_state->set('recurring_event_manager', $this);
    $event_recurrence = $elements['inline_entity_form']['#default_value'];
    $node = $form_state->getFormObject()->getEntity();

    if ($event_recurrence && $event_recurrence->event->entity != $node) {
      // Hide inline entity form and add remove from recurrence.
      $this->eventRecurrenceForm($elements, $form_state);
    }
    else {
      // If this is the base event we let them add/edit their recurrence.
      $this->eventRecurrenceBaseForm($elements, $form_state);
    }
  }

  /**
   * Helper function for an event node base recurrence.
   *
   * @param array $elements
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\intercept_event\entityReferenceWidgetFormAlter()
   */
  private function eventRecurrenceBaseForm(array &$elements, FormStateInterface $form_state) {
    $event_recurrence = $elements['inline_entity_form']['#default_value'];
    $node = $form_state->getFormObject()->getEntity();

    $elements['#attributes'] = [
      'class' => 'intercept-event-recurring-container',
      'data-event-id' => $node->id(),
    ];

    $elements['#attached']['drupalSettings']['intercept']['events'][$node->id()] = [
      'hasRecurringEvents' => $event_recurrence && !empty($event_recurrence->getEvents()),
      'recurringEventCount' => $event_recurrence ? count($event_recurrence->getEvents()) : 0,
    ];

    $elements['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#weight' => -5,
      '#default_value' => !empty($event_recurrence),
      '#attributes' => [
        'class' => [
          'intercept-event-recurring-enable',
        ],
      ],
    ];

    $elements['inline_entity_form']['#states']['visible'] = [
      ':input[name="event_recurrence[0][enabled]"]' => ['checked' => TRUE],
    ];
    $elements['#attached']['library'][] = 'intercept_event/event_recurring';
  }

  /**
   * Helper function for an event node non-base recurrence.
   *
   * @param array $elements
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\intercept_event\entityReferenceWidgetFormAlter()
   */
  private function eventRecurrenceForm(array &$elements, FormStateInterface $form_state) {
    if (!$event_recurrence = $elements['inline_entity_form']['#default_value']) {
      return;
    }

    $elements['message'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('This event is part of an event recurrence.'),
    ];

    if ($event_recurrence->event->entity) {
      $elements['link'] = [
        '#markup' => $this->t('Edit the @link', [
          '@link' => $event_recurrence->event->entity->toLink('original', 'edit-form')->toString(),
        ]),
      ];
    }

    $elements['remove_from_recurrence'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove from recurrence'),
    ];

    $elements['inline_entity_form']['#access'] = FALSE;
  }

  /**
   * Alters widget for $node['event_recurrence']...['inline_entity_form'].
   *
   * @see intercept_event_inline_entity_form_entity_form_alter()
   */
  public function iefEntityFormAlter(&$entity_form, &$form_state) {
    if ($entity_form['#entity_type'] == 'event_recurrence' && $entity_form['#form_mode'] == 'events') {
      // This can't be done on the EventRecurrenceForm entity form directly
      // because that is not called by the inline entity form.
      $entity_form['revision_log_message']['#access'] = FALSE;
      // This will allow us to:
      // - Flag the entity for removal
      // - Act on any recurring event nodes
      // - Add the current event node as the base value if needed
      // We can't use a submit handler for the node edit form because that
      // will already have saved and/or deleted the EventRecurrence entity.
      $entity_form['#ief_element_submit'][] = [$this, 'iefEntityFormSubmit'];
      $complete_form = &$form_state->getCompleteForm();
      $complete_form['actions']['submit']['#submit'][] = [$this, 'nodeFormSubmit'];
    }
  }

  /**
   * Custom IEF submit added to #ief_element_submit.
   *
   * @see \Drupal\intercept_event\RecurringEventManager::iefEntityFormAlter()
   */
  public function iefEntityFormSubmit(&$entity_form, FormStateInterface $form_state) {
    $recurring_event_manager = $form_state->get('recurring_event_manager');

    $event_recurrence = $entity_form['#entity'];
    $recurring = $form_state->getValue(['event_recurrence', 0]);
    if (($recurring['enabled'] !== 0) && $event_recurrence->isNew()) {
      $form_state->set('save_base_event', $event_recurrence);
    }

    $node = $form_state->getFormObject()->getEntity();

    $complete_form = &$form_state->getCompleteForm();
    $inline_entity_form = &NestedArray::getValue($complete_form, $entity_form['#array_parents']);

    if ($recurring['enabled'] == 0) {
      // Now set the parent entity to remove the field value.
      // $entity_form is by reference but will not change the value of the
      // field.
      $inline_entity_form['#entity'] = NULL;

      // If the checkbox is disabled and this is a base event.
      if ($recurring_event_manager->getBaseEventRecurrence($node)) {
        $existing_events = $event_recurrence->getEvents();
        if (!empty($existing_events)) {
          $nodes = $event_recurrence->deleteEvents();
          $recurring_event_manager->messenger->addStatus(new TranslatableMarkup('@count recurring events deleted.', ['@count' => count($nodes)]));
        }
        // Flag to be deleted in WidgetSubmit::doSubmit().
        $widget_state = &static::getWidgetState($form_state);
        $widget_state['delete'][] = $widget_state['entities'][0]['entity'];
        unset($widget_state['entities'][0]);
      }
    }

    // If this is NOT a base event and we're removing it from the recurrence.
    if (!empty($recurring['remove_from_recurrence'])) {
      $inline_entity_form['#entity'] = NULL;
    }
  }

  /**
   * Return the current state of the inline entity form from $form_state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current node edit form $form_state.
   *
   * @return mixed|null
   *   Array of entities, delete, and instance.
   */
  protected static function &getWidgetState(FormStateInterface $form_state) {
    foreach ($form_state->get('inline_entity_form') as $hash => $widget_state) {
      $entity = !empty($widget_state['entities']) ? $widget_state['entities'][0]['entity'] : NULL;
      if (!$entity || $entity->getEntityTypeId() != 'event_recurrence') {
        continue;
      }
      $widget_state = &$form_state->get(['inline_entity_form', $hash]);
      return $widget_state;
    }
    return NULL;
  }

  /**
   * Alters widget for field_event_rrule.
   *
   * @param array $elements
   *   EventRecurrence entity reference field widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Base node event form $form_state.
   * @param array $context
   *   Array of context including field info.
   */
  public function dateRecurWidgetFormAlter(array &$elements, FormStateInterface $form_state, array $context) {
    // This allows us to:
    // - Populate the start and end date BEFORE the $form_state values get
    // added to the entity through
    // DateRecurModularAlphaWidget::extractFormValues().
    // - Validate if 'infinite' and not hardcoding the form path to here.
    // - Hide start, end, and time_zone without hardcoding the form path here.
    $elements['#element_validate'][] = [static::class, 'dateRecurWidgetValidate'];
    $elements['start']['#access'] = FALSE;
    $elements['end']['#access'] = FALSE;
    $elements['time_zone']['#access'] = FALSE;
  }

  /**
   * Custom validate hook for the date recur widget form.
   *
   * @see dateRecurWidgetFormAlter()
   */
  public static function dateRecurWidgetValidate(&$element, FormStateInterface $form_state, &$complete_form) {
    $enabled = $form_state->getValue(['event_recurrence', 0, 'enabled']);
    $rrule = &$form_state->getValue($element['#parents']);
    $event_date = $form_state->getValue(['field_date_time', 0]);

    $rrule['start'] = $event_date['value'];
    $rrule['end'] = $event_date['end_value'];
    if ($enabled && $rrule['ends_mode'] == 'infinite') {
      $message = new TranslatableMarkup('Enabling recurring events requires either an end date, or a total repeat count.');
      $form_state->setError($element['ends_mode'], $message);
    }
  }

  /**
   * Get the mapped frequency options.
   *
   * @return array
   *   The mapped frequency options.
   */
  private function getFrequencies() {
    $options = [
      0 => ['Years', 'YEARLY'],
      1 => ['Months', 'MONTHLY'],
      2 => ['Weeks', 'WEEKLY'],
      3 => ['Days', 'DAILY'],
      4 => ['Hours', 'HOURLY'],
      5 => ['Minutes', 'MINUTELY'],
      6 => ['Seconds', 'SECONDLY'],
    ];
    return $options;
  }

  /**
   * Gets a single frequency option.
   */
  private function getFrequencyOption($value, $type = 0) {
    $options = array_flip($this->getFrequencyOptions($type));
    return !empty($options[$value]) ? $options[$value] : FALSE;
  }

  /**
   * Gets the frequency options.
   */
  private function getFrequencyOptions($type = 0) {
    return array_column($this->getFrequencies(), $type);
  }

}
