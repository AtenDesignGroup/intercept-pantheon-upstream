<?php

namespace Drupal\intercept_event;

use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\votingapi\VoteStorageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class EventEvaluationManager {

  use DependencySerializationTrait;

  use StringTranslationTrait;

  const VOTE_TYPE_ID = 'evaluation';

  const VOTE_TYPE_STAFF_ID = 'evaluation_staff';

  const FIELD_NAME_POSITIVE = 'field_evaluation_criteria_pos';

  const FIELD_NAME_NEGATIVE = 'field_evaluation_criteria_neg';


  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Return info with uuids as keys.
   *
   * @var bool
   */
  protected $useUuid;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

    /**
     * @var VoteStorageInterface
     */
  protected $voteStorage;

  /**
   * Constructs a new EventEvaluationManager object.
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->voteStorage = $this->entityTypeManager->getStorage('vote');
  }

  /**
   * Create a new EventEvaluation object.
   *
   * @return EventEvaluation
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

  private function getUserFromParams($params) {
    if (empty($params['user_id'])) {
      return $this->currentUser->id();
    }
    if ($params['user_id'] == '<current>') {
      return $this->currentUser->id();
    }
    return $params['user_id'];
  }

  public function createFromEntity(EntityInterface $entity, $values = []) {
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
   * @param EntityInterface $entity
   * @param null $type
   *
   * @return bool|EventEvaluation
   */
  public function loadByEntity(EntityInterface $entity, array $values = []) {
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
   *
   * @return bool|EventEvaluation
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

  public function eventHasEnded(EntityInterface $entity) {
    if (!$end_date = $entity->field_date_time->end_value) {
      return FALSE;
    }
    $end_date = new \Drupal\Core\Datetime\DrupalDateTime($end_date, 'UTC');
    $now = new \Drupal\Core\Datetime\DrupalDateTime('now', 'UTC');
    return $now->diff($end_date)->invert;
  }

  /**
   * Helper function for the EventEvaluation instantiation.
   *
   * @return EventEvaluation
   */
  protected function createEventEvaluationInstance(\Drupal\votingapi\VoteInterface $vote) {
    $evaluation = new EventEvaluation($vote);
    return $evaluation->setManager($this);
  }

  /**
   * Configure manager to use uuids or entity ids.
   *
   * @param bool $use
   *   Boolean to set the value with.
   * @return $this
   */
  public function uuid($use = TRUE) {
    $this->useUuid = $use;
    return $this;
  }

  /**
   * Load analysis info by event node.
   *
   * @param EntityInterface $entity
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadAnalysis(EntityInterface $entity) {
    $vote_storage = $this->entityTypeManager->getStorage('vote');
    $votes = $vote_storage->loadByProperties([
      'type' => self::VOTE_TYPE_ID,
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ]);
    $data = [];
    foreach ([$this->t('Dislike'), $this->t('Like')] as $index => $label) {
      $count = count(array_filter($votes, function($vote) use ($index) {
        return $index == $vote->getValue();
      }));
      $data[$index] = [
        'label' => $label->__toString(),
        'count' => $count,
      ];
    }
    $criteria_terms = $this->getPositiveCriteria($entity) + $this->getNegativeCriteria($entity);
    $data = array_reduce($votes, function($carry, $vote) use ($criteria_terms) {
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
   * @param EntityInterface $entity
   *
   * @return array
   */
  public function buildJsWidget(EntityInterface $entity) {
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
   * @param EntityInterface $entity
   *
   * @return array
   *   Form render array.
   */
  public function getAttendeeForm(EntityInterface $entity) {
    $class = \Drupal\intercept_event\Form\EventEvaluationAttendeeForm::class;
    $form_arg = \Drupal::service('class_resolver')->getInstanceFromDefinition($class)
      ->setEntity($entity);
    $form_state = new \Drupal\Core\Form\FormState();
    return \Drupal::service('form_builder')
      ->buildForm($form_arg, $form_state);
  }

  /**
   * Staff evaluation form.
   *
   * @param EntityInterface $entity
   *
   * @return array
   *   Form render array.
   */
  public function getStaffForm(EntityInterface $entity) {
    $class = \Drupal\intercept_event\Form\EventEvaluationStaffForm::class;
    $form_arg = \Drupal::service('class_resolver')->getInstanceFromDefinition($class)
      ->setEntity($entity);
    $form_state = new \Drupal\Core\Form\FormState();
    return \Drupal::service('form_builder')
      ->buildForm($form_arg, $form_state);
  }

  /**
   * @param NodeInterface $event
   *
   * @return bool|string
   */
  public function getPrimaryEventType(NodeInterface $event) {
    if (!$event_type = $event->get('field_event_type_primary')->entity) {
      return FALSE;
    }
    return $event_type;
  }

  /**
   * @param NodeInterface $event
   *
   * @return array
   */
  public function getNegativeCriteria(NodeInterface $event) {
    $criteria = $this->getCriteria($event);
    if (!empty($criteria[self::FIELD_NAME_NEGATIVE])) {
      return $criteria[self::FIELD_NAME_NEGATIVE];
    }
    return [];
  }

  /**
   * @param NodeInterface $event
   *
   * @return array
   */
  public function getNegativeCriteriaOptions(NodeInterface $event) {
    $criteria = $this->getNegativeCriteria($event);
    return array_map(function($term) {
      return $term->label();
    }, $criteria);
  }

  /**
   * @param NodeInterface $event
   *
   * @return array
   */
  public function getPositiveCriteria(NodeInterface $event) {
    $criteria = $this->getCriteria($event);
    if (!empty($criteria[self::FIELD_NAME_POSITIVE])) {
      return $criteria[self::FIELD_NAME_POSITIVE];
    }
    return [];
  }

  /**
   * @param NodeInterface $event
   *
   * @return array
   */
  public function getPositiveCriteriaOptions(NodeInterface $event) {
    $criteria = $this->getPositiveCriteria($event);
    return array_map(function($term) {
      return $term->label();
    }, $criteria);
  }

  /**
   * @param NodeInterface $event
   *
   * @return array
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
