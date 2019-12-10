<?php

namespace Drupal\intercept_event\Controller;

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
   * EventsController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
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
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event Node entity.
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

    // Add Event Header.
    $view_builder = $this->entityTypeManager()->getViewBuilder('node');
    $build['header'] = $view_builder->view($node, 'header');

    // Add Registration Form.
    $build['#attached']['library'][] = 'intercept_event/eventRegister';
    $build['#markup'] = '';
    $build['intercept_event_register']['#markup'] = '<div id="eventRegisterRoot" data-uuid="' . $node->uuid() . '"></div>';

    return $build;
  }

  /**
   * Not used right now, here for reference.
   *
   * @param \Drupal\user\UserInterface $user
   *   The User entity.
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
   *
   * @param \Drupal\user\UserInterface $user
   *   The User entity.
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

}
