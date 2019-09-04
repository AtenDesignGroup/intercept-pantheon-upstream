<?php

namespace Drupal\intercept_event\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\date_recur\DateRecurRRule;
use Drupal\date_recur\Plugin\DateRecurOccurrenceHandler\DefaultDateRecurOccurrenceHandler;
use Drupal\date_recur\Plugin\DateRecurOccurrenceHandlerInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\intercept_core\DateRangeFormatterTrait;
use Drupal\intercept_core\Utility\Dates;
use Drupal\intercept_event\Entity\EventRecurrenceInterface;
use Drupal\intercept_event\RecurringEventManager;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Event Recurrence edit forms.
 *
 * @ingroup intercept_event
 */
class EventAttendanceEventsForm extends ContentEntityForm {

  use DateRangeFormatterTrait;

  /**
   * @var EventRecurrenceInterface
   */
  private $eventRecurrence;

  /**
   * @var RecurringEventManager
   */
  protected $recurringEventManager;

  /**
   * @var Dates
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

    $form = parent::buildForm($form, $form_state);

    //$form['#theme'] = 'event_recurrence_event_form';

  $form['revision']['#access'] = FALSE;
  $form['revision_information']['#access'] = FALSE;
  $form['revision_log']['#access'] = FALSE;
  $form['advanced']['#access'] = FALSE;
  $form['#process'][] = '::processNodeForm';

    return $form;
  }

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

  private function compensate($date, $timezone = 'default') {
    $converted = $this->dateUtility->convertTimezone($date, 'storage')
      ->format($this->dateUtility->getStorageFormat());
     $new_date = $this->dateUtility->getDrupalDate($converted, 'default');
    return $timezone == 'default' ? $new_date : $this->dateUtility->convertTimezone($new_date, 'storage');
  }

  /**
   * @param DateRecurItem $item
   *
   * @return array
   * @throws \Exception
   */
  private function getDates($item, $timezone = 'default') {
    /** @var DateRecurOccurrenceHandlerInterface $handler */
    $handler = $item->getOccurrenceHandler();
    $storage_format = $item->getDateStorageFormat();
    if (!$handler->isRecurring()) {
      if (empty($item->end_date)) {
        $item->end_date = $item->start_date;
      }
      return [[
        'value' => DateRecurRRule::massageDateValueForStorage($item->start_date, $storage_format),
        'end_value' => DateRecurRRule::massageDateValueForStorage($item->end_date, $storage_format),
      ]];
    }
    else {
      $occurrences = $item->occurrences;
      // We have to compensate for the DateTimeComputed class assuming UTC.
      // TODO: Create issue for this in date_recur or in Drupal core.
      foreach ($occurrences as &$value) {
        $value['value'] = $this->compensate($value['value'], $timezone);
        $value['end_value'] = $this->compensate($value['end_value'], $timezone);
      }
      return $occurrences;
    }
  }

  protected function getEvent() {
    if (!empty($this->entity->event->entity)) {
      return $this->entity->event->entity;
    }
    $values = [
      'type' => 'event',
      'event_recurrence' => $this->entity->id(),
    ];

    return $this->entityTypeManager->getStorage('node')->create($values);
  }

  /**
   * Submit handler to delete all events.
   */
  public function deleteEvents(array &$form, FormStateInterface $form_state) {
    $nodes = $this->eventRecurrence->deleteEvents();
    \Drupal::service('messenger')->addStatus($this->t('@count recurring events deleted.', ['@count' => count($nodes)]));
  }

  /**
   * Submit handler to update existing events.
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
        // TODO: This should be grabbed from form_state and processed through EntityFormDisplay.
        if (in_array($field_name, ['event_recurrence', 'nid', 'vid', 'type', 'uuid', 'field_date_time'])) {
          continue;
        }
        $node->set($field_name, $field->getValue());
      }
      $node->save();
      $count++;
    }
    drupal_set_message($this->t('@count events updated.', ['@count' => $count]));
  }

  /**
   * Submit handler to generate events.
   */
  public function generateEvents(array &$form, FormStateInterface $form_state) {
      /** @var NodeInterface $base_event */
    $base_event = $form_state->getFormObject()->getEntity();

    $recurring_rule_field = $this->eventRecurrence->getRecurField();
    $storage_format = $recurring_rule_field->getDateStorageFormat();
    $dates = $this->getDates($recurring_rule_field, 'storage');
    foreach ($dates as $date) {
      $event = $base_event->createDuplicate();
      $event->set('field_date_time', [
        'value' => $date['value']->format($storage_format),
        'end_value' => $date['end_value']->format($storage_format),
      ]);
      $event->set('event_recurrence', $this->eventRecurrence->id());
      $event->save();
    }
    drupal_set_message($this->t('@count events created.', ['@count' => count($dates)]));
  }

  /**
   * Submit handler to delete all events and regenerate.
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
    $actions['delete']['#access'] = FALSE;

    return $actions;
  }

  protected function submitHandlers($extra = []) {
    $ief = [[\Drupal\inline_entity_form\ElementSubmit::class, 'trigger']];
    return array_merge($ief, $extra);
  }
}
