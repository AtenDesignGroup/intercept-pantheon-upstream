<?php

namespace Drupal\intercept_event;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\date_recur\Rl\RlHelper;
use Drupal\node\NodeInterface;
use Drupal\intercept_core\Utility\Dates;
use Drupal\intercept_event\Entity\EventRecurrence;
use Drupal\intercept_event\Entity\EventRecurrenceInterface;

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
    $storage = \Drupal::service('entity_type.manager')->getStorage('event_recurrence');
    $recurrences = $storage->loadByProperties([
      'event' => $node->id(),
    ]);
    return $recurrences ? reset($recurrences) : FALSE;
  }

  /**
   * Creates an event recurrence base form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\node\NodeInterface $node
   *   The Event node.
   */
  private function eventRecurrenceBaseForm(array &$form, FormStateInterface $form_state, NodeInterface $node) {
    $r = &$form['recurring_event'];

    $recurrence = NULL;
    if (!$node->isNew()) {
      $storage = \Drupal::service('entity_type.manager')->getStorage('event_recurrence');
      $recurrences = $storage->loadByProperties([
        'event' => $node->id(),
      ]);
      $recurrence = $recurrences ? reset($recurrences) : NULL;
    }
    $r['#entity'] = $recurrence;

    $r['#attributes'] = [
      'class' => 'intercept-event-recurring-container',
      'data-intercept-event-recurring-name' => 'interval',
      'data-event-id' => $node->id(),
      // TODO: This is not ajax friendly.
      'data-start-date-selector' => '#edit-field-date-time-0-value-date',
      'data-end-date-selector' => '#edit-field-date-time-0-end-value-date',
      'data-start-time-selector' => '#edit-field-date-time-0-value-time',
      'data-end-time-selector' => '#edit-field-date-time-0-end-value-time',
    ];

    $r['#attached']['drupalSettings']['intercept']['events'][$node->id()] = [
      'hasRecurringEvents' => $recurrence && !empty($recurrence->getEvents()),
      'recurringEventCount' => $recurrence ? count($recurrence->getEvents()) : 0,
    ];

    $r['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => !empty($recurrence),
      '#attributes' => [
        'class' => [
          'intercept-event-recurring-enable',
        ],
      ],
    ];
    $readable = $recurrence ? $recurrence->getRecurReadable() : '';
    $rule_string = $recurrence ? $recurrence->field_event_rrule->rrule : '';
    $start_date = $recurrence && $recurrence->field_event_rrule->value ? $recurrence->field_event_rrule->value : 'now';
    $start_date_object = new \DateTime($start_date, new \DateTimeZone('UTC'));
    $end_date = $recurrence && $recurrence->field_event_rrule->end_value ? $recurrence->field_event_rrule->end_value : 'now';
    $end_date_object = new \DateTime($end_date, new \DateTimeZone('UTC'));
    $data_name = 'data-intercept-event-recurring-name';

    if ($rule_string) {
      $rule = new RlHelper($rule_string, $start_date_object, $end_date_object);
      $rules = $rule->getRules();
      $parts = $rules[0]->getParts();
      $freq = $this->getFrequencyOption($parts['FREQ'], 1);
      $end_type = !empty($parts['UNTIL']) ? 1 : 0;
    }

    $r['readable_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Summary'),
      '#disabled' => TRUE,
      '#default_value' => isset($readable) ? $readable : '',
      '#attributes' => [
        'class' => [
          'intercept-event-recurring-readable',
        ],
      ],
      '#states' => [
        'visible' => [
          ':input[name="recurring_event[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $r['date'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="recurring_event[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $r['date']['start'] = [
      '#title' => $this->t('Start date'),
      '#type' => 'date',
      '#default_value' => $start_date_object->format('Y-m-d'),
    ];

    $r['raw_value'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Raw value'),
      '#attributes' => [
        'class' => [
          'intercept-event-recurring-raw',
        ],
      ],
      '#default_value' => isset($rule_string) ? $rule_string : '',
    ];

    $r['interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Repeat every'),
      '#default_value' => 1,
      '#attributes' => [
        'class' => ['intercept-event-recurring-value'],
        $data_name => 'interval',
      ],
      '#states' => [
        'visible' => [
          ':input[name="recurring_event[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $r['freq'] = [
      '#type' => 'select',
      '#title' => $this->t('Frequency'),
      '#options' => ['Years', 'Months', 'Weeks', 'Days', 'Hours', 'Minutes'],
      '#attributes' => [
        'class' => ['intercept-event-recurring-value'],
        $data_name => 'freq',
      ],
      '#default_value' => isset($freq) ? $freq : 2,
      '#states' => [
        'visible' => [
          ':input[name="recurring_event[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $r['end_type'] = [
      '#type' => 'select',
      '#options' => [$this->t('End after number of times'), $this->t('End on date')],
      '#attributes' => [
        'class' => ['intercept-event-recurring-end-type'],
      ],
      '#default_value' => isset($end_type) ? $end_type : 0,
      '#states' => [
        'visible' => [
          ':input[name="recurring_event[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $r['end']['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of times'),
      '#attributes' => [
        'class' => ['intercept-event-recurring-value'],
        $data_name => 'count',
      ],
      '#states' => [
        'visible' => [
          ':input[name="recurring_event[enabled]"]' => ['checked' => TRUE],
          ':input[name="recurring_event[end_type]"]' => ['value' => 0],
        ],
      ],
    ];

    $r['end']['until'] = [
      '#title' => $this->t('End date'),
      '#type' => 'date',
      '#attributes' => [
        'class' => ['intercept-event-recurring-value'],
        'type' => 'date',
        $data_name => 'until',
      ],
      '#states' => [
        'visible' => [
          ':input[name="recurring_event[enabled]"]' => ['checked' => TRUE],
          ':input[name="recurring_event[end_type]"]' => ['value' => 1],
        ],
      ],
    ];

    if (!empty($parts['UNTIL'])) {
      $date = $parts['UNTIL'];
      $r['end']['until']['#default_value'] = $date->format('Y-m-d');
    }
    $form['recurring_event']['#attached']['library'][] = 'intercept_event/event_recurring';
  }

  /**
   * Creates a form for the event recurrence.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\intercept_event\Entity\EventRecurrenceInterface $recurrence
   *   The Event Recurrence entity.
   */
  private function eventRecurrenceForm(array &$form, FormStateInterface $form_state, EventRecurrenceInterface $recurrence) {
    $form['recurring_event']['message'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('This event is part of an event recurrence.'),
    ];

    $form['recurring_event']['link'] = [
      '#markup' => $this->t('Edit the @link', [
        '@link' => $recurrence->event->entity->toLink('original', 'edit-form')->toString(),
      ]),
    ];

    $form['recurring_event']['remove_from_recurrence'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove from recurrence'),
    ];

    $form['event_recurrence']['#access'] = FALSE;
  }

  /**
   * Alter callback for the event form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function nodeFormAlter(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getFormObject()->getFormDisplay($form_state)->getComponent('recurring_event')) {
      return;
    }
    // Use in submit and validate handlers but do not actually submit.
    $node = $form_state->set('recurring_event_manager', $this)->getFormObject()->getEntity();

    $form['recurring_event'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Recurring'),
      '#weight' => 10,
      '#tree' => TRUE,
    ];

    if (($recurrence = $node->event_recurrence->entity) && $recurrence->event->entity != $node) {
      $this->eventRecurrenceForm($form, $form_state, $recurrence);
    }
    else {
      // If this is the base event we let them add/edit their recurrence.
      $this->eventRecurrenceBaseForm($form, $form_state, $node);
    }

    $form['#validate'][] = [static::class, 'nodeFormValidate'];
    $form['actions']['submit']['#submit'][] = [static::class, 'nodeFormSubmit'];
  }

  /**
   * Form validation for the event.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function nodeFormValidate(array &$form, FormStateInterface $form_state) {
    $recurring = $form_state->getValue('recurring_event');
    if (!empty($recurring['enabled']) && empty($recurring['end']['count']) && empty($recurring['end']['until'])) {
      $message = new TranslatableMarkup('Enabling recurring events requires either an end date, or a total repeat count.');
      $form_state->setError($form['recurring_event']['end_type'], $message);
    }
  }

  /**
   * Form submit handler for the event.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function nodeFormSubmit(array &$form, FormStateInterface $form_state) {
    $recurring = $form_state->getValue('recurring_event');
    $recurrence = $form_state->getValue('event_recurrence');

    $manager = $form_state->get('recurring_event_manager');
    $node = $form_state->getFormObject()->getEntity();

    // If the checkbox is disabled and this is a base event.
    if (empty($recurring['enabled']) && ($recurrence = $manager->getBaseEventRecurrence($node))) {
      $existing_events = $recurrence->getEvents();
      if (!empty($existing_events)) {
        $nodes = $recurrence->deleteEvents();
        $manager->messenger->addStatus(new TranslatableMarkup('@count recurring events deleted.', ['@count' => count($nodes)]));
      }
      $recurrence->delete();
    }
    if (!empty($recurring['remove_from_recurrence'])) {
      $node = $form_state->getFormObject()->getEntity();
      $node->event_recurrence->setValue(NULL);
      $node->save();
      return;
    }

    if ($recurring['enabled']) {
      $recurring_event = $form['recurring_event']['#entity'];
      if (!$recurring_event) {
        $recurring_event = EventRecurrence::create([
          'event' => $form_state->getFormObject()->getEntity()->id(),
        ]);
      }

      $node = $form_state->getFormObject()->getEntity();

      $recurring_start_datetime = clone $node->field_date_time->start_date;

      $recurring_end_datetime = clone $node->field_date_time->end_date;

      $recurring_event->field_event_rrule->setValue([
        'value' => $recurring_start_datetime
          ->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'end_value' => $recurring_end_datetime
          ->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'rrule' => $recurring['raw_value'],
        // TODO: This should be customizable.
        'infinit' => 0,
        'timezone' => $manager->dateUtility()->getDefaultTimezone()->getName(),
      ]);

      $recurring_event->save();

      $node->event_recurrence->setValue($recurring_event->id());
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
