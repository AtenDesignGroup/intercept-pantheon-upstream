<?php

namespace Drupal\intercept_bulk_room_reservation\Form;

use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\intercept_room_reservation\Entity\RoomReservation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\intercept_bulk_room_reservation\SeriesGeneratorInterface;
use Drupal\intercept_bulk_room_reservation\Entity\BulkRoomReservation;

/**
 * Form controller for the bulk room reservation entity edit forms.
 */
class BulkRoomReservationForm extends ContentEntityForm {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\RevisionLogInterface
   */
  protected $entity;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Drupal\intercept_bulk_room_reservation\SeriesGeneratorInterface definition.
   *
   * @var \Drupal\intercept_bulk_room_reservation\SeriesGeneratorInterface
   */
  protected $bulkReservationsSeriesGenerator;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\intercept_bulk_room_reservation\SeriesGeneratorInterface $bulkReservationsSeriesGenerator
   *   The series generator.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The current user account proxy.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, SeriesGeneratorInterface $bulkReservationsSeriesGenerator, AccountProxy $currentUser) {
    $this->entityRepository = $entity_repository;
    $this->entityTypeBundleInfo = $entity_type_bundle_info ?: \Drupal::service('entity_type.bundle.info');
    $this->time = $time ?: \Drupal::service('datetime.time');
    $this->bulkReservationsSeriesGenerator = $bulkReservationsSeriesGenerator;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('intercept_bulk_room_reservation.series_generator'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->getEntity();
    $input = $form_state->getUserInput();

    // Pre-populate the start and end points so that no component of
    // field_date_time is empty.
    $values = [];
    $start = new DrupalDateTime();
    $start = static::incrementRound($start, 900);
    $end = new DrupalDateTime('+1 hour');
    $end = static::incrementRound($end, 900);
    $values['start'] = $start;
    $values['end'] = $end;
    foreach (['start', 'end'] as $endpoint) {
      if (!$form['field_date_time']['widget'][0][$endpoint]['#default_value']) {
        $form['field_date_time']['widget'][0][$endpoint]['#default_value'] = $values[$endpoint];
      }
    }

    // Determine available rooms only if the date and location fields have
    // values.
    $roomOptions = $roomAttributes = [];
    $endDate = (array_key_exists('field_date_time', $input)) ? $input['field_date_time'][0]['end']['date'] : NULL;
    if ((!empty($endDate) && !empty($input['field_location'])) || !$entity->isNew()) {
      $room_data = $this->bulkReservationsSeriesGenerator->generateSeries($form_state, 'room_data');
      $roomOptions = $room_data['room_options'];
      $roomAttributes = $room_data['room_attributes'];
    }

    $form['field_room']['#prefix'] = '<div id="rooms-wrapper">';
    $form['field_room']['#suffix'] = '</div>';
    $form['field_room']['widget']['#options'] = $roomOptions;
    $form['field_room']['widget']['#options_attributes'] = $roomAttributes;

    $form['field_location']['widget']['#ajax'] = [
      'callback' => [$this, 'roomCallback'],
      'wrapper' => 'rooms-wrapper',
      'event' => 'change',
      'progress' => [
        'type' => 'throbber',
        'message' => $this->t('Getting room options...'),
      ],
    ];

    if (!$entity->isNew()) {
      // @todo See if $this has any overridden room reservations and render this
      // field only if it does.
      $overridden = !empty($entity->field_overridden->referencedEntities());
      $form['update_overridden'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Update overridden'),
        '#description' => $this->t('This bulk room reservation has occurrences that have been overridden. Check this box if you would like changes you make to this bulk room reservation to apply to its overridden occurrences in addition to its occurrences that have not been overridden.'),
        '#weight' => 10,
      ];
    }

    $form['#validate'][] = '::validateForm';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Ensure that the user hasn't entered an end date that preceeds the start.
    $dates = reset($form_state->getValue('field_date_time'));
    if ($dates['start'] > $dates['end']) {
      $message = $this->t('The "Ends on" date must not be earlier than the "Starts on" date.');
      $form_state->setErrorByName('field_date_time', $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => \Drupal::service('renderer')->render($link)];

    // Determine operation based on $result.
    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New bulk room reservation %label has been created.', $message_arguments));
        $this->logger('intercept_bulk_room_reservation')->notice('Created new bulk room reservation %label', $logger_arguments);
        $dates = $this->bulkReservationsSeriesGenerator->generateSeries($form_state, 'dates');

        // Create, edit or delete room_reservations using a batch process.
        $batch = $this->createBatch($dates, 'create', $form_state);
        batch_set($batch);

        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The bulk room reservation %label has been updated.', $message_arguments));
        $this->logger('intercept_bulk_room_reservation')->notice('Updated bulk room reservation %label.', $logger_arguments);

        // Overview: Unless we're not updating overridden room reservations,
        // delete all related room reservations and create new ones. If we're
        // not updating overridden room reservations, leave the overridden ones
        // as is and leave them in field_related_room_reservations and
        // field_overridden.
        // First, delete current room reservations related to this bulk room
        // reservation.
        $batch = $this->createBatch([], 'delete', $form_state);
        batch_set($batch);

        // Now add room reservations for the series of $dates.
        $dates = $this->bulkReservationsSeriesGenerator->generateSeries($form_state, 'dates');
        $batch = $this->createBatch($dates, 'create', $form_state);
        batch_set($batch);

        break;

      case SAVED_DELETED:
        $this->messenger()->addStatus($this->t('The bulk room reservation %label has been deleted.', $message_arguments));
        $this->logger('intercept_bulk_room_reservation')->notice('Deleted bulk room reservation %label.', $logger_arguments);

    }

    $form_state->setRedirect('entity.bulk_room_reservation.canonical', ['bulk_room_reservation' => $entity->id()]);
  }

  /**
   * {@inheritDoc}
   */
  public function roomCallback(array $form, FormStateInterface $form_state) {
    return $form['field_room'];
  }

  /**
   * {@inheritDoc}
   */
  public function createBatch(array $dates, $operation, FormStateInterface $form_state) {
    $variables = [
      'create' => [
        'title' => 'Bulk generating room reservations',
        'process' => 'Drupal\intercept_bulk_room_reservation\Form\BulkRoomReservationForm::createProcess',
        'finished' => 'Drupal\intercept_bulk_room_reservation\Form\BulkRoomReservationForm::batchFinished',
      ],
      'delete' => [
        'title' => 'Bulk deleting room reservations.',
        'process' => 'Drupal\intercept_bulk_room_reservation\Form\BulkRoomReservationForm::deleteProcess',
        'finished' => 'Drupal\intercept_bulk_room_reservation\Form\BulkRoomReservationForm::batchFinishedUpdate',
      ],
      // Handling delete logic in BulkRoomReservation::preDelete().
    ];

    switch ($operation) {
      case 'create':
        $batch = [
          'title' => $variables[$operation]['title'],
          'operations' => [
            [
              'Drupal\intercept_bulk_room_reservation\Form\BulkRoomReservationForm::batchStart',
              [count($dates)],
            ],
          ],
          'finished' => $variables[$operation]['finished'],
        ];

        // Add an operation to the batch for each date.
        foreach ($dates as $date) {
          $values = [
            // Not associating bulk room reservations with events.
            'field_event' => NULL,
            'field_room' => $date['room'],
            'field_user' => $this->currentUser->id(),
            'field_group_name' => $form_state->getValue(['field_group_name', 0]),
            'field_dates' => [
              'value' => $date['range']['start']->format('Y-m-d\TH:i:s'),
              'end_value' => $date['range']['end']->format('Y-m-d\TH:i:s'),
            ],
          ];

          $batch['operations'][] = [
            $variables[$operation]['process'],
            [$values, $this->getEntity()->id()],
          ];
        }

        break;

      case 'delete':
        $toRemove = $this->getRoomReservationsToRemove($this->getEntity(), $form_state);
        $batch = [
          'title' => 'Removing room reservations; creating new series',
          'operations' => [
            [
              'Drupal\intercept_bulk_room_reservation\Form\BulkRoomReservationForm::batchStart',
              [count($toRemove)],
            ],
          ],
          'finished' => $variables[$operation]['finished'],
        ];

        foreach ($toRemove as $room_reservation) {
          $batch['operations'][] = [
            'Drupal\intercept_bulk_room_reservation\Form\BulkRoomReservationForm::deleteRoomReservation',
            [$room_reservation],
          ];
        }
        return $batch;

    }

    return $batch;
  }

  /**
   * Determines the number of non-overridden related room_reservations.
   *
   * @param Drupal\intercept_bulk_room_reservation\BulkRoomReservation $entity
   *   The Bulk Room Reservation.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return int
   *   The number of occurrences in the $dates series that are not included in
   *   field_related_room_reservations.
   */
  public function getCreateCount(BulkRoomReservation $entity, FormStateInterface $form_state) {
    $count = 0;
    $dates = $this->bulkReservationsSeriesGenerator->generateSeries($form_state, 'dates');

    foreach ($dates as $date) {
      $found = FALSE;
      foreach ($entity->field_related_room_reservations->referencedEntities() as $key => $room_reservation) {
        if ($room_reservation->field_dates->value == $date['range']['start']->format('Y-m-d\TH:i:s')) {
          $found = TRUE;
          continue;
        }
      }
      if (!$found) {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Determines the number of non-overridden related room_reservations.
   *
   * @param Drupal\intercept_bulk_room_reservation\BulkRoomReservation $entity
   *   The Bulk Room Reservation.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The number of non-overridden related room_reservations.
   */
  public function getRoomReservationsToRemove(BulkRoomReservation $entity, FormStateInterface $form_state) {
    $entities = [];

    foreach ($entity->field_related_room_reservations->referencedEntities() as $key => $room_reservation) {
      if (array_key_exists('update_overridden', $form_state->getValues())
        && !$form_state->getValue('update_overridden')
        && in_array($room_reservation, $entity->field_overridden->referencedEntities())) {
        continue;
      }
      $entities[] = $room_reservation;
    }

    return $entities;
  }

  /**
   * Provides the number of related room_reservations to delete.
   *
   * Delete related room reservations if their start date matches no elements
   * of the $dates array as determined by the start and end points in the form.
   *
   * @param Drupal\intercept_bulk_room_reservation\BulkRoomReservation $entity
   *   The Bulk Room Reservation.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return int
   *   The number of room_reservations to delete.
   */
  public function getDeleteIds(BulkRoomReservation $entity, FormStateInterface $form_state) {
    $deleteIds = [];
    $dates = $this->bulkReservationsSeriesGenerator->generateSeries($form_state, 'dates');
    foreach ($entity->field_related_room_reservations->referencedEntities() as $key => $room_reservation) {
      if (array_key_exists('update_overridden', $form_state->getValues()) && !$form_state->getValue('update_overridden')) {
        // Not updating overridden room reservations.
        if (in_array($room_reservation, $entity->field_overridden->referencedEntities())) {
          // This room_reservation is overridden; don't delete.
          continue;
        }
      }
      // Here we are either updating overridden or $room_reservation is not
      // overridden. Add its id to $deleteIds iff no $date's start matches
      // this $room_reservation's start.
      $found = FALSE;
      foreach ($dates as $date) {
        if ($room_reservation->field_dates->value == $date['range']['start']->format('Y-m-d\TH:i:s')) {
          $found = TRUE;
        }
      }
      if (!$found) {
        $deleteIds[] = $room_reservation->id();
      }

    }

    return $deleteIds;
  }

  /**
   * Provides an array of room_reservation entity ids to update.
   *
   * @param Drupal\intercept_bulk_room_reservation\BulkRoomReservation $entity
   *   The Bulk Room Reservation.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array of room_reservation entity ids.
   */
  public function getEidsToUpdate(BulkRoomReservation $entity, FormStateInterface $form_state) {
    $eids = [];

    foreach ($entity->field_related_room_reservations->referencedEntities() as $key => $room_reservation) {
      if (array_key_exists('update_overridden', $form_state->getValues())
        && !$form_state->getValue('update_overridden')
        && in_array($room_reservation, $entity->field_overridden->referencedEntities())) {
        continue;
      }
      // Otherwise, add this room reservation to our array.
      $eids[] = $room_reservation->id();
    }

    return $eids;
  }

  /**
   * Create event batch processing callback.
   */
  public static function createProcess(array $values, int $entityId) {
    $room_reservation = \Drupal::entityTypeManager()->getStorage('room_reservation')->create($values);
    $room_reservation->save();
    // Attach new room_reservation to the bulk_room_reservation.
    $bulk_room_reservation = \Drupal::entityTypeManager()->getStorage('bulk_room_reservation')->load($entityId);
    $bulk_room_reservation->field_related_room_reservations[] = [
      'target_id' => $room_reservation->id(),
    ];
    $bulk_room_reservation->save();
  }

  /**
   * Batch operation callback to delete a room reservation.
   *
   * We're removing this room reservation from its bulk room reservation
   * in intercept_bulk_room_reservation_room_reservation_predelete().
   *
   * @param Drupal\intercept_room_reservation\Entity\RoomReservation $room_reservation
   *   The room reservation entity to delete.
   */
  public static function deleteRoomReservation(RoomReservation $room_reservation) {
    $room_reservation->delete();
  }

  /**
   * Update event batch processing callback.
   */
  public static function updateProcess(array $values, int $entityId) {
    $bulk_room_reservation = \Drupal::entityTypeManager()->getStorage('bulk_room_reservation')->load($entityId);
    foreach ($bulk_room_reservation->field_related_room_reservations->referencedEntities() as $key => $room_reservation) {
      if (!is_null($bulk_room_reservation->field_overridden->value)
        && !empty($bulk_room_reservation->field_overridden->target_id)
        && in_array($room_reservation, $bulk_room_reservation->field_overridden->referencedEntities())
        ) {
        continue;
      }

      // Update this $room_reservation.
      foreach (['field_room', 'field_user', 'field_dates', 'field_group_name'] as $field) {
        $room_reservation->set($field, $values[$field]);
      }
      $room_reservation->save();
    }
  }

  /**
   * Delete event batch processing callback.
   */
  public function deleteProcess(array $values, int $entityId) {
    $bulk_room_reservation = \Drupal::entityTypeManager()->getStorage('bulk_room_reservation')->load($entityId);
    foreach ($bulk_room_reservation->field_related_room_reservations->referencedEntities() as $key => $room_reservation) {
      if (array_key_exists('update_overridden', $form_state->getValues())
        && !$form_state->getValue('update_overridden')
        && !empty($bulk_room_reservation->field_overridden->target_id)
        && in_array($room_reservation, $bulk_room_reservation->field_overridden->referencedEntities())) {
        continue;
      }
      // Delete this $room_reservation.
      $entity = $this->entityTypeManager->getStorage('room_reservation')->load($room_reservation->target_id);
      $entity->delete();
    }

  }

  /**
   * Batch callback; initialize the number of room reservations.
   */
  public static function batchStart($total, &$context) {
    $context['results']['room_reservations'] = $total;
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      if ($results['room_reservations']) {
        \Drupal::service('messenger')->addMessage(\Drupal::translation()
          ->formatPlural($results['room_reservations'], 'Generated 1 reservation.', 'Generated @count reservations.'));
      }
      else {
        \Drupal::service('messenger')
          ->addMessage(new TranslatableMarkup('No new reservations to generate.'));
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
   * Batch finished callback.
   */
  public static function batchFinishedUpdate($success, $results, $operations) {
    if ($success) {
      if ($results['room_reservations']) {
        \Drupal::service('messenger')->addMessage(\Drupal::translation()
          ->formatPlural($results['room_reservations'], 'Deleted 1 reservation.', 'Deleted @count reservations.'));
      }
      else {
        \Drupal::service('messenger')
          ->addMessage(new TranslatableMarkup('No new reservations to generate.'));
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
   *
   */
  protected static function incrementRound(&$date, $increment) {

    // Round minutes and seconds, if necessary.
    if ($date instanceof DrupalDateTime && $increment > 1) {
      $day = intval($date
        ->format('j'));
      $hour = intval($date
        ->format('H'));
      $second = intval(round(intval($date
        ->format('s')) / $increment) * $increment);
      $minute = intval($date
        ->format('i'));
      if ($second == 60) {
        $minute += 1;
        $second = 0;
      }
      $minute = intval(round($minute / $increment) * $increment);
      if ($minute == 60) {
        $hour += 1;
        $minute = 0;
      }
      $date
        ->setTime($hour, $minute, $second);
      if ($hour == 24) {
        $day += 1;
        $year = $date
          ->format('Y');
        $month = $date
          ->format('n');
        $date
          ->setDate($year, $month, $day);
      }
    }
    return $date;
  }

}
