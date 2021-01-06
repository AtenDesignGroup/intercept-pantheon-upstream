<?php

namespace Drupal\intercept_event\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\inline_entity_form\ElementSubmit;
use Drupal\intercept_core\DateRangeFormatterTrait;
use Drupal\intercept_core\Utility\Dates;
use Drupal\intercept_event\RecurringEventManager;
use Drupal\intercept_event\Entity\EventRecurrenceInterface;
use Drupal\intercept_room_reservation\Entity\RoomReservationInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Event Recurrence edit forms.
 *
 * @ingroup intercept_event
 */
class EventRecurrenceEventsForm extends ContentEntityForm {

  use DateRangeFormatterTrait;

  /**
   * The Event Recurrence entity.
   *
   * @var \Drupal\intercept_event\Entity\EventRecurrenceInterface
   */
  private $eventRecurrence;

  /**
   * The Intercept recurring event manager.
   *
   * @var \Drupal\intercept_event\RecurringEventManager
   */
  protected $recurringEventManager;

  /**
   * The Intercept dates utility.
   *
   * @var \Drupal\intercept_core\Utility\Dates
   */
  protected $dateUtility;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, RecurringEventManager $recurring_event_manager, Dates $date_utility) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->recurringEventManager = $recurring_event_manager;
    $this->dateUtility = $date_utility;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('intercept_event.recurring_manager'),
      $container->get('intercept_core.utility.dates')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\node\Entity\Node */
    $entity = $this->entity;
    $this->eventRecurrence = $this->recurringEventManager->getBaseEventRecurrence($entity);

    $form = parent::buildForm($form, $form_state);

    $form['#theme'] = 'event_recurrence_event_form';

    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => $this->t('Recurring events for @title', [
        '@title' => $this->entity->label(),
      ]),
    ];

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Before generating all events, you can preview the dates. If you edit this event, you can then update the recurring events by either re-generating or updating.'),
    ];

    $form['event_list'] = [
      '#type' => 'container',
      '#title' => $this->t('Event list'),
    ];

    $form['event_list']['table'] = [
      '#type' => 'table',
      '#header' => ['Event ID', 'Date', '', ''],
      '#rows' => [],
    ];

    $nodes = $this->eventRecurrence->getEvents();
    if (!empty($nodes)) {
      foreach ([$this->entity->id() => $this->entity] + $nodes as $node) {
        $date_item = $node->get('field_date_time')->first();
        $start_date = $this->dateUtility->convertTimezone($date_item->start_date, 'default');
        $end_date = $this->dateUtility->convertTimezone($date_item->end_date, 'default');
        $reservation = \Drupal::service('intercept_core.reservation.manager')->getEventReservation($node);
        $column = [
          $node->id() == $this->entity->id() ? $this->t('Base event') : $node->link(),
          $this->formatDateRange([
            '@date' => $start_date->format($this->startDateFormat),
            '@time_start' => $start_date->format($this->startTimeFormat),
            '@time_end' => $end_date->format($this->endTimeFormat),
          ]),
          $node->link('edit event', 'edit-form'),
          $reservation ? $reservation->link('edit reservation', 'edit-form') : '',
        ];
        $form['event_list']['table']['#rows'][] = $column;
      }
    }
    else {
      $dates = $this->eventRecurrence->getDateOccurrences();
      // If the first computed recurrence date is the same as the base event
      // then label it as such. If not, then we add in the base event date
      // to make sure that is clear that that is an occurrence as well.
      foreach ($dates as $index => $date) {
        $column = [
          $index == 0 ? $this->t('Base event') : $this->t('Date preview, not created yet'),
          $this->formatDateRange([
            '@date' => $date->getStart()->format($this->startDateFormat),
            '@time_start' => $date->getStart()->format($this->startTimeFormat),
            '@time_end' => $date->getEnd()->format($this->endTimeFormat),
          ]),
          '',
          '',
        ];
        $form['event_list']['table']['#rows'][] = $column;
      }
    }

    $form['revision']['#access'] = FALSE;
    $form['revision_information']['#access'] = FALSE;
    $form['revision_log']['#access'] = FALSE;
    $form['advanced']['#access'] = FALSE;
    $form['#process'][] = '::processNodeForm';

    return $form;
  }

  /**
   * Process callback for EventAttendanceEvents form.
   *
   * @see \Drupal\Core\Entity\EntityForm::form()
   */
  public function processNodeForm($element, FormStateInterface $form_state, $form) {
    if (!empty($element['actions']['template_create'])) {
      $element['actions']['template_create']['#access'] = FALSE;
    }
    if (!empty($element['actions']['draft'])) {
      $element['actions']['draft']['#access'] = FALSE;
    }
    $element['menu']['#access'] = FALSE;
    return $element;
  }

  /**
   * Converts a date to the storage format's timezone.
   *
   * @param object $date
   *   The DateTime object.
   * @param string $timezone
   *   PHP Timezone name.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The converted DrupalDateTime object.
   */
  private function compensate($date, $timezone = 'default') {
    $converted = $this->dateUtility->convertTimezone($date, 'storage')
      ->format($this->dateUtility->getStorageFormat());
    $new_date = $this->dateUtility->getDrupalDate($converted, 'default');
    return $timezone == 'default' ? $new_date : $this->dateUtility->convertTimezone($new_date, 'storage');
  }

  /**
   * Submit handler to delete all events.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function deleteEvents(array &$form, FormStateInterface $form_state) {
    $nodes = $this->eventRecurrence->deleteEvents();
    \Drupal::service('messenger')->addStatus($this->t('@count recurring events deleted.', ['@count' => count($nodes)]));
  }

  /**
   * Submit handler to update existing events.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function updateEvents(array &$form, FormStateInterface $form_state) {
    // Cycle through events connected to this recurrence.
    $count = 0;
    $nodes = $this->eventRecurrence->getEvents();
    foreach ($nodes as $node) {
      // If this is the base event skip it.
      if ($node->id() == $this->entity->id()) {
        // We still want to count it in the message though.
        $count++;
        continue;
      }
      // Copy the fields over to the other events from the base event.
      foreach ($this->entity->getFields(FALSE) as $field_name => $field) {
        // TODO: This should be grabbed from form_state and processed
        // through EntityFormDisplay.
        if (in_array($field_name, [
          'event_recurrence',
          'nid',
          'vid',
          'type',
          'uuid',
          'field_date_time',
        ])) {
          continue;
        }
        $node->set($field_name, $field->getValue());
      }
      $node->save();
      $count++;
    }
    \Drupal::messenger()->addMessage($this->t('@count events updated.', ['@count' => $count]));
  }

  /**
   * Batch callback; initialize the number of events.
   */
  public static function batchStart($total, &$context) {
    $context['results']['events'] = $total;
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      if ($results['events']) {
        \Drupal::service('messenger')->addMessage(\Drupal::translation()
          ->formatPlural($results['events'], 'Generated 1 event.', 'Generated @count events.'));
      }
      else {
        \Drupal::service('messenger')
          ->addMessage(new TranslatableMarkup('No new events to generate.'));
      }
    }
    else {
      $error_operation = reset($operations);
      \Drupal::service('messenger')
        ->addMessage(new TranslatableMarkup('An error occurred while processing @operation with arguments : @args'), [
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[0]),
        ]);
    }
  }

  /**
   * Create event batch processing callback.
   */
  public function createProcess(NodeInterface $base_event, array $date_time, $recurrence) {
    $event = $base_event->createDuplicate();
    $event->set('field_date_time', $date_time);
    $event->set('event_recurrence', $recurrence->id());
    $event->save();
    $manager = \Drupal::service('intercept_core.reservation.manager');
    if ($reservation = $manager->getEventReservation($base_event)) {
      $this->generateReservation($event, $reservation, $recurrence);
    }
  }

  /**
   * Submit handler to generate events.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function generateEvents(array &$form, FormStateInterface $form_state) {
    /** @var Drupal\node\NodeInterface $base_event */
    $base_event = $this->entity;
    $storage_format = $this->eventRecurrence->getDateStorageFormat();
    $recurrence = $this->eventRecurrence;

    $dates = $this->eventRecurrence->getDateOccurrences();
    $first_date = $dates[0];
    if ($this->compensate($first_date->getStart())->format($storage_format) == $base_event->field_date_time->start_date->format($storage_format)) {
      array_shift($dates);
    }

    $batch = [
      'title' => $this->t('Bulk generating recurring events.'),
      'operations' => [
        ['Drupal\intercept_event\Form\EventRecurrenceEventsForm::batchStart', [count($dates)]],
      ],
      'finished' => 'Drupal\intercept_event\Form\EventRecurrenceEventsForm::batchFinished',
    ];

    foreach ($dates as $date) {
      $date_time = [
        'value' => $this->compensate($date->getStart())->format($storage_format),
        'end_value' => $this->compensate($date->getEnd())->format($storage_format),
      ];
      $batch['operations'][] = [
        [$this, 'createProcess'],
        [$base_event, $date_time, $recurrence],
      ];
    }

    batch_set($batch);
  }

  /**
   * Generates a new event cloned from the base event.
   *
   * @param \Drupal\node\NodeInterface $new_event
   *   The new event node entity.
   * @param \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $reservation
   *   The base reservation entity to clone.
   * @param \Drupal\intercept_event\Entity\EventRecurrenceInterface $event_recurrence
   *   The event recurrence entity.
   */
  protected function generateReservation(NodeInterface $new_event, RoomReservationInterface $reservation, EventRecurrenceInterface $event_recurrence) {
    $manager = \Drupal::service('intercept_core.reservation.manager');
    $storage_format = $event_recurrence->getDateStorageFormat();
    // This field originates from the value entered in on the event form. We
    // need to preserve the time that was entered, but change the date to the
    // recurring date that was calculated.
    $start_date = $this->dateUtility->convertTimezone($new_event->field_date_time->start_date, 'default');
    $end_date = $this->dateUtility->convertTimezone($new_event->field_date_time->end_date, 'default');
    $dates = clone $reservation->field_dates;
    $start_time = $this->dateUtility->convertTimezone($dates->start_date, 'default');
    $end_time = $this->dateUtility->convertTimezone($dates->end_date, 'default');
    $reservation_start = $this->dateUtility
      ->createDateFromArray(
        $start_date->format('Y'),
        $start_date->format('n'),
        $start_date->format('j'),
        $start_time->format('H'),
        $start_time->format('i'),
        $start_time->format('s')
      );
    $reservation_end = $this->dateUtility
      ->createDateFromArray(
        $end_date->format('Y'),
        $end_date->format('n'),
        $end_date->format('j'),
        $end_time->format('H'),
        $end_time->format('i'),
        $end_time->format('s')
      );
    $manager->createEventReservation($new_event, [
      'field_dates' => [
        'value' => $this->compensate($reservation_start)->format($storage_format),
        'end_value' => $this->compensate($reservation_end)->format($storage_format),
      ],
    ]);
  }

  /**
   * Submit handler to delete all events and regenerate.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function regenerateEvents(array &$form, FormStateInterface $form_state) {
    $this->deleteEvents($form, $form_state);
    $this->generateEvents($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // TODO: Create confirmation forms from these actions.
    $actions['events_generate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate events'),
      '#submit' => $this->submitHandlers(['::generateEvents']),
      '#access' => empty($this->eventRecurrence->getEvents()),
    ];

    $actions['events_regenerate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Re-generate events'),
      '#submit' => $this->submitHandlers(['::regenerateEvents']),
      '#access' => !empty($this->eventRecurrence->getEvents()),
    ];

    $actions['events_update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update events'),
      '#submit' => $this->submitHandlers(['::updateEvents']),
      '#access' => !empty($this->eventRecurrence->getEvents()),
    ];

    $actions['events_delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete events'),
      '#limit_validation_errors' => [],
      '#submit' => $this->submitHandlers(['::deleteEvents']),
      '#access' => !empty($this->eventRecurrence->getEvents()),
    ];
    $actions['submit']['#access'] = FALSE;
    $actions['delete']['#access'] = FALSE;

    return $actions;
  }

  /**
   * Attaches the inline_entity_form submit to the form.
   *
   * @param array $extra
   *   Additional submit handlers.
   *
   * @return array
   *   The submit handlers.
   */
  protected function submitHandlers(array $extra = []) {
    $ief = [[ElementSubmit::class, 'trigger']];
    return array_merge($ief, $extra);
  }

}
