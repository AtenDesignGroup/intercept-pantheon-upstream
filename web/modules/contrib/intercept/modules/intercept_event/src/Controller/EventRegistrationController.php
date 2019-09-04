<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class EventRegistrationController.
 */
class EventRegistrationController extends ControllerBase {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * EventsController constructor.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   * @param EntityFormBuilderInterface $entity_form_builder
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('form_builder')
    );
  }

  /**
   * Event registration form.
   */
  public function register(NodeInterface $node) {
    if ($this->currentUser()->isAnonymous()) {
      return $this->redirect('user.login', [
        'destination' => Url::fromRoute('<current>')->toString(),
      ]);
    }
    $access_handler = $this->entityTypeManager()->getAccessControlHandler('event_registration');
    if (!$access_handler->createAccess('event_registration')) {
      throw new AccessDeniedHttpException();
    }

    $build = [];

    // Add Event Header
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build['header'] = $view_builder->view($node, 'header');

    // Add Registration Form
    $build['#attached']['library'][] = 'intercept_event/eventRegister';
    $build['#markup'] = '';
    $build['intercept_event_register']['#markup'] = '<div id="eventRegisterRoot" data-uuid="' . $node->uuid() . '"></div>';

    return $build;
  }

  /**
   * Not used right now, here for reference.
   */
  public function manageJs(UserInterface $user) {
    $build = [];

    $build['#attached']['library'][] = 'intercept_event/manageEventRegistrations';
    $build['#markup'] = '';
    $build['intercept_event_registration']['#markup'] = '';
    $build['#attached']['drupalSettings']['intercept']['parameters']['user']['uuid'] = $user->uuid();

    return $build;
  }

  /**
   * Menu callback for user/{user}/events.
   */
  public function manage(UserInterface $user) {
    $build = [];

    $build['events'] = [
      '#type' => 'view',
      '#name' => 'intercept_user_events',
      '#display_id' => 'embed',
      '#attached' => [
        'library' => ['intercept_event/eventCustomerEvaluation'],
      ],
    ];

    return $build;
  }

  public function overview() {

  }
}
