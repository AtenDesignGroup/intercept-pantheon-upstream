<?php

namespace Drupal\intercept_event;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\intercept_core\Utility\Dates;

class RecurringEventManager {

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
   * @var MessengerInterface
   */
  protected $messenger;

  /**
   * @var Dates
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
   * @return MessengerInterface
   */
  public function messenger() {
    return $this->messenger;
  }

  public function dateUtility() {
    return $this->dateUtility;
  }

  /**
   * Check bundle access and permissions.
   */
  public function isRecurrenceBaseEvent(\Drupal\node\NodeInterface $node) {
    return \Drupal\Core\Access\AccessResult::allowedIf($this->getBaseEventRecurrence($node));
  }

  /**
   * Gets the event recurrence if this event is a base event.
   */
  public function getBaseEventRecurrence(\Drupal\node\NodeInterface $node) {
    $storage = \Drupal::service('entity_type.manager')->getStorage('event_recurrence');
    $recurrences = $storage->loadByProperties([
      'event' => $node->id(),
    ]);
    return $recurrences ? reset($recurrences) : FALSE;
  }

  private function eventRecurrenceBaseForm(&$form, FormStateInterface $form_state, $node) {
    $r = &$form['recurring_event'];

    $recurrence = NULL;
    if (!$node->isNew()) {
      $storage = \Drupal::service('entity_type.manager')->getStorage('event_recurrence');
      $recurrences = $storage->loadByProperties([
        'event' => $node->id(),
      ]);
      $recurrence = $recurrences ? reset($recurrences) : NULL;
    }
    $form['recurring_event']['#entity'] = $recurrence;

    $form['recurring_event']['#attributes'] = [
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

    $form['recurring_event']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => !empty($recurrence),
      '#attributes' => [
        'class' => [
          'intercept-event-recurring-enable',
        ],
      ],
    ];
    $readable = $recurrence ? $recurrence->getRecurHandler()->humanReadable() : '';
    $handler = $recurrence ? $recurrence->getRecurHandler() : FALSE;
    $rule_string = $recurrence ? $recurrence->field_event_rrule->rrule : '';
    $start_date = $recurrence && $recurrence->field_event_rrule->value ? $recurrence->field_event_rrule->value : 'now';
    $start_date_object = new \Drupal\Core\Datetime\DrupalDateTime($start_date, 'UTC');
    $end_date = $recurrence && $recurrence->field_event_rrule->end_value ? $recurrence->field_event_rrule->end_value : 'now';
    $end_date_object = new \Drupal\Core\Datetime\DrupalDateTime($end_date, 'UTC');
    $data_name = 'data-intercept-event-recurring-name';

    if ($rule_string) {
      $rule = new \Drupal\date_recur\DateRecurRRule($rule_string, $start_date_object, $end_date_object, 'UTC');
      $parts = $rule->getParts();
      $freq = $this->getFrequencyOption($parts['FREQ'], 1);
      $end_type = !empty($parts['UNTIL']) ? 1 : 0;
    }

    $enabled_state = [
      'visible' => [
        ':input[name="recurring_event[enabled]"]' => ['checked' => TRUE],
      ]
    ];

    $r['readable_value'] = [
      '#type' => 'textfield',
      '#title' => t('Summary'),
      '#disabled' => TRUE,
      '#default_value' => isset($readable) ? $readable : '',
      '#attributes' => [
        'class' => [
          'intercept-event-recurring-readable',
        ],
      ],
      '#states' => $enabled_state,
    ];

    $r['date'] = [
      '#type' => 'container',
      '#states' => $enabled_state,
    ];

    $r['date']['start'] = [
      '#title' => t('Start date'),
      '#type' => 'date',
      '#default_value' => $start_date_object->format('Y-m-d'),
    ];

    $r['raw_value'] = [
      '#type' => 'hidden',
      '#title' => t('Raw value'),
      '#attributes' => [
        'class' => [
          'intercept-event-recurring-raw',
        ],
      ],
      '#default_value' => isset($rule_string) ? $rule_string : '',
    ];

    $r['interval'] = [
      '#type' => 'number',
      '#title' => t('Repeat every'),
      '#default_value' => 1,
      '#attributes' => [
        'class' => ['intercept-event-recurring-value'],
        $data_name => 'interval',
      ],
      '#states' => [
        'visible' => [
          ':input[name="recurring_event[enabled]"]' => ['checked' => TRUE],
        ]
      ],
    ];

    $r['freq'] = [
      '#type' => 'select',
      '#title' => t('Frequency'),
      '#options' => ['Years', 'Months', 'Weeks', 'Days', 'Hours', 'Minutes', 'Seconds'],
      '#attributes' => [
        'class' => ['intercept-event-recurring-value'],
        $data_name => 'freq',
      ],
      '#default_value' => isset($freq) ? $freq : 2,
      '#states' => [
        'visible' => [
          ':input[name="recurring_event[enabled]"]' => ['checked' => TRUE],
        ]
      ],
    ];

    $r['end_type'] = [
      '#type' => 'select',
      '#options' => [t('End after number of times'), t('End on date')],
      '#attributes' => [
        'class' => ['intercept-event-recurring-end-type'],
      ],
      '#default_value' => isset($end_type) ? $end_type : 0,
      '#states' => [
        'visible' => [
          ':input[name="recurring_event[enabled]"]' => ['checked' => TRUE],
        ]
      ],
    ];

    $r['end']['count'] = [
      '#type' => 'number',
      '#title' => t('Number of times'),
      '#attributes' => [
        'class' => ['intercept-event-recurring-value'],
        $data_name => 'count',
      ],
      '#states' => [
        'visible' => [
          ':input[name="recurring_event[enabled]"]' => ['checked' => TRUE],
          ':input[name="recurring_event[end_type]"]' => ['value' => 0],
        ]
      ],
    ];

    $r['end']['until'] = [
      '#title' => t('End date'),
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
        ]
      ],
    ];

    if (!empty($parts['UNTIL'])) {
      $date = $parts['UNTIL'];
      $r['end']['until']['#default_value'] = $date->format('Y-m-d');
    }
    $form['recurring_event']['#attached']['library'][] = 'intercept_event/event_recurring';
  }

  private function eventRecurrenceForm(&$form, FormStateInterface $form_state, $recurrence) {
    $form['recurring_event']['message'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('This event is part of an event recurrence.'),
    ];
    $form['recurring_event']['link'] = [
      '#markup' => $this->t('Edit the @link', [
        '@link' => $recurrence->event->entity->link('original', 'edit-form'),
      ])
    ];

    $form['recurring_event']['remove_from_recurrence'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove from recurrence'),
    ];
  }

  public function nodeFormAlter(&$form, FormStateInterface $form_state) {
    if (!$form_state->getFormObject()->getFormDisplay($form_state)->getComponent('recurring_event')) {
      return;
    }
    // Use in submit and validate handlers but do not actually submit.
    $node = $form_state->set('recurring_event_manager', $this)->getFormObject()->getEntity();

    $form['recurring_event'] = [
      '#type' => 'fieldset',
      '#title' => t('Recurring'),
      '#weight' => 10,
      '#tree' => TRUE,
    ];

    if ($recurrence = $node->event_recurrence->entity) {
      $this->eventRecurrenceForm($form, $form_state, $recurrence);
    }
    else {
      // If this is the base event we let them add/edit their recurrence.
      $this->eventRecurrenceBaseForm($form, $form_state, $node);
    }

    $form['#validate'][] = [static::class, 'nodeFormValidate'];
    $form['actions']['submit']['#submit'][] = [static::class, 'nodeFormSubmit'];
  }

  public static function nodeFormValidate(&$form, FormStateInterface $form_state) {
    $recurring = $form_state->getValue('recurring_event');
    if (!empty($recurring['enabled']) && empty($recurring['end']['count']) && empty($recurring['end']['until'])) {
      $message = t('Enabling recurring events requires either an end date, or a total repeat count.');
      $form_state->setError($form['recurring_event']['end_type'], $message);
    }
  }

  public static function nodeFormSubmit(&$form, $form_state) {
    $recurring = $form_state->getValue('recurring_event');

    $manager = $form_state->get('recurring_event_manager');
    $node = $form_state->getFormObject()->getEntity();

    // If the checkbox is disabled and this is a base event.
    if (empty($recurring['enabled']) && ($recurrence = $manager->getBaseEventRecurrence($node))) {
      $existing_events = $recurrence->getEvents();
      if (!empty($existing_events)) {
        $nodes = $recurrence->deleteEvents();
        $manager->messenger->addStatus(t('@count recurring events deleted.', ['@count' => count($nodes)]));
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
        $recurring_event = \Drupal\intercept_event\Entity\EventRecurrence::create([
          'event' => $form_state->getFormObject()->getEntity()->id(),
        ]);
      }

      $node = $form_state->getFormObject()->getEntity();

      $submitted_start_date = new \DateTime($recurring['date']['start'], new \DateTimeZone('UTC'));
      $recurring_start_datetime = clone $node->field_date_time->start_date;
      $recurring_start_datetime->setDate(
        $submitted_start_date->format('Y'),
        $submitted_start_date->format('m'),
        $submitted_start_date->format('d'));

      $recurring_end_datetime = clone $node->field_date_time->end_date;
      $recurring_end_datetime->setDate(
        $submitted_start_date->format('Y'),
        $submitted_start_date->format('m'),
        $submitted_start_date->format('d'));

      $recurring_event->field_event_rrule->setValue([
        //'value' => $recurring_start_datetime->format(DATETIME_DATETIME_STORAGE_FORMAT),
        //'end_value' => $recurring_end_datetime->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'value' => $manager->dateUtility()
          ->convertTimezone($recurring_start_datetime, 'default')
          ->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'end_value' => $manager->dateUtility()
          ->convertTimezone($recurring_end_datetime, 'default')
          ->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'rrule' => $recurring['raw_value'],
        // TODO: This should be customizable.
        'infinit' => 0,
        // FIXME
        'timezone' => $manager->dateUtility()->getDefaultTimezone()->getName(),
      ]);

      $recurring_event->save();
    }
  }

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

  private function getFrequencyOption($value, $type = 0) {
    $options = array_flip($this->getFrequencyOptions($type));
    return !empty($options[$value]) ? $options[$value] : FALSE;
  }

  private function getFrequencyOptions($type = 0) {
    return array_column($this->getFrequencies(), $type);
  }
}
