<?php

namespace Drupal\intercept_event;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\intercept_event\Form\EventEvaluationAttendeeForm;
use Drupal\intercept_event\Form\EventEvaluationStaffForm;
use Drupal\node\NodeInterface;
use Drupal\votingapi\VoteInterface;

/**
 * The Event Evaluation manager.
 */
class EventEvaluationManager {

  use DependencySerializationTrait;

  use StringTranslationTrait;

  const VOTE_TYPE_ID = 'evaluation';

  const VOTE_TYPE_STAFF_ID = 'evaluation_staff';

  const FIELD_NAME_POSITIVE = 'field_evaluation_criteria_pos';

  const FIELD_NAME_NEGATIVE = 'field_evaluation_criteria_neg';

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Return info with uuids as keys.
   *
   * @var bool
   */
  protected $useUuid;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The votingapi vote storage manager.
   *
   * @var \Drupal\votingapi\VoteStorageInterface
   */
  protected $voteStorage;

  /**
   * Constructs a new EventEvaluationManager object.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The currently active request object.
   */
  public function __construct(ClassResolverInterface $class_resolver, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder) {
    $this->classResolver = $class_resolver;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->voteStorage = $this->entityTypeManager->getStorage('vote');
  }

  /**
   * Create a new EventEvaluation object.
   *
   * @return \Drupal\intercept_event\EventEvaluation
   *   An Event Evaluation entity.
   */
  public function create(array $values = []) {
    $vote_storage = $this->entityTypeManager->getStorage('vote');
    /** @var \Drupal\votingapi\VoteInterface $vote */
    $vote_type = $this->entityTypeManager->getStorage('vote_type')->load($values['type']);
    $vote = $vote_storage->create(['type' => $values['type']]);
    $vote->setOwnerId($this->getUserFromParams($values));
    $vote->setVotedEntityId($values['entity_id']);
    $vote->setVotedEntityType($values['entity_type']);
    $vote->setValueType($vote_type->getValueType());
    return $this->createEventEvaluationInstance($vote);
  }

  /**
   * Gets the current user ID.
   *
   * @param array $params
   *   An votingapi Vote entity array.
   *
   * @return int
   *   The user ID.
   */
  private function getUserFromParams(array $params) {
    if (empty($params['user_id'])) {
      return $this->currentUser->id();
    }
    if ($params['user_id'] == '<current>') {
      return $this->currentUser->id();
    }
    return $params['user_id'];
  }

  /**
   * Creates an Event Evaluation given an Event Node.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The Event node.
   * @param array $values
   *   (optional) Additional entity values.
   *
   * @return bool|\Drupal\intercept_event\EventEvaluation
   *   The Event Evaluation entity.
   */
  public function createFromEntity(NodeInterface $entity, array $values = []) {
    $params = [
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'type' => !empty($values['type']) ? $values['type'] : self::VOTE_TYPE_ID,
      'user_id' => $this->getUserFromParams($values),
    ];

    return $this->create($params);
  }

  /**
   * Load an EventEvaluation by votingapi entity properties.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The Event node.
   * @param array $values
   *   (optional) Additional entity values.
   *
   * @return bool|\Drupal\intercept_event\EventEvaluation
   *   The Event Evaluation entity.
   */
  public function loadByEntity(NodeInterface $entity, array $values = []) {
    $values += [
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ];
    return $this->loadByProperties($values);
  }

