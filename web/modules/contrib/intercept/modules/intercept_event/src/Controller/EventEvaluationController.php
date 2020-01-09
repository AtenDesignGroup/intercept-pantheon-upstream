<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\intercept_event\EventEvaluationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EventEvaluationController.
 */
class EventEvaluationController extends ControllerBase {

  use \Drupal\intercept_core\EntityUuidConverterTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The Intercept event evaluation manager.
   *
   * @var \Drupal\intercept_event\EventEvaluationManager
   */
  protected $eventEvaluationManager;

  /**
   * EventsController constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, EventEvaluationManager $event_evaluation_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->eventEvaluationManager = $event_evaluation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('intercept_event.evaluation_manager')
    );
  }

  /**
   * Analysis api callback to get event evaluation data.
   */
  public function analysis(Request $request) {
    $events = $request->query->get('events');
    if (empty($events)) {
      $content = Json::decode($request->getContent());
      $events = !empty($content['events']) ? $content['events'] : FALSE;
    }

    if (!$events) {
      return JsonResponse::create([], 200);
    }
    $result = [];

    $events = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => 'event',
      'uuid' => $events,
    ]);

    foreach ($events as $event) {
      $result[$event->uuid()] = [
        'id' => $event->id(),
        'title' => $event->label(),
        'url' => $event->toUrl(),
      ];
      if ($analysis = $this->eventEvaluationManager->uuid()->loadAnalysis($event)) {
        $result[$event->uuid()] += $analysis;
      }
    }
    return JsonResponse::create($result, 200);
  }

  /**
   * Evaluation api callback to evaluate an event node.
   */
  public function evaluate(Request $request) {
    $method = $request->getMethod();
    $post = Json::decode($request->getContent());

    if (!is_array($post) || !($evaluation = $this->getEvaluationFromPost($post))) {
      return JsonResponse::create(['error' => 'Invalid data'], 200);
    }

    if (!$evaluation->access()->isAllowed()) {
      return JsonResponse::create(['error' => 'Access denied'], 200);
    }

    if ($method == 'DELETE') {
      $evaluation->delete();
    }
    else {
      $criteria = !empty($post['evaluation_criteria'])
        ? ['taxonomy_term' => $this->convertUuids($post['evaluation_criteria'], 'taxonomy_term')]
        : [];
      $evaluation->evaluate($post['evaluation'], $criteria);
    }
    $result = [
      'message' => 'saved',
    ];
    return JsonResponse::create($result, 200);
  }

  /**
   * Helper function to parse json request content body.
   */
  private function getEvaluationFromPost(array $post) {
    $entity_id = $this->convertUuid($post['event'], 'node');
    $user_id = !empty($post['user'])
      ? $this->convertUuid($post['user'], 'user')
      : '<current>';
    // If they sent a user uuid but now the variable is blank,
    // it's an invalid ID.
    if (!empty($post['user']) && empty($user_id)) {
      return FALSE;
    }
    $evaluation = $this->eventEvaluationManager->loadByProperties([
      'entity_id' => $entity_id,
      'entity_type' => 'node',
      'user_id' => $user_id,
      'type' => EventEvaluationManager::VOTE_TYPE_ID,
    ]);
    return $evaluation ? $evaluation : $this->eventEvaluationManager->create([
      'entity_id' => $entity_id,
      'entity_type' => 'node',
      'user_id' => $user_id,
      'type' => EventEvaluationManager::VOTE_TYPE_ID,
    ]);
  }

}