  /**
   * Load an EventEvaluation by votingapi entity properties.
   *
   * @param array $properties
   *   The votingapi entity properties.
   *
   * @return bool|\Drupal\intercept_event\EventEvaluation
   *   The Event Evaluation entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadByProperties(array $properties = []) {
    if (empty($properties['entity_type']) || empty($properties['entity_id'])) {
      return FALSE;
    }
    if (!$this->entityTypeManager->getStorage($properties['entity_type'])->load($properties['entity_id'])) {
      // Invalid entity.
      return FALSE;
    }
    $params = [
      'type' => !empty($properties['type']) ? $properties['type'] : self::VOTE_TYPE_ID,
      'entity_type' => $properties['entity_type'],
      'entity_id' => $properties['entity_id'],
    ];
    if (!empty($properties['user_id'])) {
      $params['user_id'] = $this->getUserFromParams($properties);
    }

    /** @var \Drupal\votingapi\VoteStorageInterface $vote_storage */
    $vote_storage = $this->entityTypeManager->getStorage('vote');
    $votes = $vote_storage->loadByProperties($params);
    if (empty($votes)) {
      return FALSE;
    }
    $vote = reset($votes);
    return $this->createEventEvaluationInstance($vote);
  }

  /**
   * Whether the event has ended.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Event Node.
   *
   * @return bool
   *   Whether the event has ended.
   */
  public function eventHasEnded(EntityInterface $entity) {
    if (!$end_date = $entity->field_date_time->end_value) {
      return FALSE;
    }
    $end_date = new DrupalDateTime($end_date, 'UTC');
    $now = new DrupalDateTime('now', 'UTC');
    return $now->diff($end_date)->invert;
  }

  /**
   * Helper function for the EventEvaluation instantiation.
   *
   * @return \Drupal\intercept_event\EventEvaluation
   *   The Event Evaluation entity.
   */
  protected function createEventEvaluationInstance(VoteInterface $vote) {
    $evaluation = new EventEvaluation($vote);
    return $evaluation->setManager($this);
  }

  /**
   * Configure manager to use uuids or entity ids.
   *
   * @param bool $use
   *   Boolean to set the value with.
   *
   * @return $this
   */
  public function uuid($use = TRUE) {
    $this->useUuid = $use;
    return $this;
  }

  /**
   * Load analysis info by event node.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The Event Node.
   *
   * @return array
   *   The analysis info array for an event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadAnalysis(NodeInterface $entity) {
    $vote_storage = $this->entityTypeManager->getStorage('vote');
    $votes = $vote_storage->loadByProperties([
      'type' => self::VOTE_TYPE_ID,
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ]);
    $data = [];
    foreach ([$this->t('Dislike'), $this->t('Like')] as $index => $label) {
      $count = count(array_filter($votes, function ($vote) use ($index) {
        return $index == $vote->getValue();
      }));
      $data[$index] = [
        'label' => $label->__toString(),
        'count' => $count,
      ];
    }
    $criteria_terms = $this->getPositiveCriteria($entity) + $this->getNegativeCriteria($entity);
    $data = array_reduce($votes, function ($carry, $vote) use ($criteria_terms) {
      $e = $this->createEventEvaluationInstance($vote);
      $values = &$carry[$e->getVote()]['criteria'];
      foreach ($e->getVoteCriteria() as $tid) {
        $term = $criteria_terms[$tid];
        $key = $this->useUuid ? $term->uuid() : $tid;
        if (!isset($values[$key])) {
          $values[$key] = [
            'count' => 1,
            'label' => $term->label(),
            'id' => $tid,
          ];
        }
        else {
          $values[$key]['count']++;
        }
      }
      return $carry;
    }, $data);

    return $data;
  }

  /**
   * Build the React.js widget for voting on an event view mode.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The Event Node.
   *
   * @return array
   *   The renderable build array.
   */
  public function buildJsWidget(NodeInterface $entity) {
    if (!$evaluation = $this->loadByEntity($entity, [
      'type' => self::VOTE_TYPE_ID,
      'user_id' => '<current>',
    ])) {
      $evaluation = $this->createFromEntity($entity);
    }

    $build['wrapper'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['js-event-evaluation--attendee'],
        'data-event-uuid' => [$entity->uuid()],
        'data-event-type-primary-uuid' => [
          $evaluation->getPrimaryEventType() ? $evaluation->getPrimaryEventType()->uuid() : '',
        ],
      ],
      '#evaluation' => $evaluation,
      '#attached' => [
        'library' => ['intercept_event/eventCustomerEvaluation'],
      ],
    ];

    // Attach library here.
    return $build;
  }

  /**
   * Attendee evaluation form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Event Evaluation.
   *
   * @return array
   *   Form render array.
   */
  public function getAttendeeForm(EntityInterface $entity) {
    $class = EventEvaluationAttendeeForm::class;
    $form_arg = $this->classResolver->getInstanceFromDefinition($class)
      ->setEntity($entity);
    $form_state = new FormState();
    return $this->formBuilder
      ->buildForm($form_arg, $form_state);
  }

  /**
   * Staff evaluation form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Event Evaluation.
   *
   * @return array
   *   Form render array.
   */
  public function getStaffForm(EntityInterface $entity) {
    $class = EventEvaluationStaffForm::class;
    $form_arg = $this->classResolver->getInstanceFromDefinition($class)
      ->setEntity($entity);
    $form_state = new FormState();
    return $this->formBuilder
      ->buildForm($form_arg, $form_state);
  }

  /**
   * Gets the primary event type entity.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event Node.
   *
   * @return bool|string
   *   The event type string.
   */
  public function getPrimaryEventType(NodeInterface $event) {
    if (!$event_type = $event->get('field_event_type_primary')->entity) {
      return FALSE;
    }
    return $event_type;
  }

  /**
   * Gets an array of negative criteria taxonomy Terms.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event Node.
   *
   * @return array
   *   The array of negative criteria taxonomy Terms.
   */
  public function getNegativeCriteria(NodeInterface $event) {
    $criteria = $this->getCriteria($event);
    if (!empty($criteria[self::FIELD_NAME_NEGATIVE])) {
      return $criteria[self::FIELD_NAME_NEGATIVE];
    }
    return [];
  }

  /**
   * Gets an array of negative criteria taxonomy Term names.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event Node.
   *
   * @return array
   *   The array of negative criteria taxonomy Term names.
   */
  public function getNegativeCriteriaOptions(NodeInterface $event) {
    $criteria = $this->getNegativeCriteria($event);
    return array_map(function ($term) {
      return $term->label();
    }, $criteria);
  }

  /**
   * Gets an array of positive criteria taxonomy Terms.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event Node.
   *
   * @return array
   *   The array of positive criteria taxonomy Terms.
   */
  public function getPositiveCriteria(NodeInterface $event) {
    $criteria = $this->getCriteria($event);
    if (!empty($criteria[self::FIELD_NAME_POSITIVE])) {
      return $criteria[self::FIELD_NAME_POSITIVE];
    }
    return [];
  }

  /**
   * Gets an array of positive criteria taxonomy Term names.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event Node.
   *
   * @return array
   *   The array of positive criteria taxonomy Term names.
   */
  public function getPositiveCriteriaOptions(NodeInterface $event) {
    $criteria = $this->getPositiveCriteria($event);
    return array_map(function ($term) {
      return $term->label();
    }, $criteria);
  }

  /**
   * Gets an array of criteria taxonomy Terms.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The Event Node.
   *
   * @return array
   *   The array of criteria taxonomy Terms.
   */
  public function getCriteria(NodeInterface $event) {
    $criteria = [];
    if (!$event_type = $this->getPrimaryEventType($event)) {
      return $criteria;
    }
    $fields = [self::FIELD_NAME_POSITIVE, self::FIELD_NAME_NEGATIVE];
    foreach ($fields as $field_name) {
      if ($event_type->get($field_name)->isEmpty()) {
        continue;
      }
      $criteria[$field_name] = [];
      foreach ($event_type->get($field_name)->getIterator() as $item) {
        $criteria[$field_name][$item->entity->id()] = $item->entity;
      }
    }
    return $criteria;
  }

}
