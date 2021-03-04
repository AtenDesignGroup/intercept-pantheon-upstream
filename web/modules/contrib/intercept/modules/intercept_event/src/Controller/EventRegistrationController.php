<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\intercept_core\HttpRequestTrait;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EventRegistrationController.
 */
class EventRegistrationController extends ControllerBase {

  use HttpRequestTrait;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * EventRegistrationController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(FormBuilderInterface $form_builder, AccountInterface $current_user) {
    $this->formBuilder = $form_builder;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('current_user')
    );
  }

  /**
   * Event registration form.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event Node entity.
   */
  public function register(NodeInterface $node) {
    // Add Event Header.
    $view_builder = $this->entityTypeManager()->getViewBuilder('node');
    if ($this->currentUser->id() == 0) {
      return [
        '#theme' => 'event_registration_guest_form',
        '#event' => $node,
        '#header' => $view_builder->view($node, 'header'),
      ];
    }
    return [
      '#theme' => 'event_registration_user_form',
      '#event' => $node,
      '#header' => $view_builder->view($node, 'header'),
      '#form' => [
        '#attached' => [
          'library' => [
            'intercept_event/eventRegister',
          ],
        ],
        '#markup' => '<div id="eventRegisterRoot" data-uuid="' . $node->uuid() . '"></div>',
      ],
    ];
  }

  /**
   * Event registration guest form.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event Node entity.
   */
  public function guestRegister(NodeInterface $node) {
    // Add Event Header.
    $view_builder = $this->entityTypeManager()->getViewBuilder('node');
    $build['header'] = $view_builder->view($node, 'header');

    // Add Registration page.
    $build['#attached']['library'][] = 'intercept_event/eventRegister';
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
    return $this->redirect('view.intercept_user_events.page');
  }

  /**
   * Gets a user's event registration IDs by event NID.
   *
   * The Request object parameters must contain both a 'uid' and 'eventId'
   * value.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JsonResponse object with event registration IDs.
   */
  public function userRegistrationsByEventId(Request $request) {
    $params = $this->getParams($request);
    if (!empty($params['uid'] && !empty($params['eventId']))) {
      $registrations = $this->entityTypeManager()
        ->getStorage('event_registration')
        ->getQuery()
        ->condition('field_event', $params['eventId'])
        ->condition('field_user', $params['uid'])
        ->execute();
      return JsonResponse::create($registrations, 200);
    }
    return JsonResponse::create();
  }

  /**
   * Gets a guest's event registration IDs by event NID.
   *
   * The Request object parameters must contain both an 'email' and 'eventId'
   * value.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JsonResponse object with event registration IDs.
   */
  public function guestRegistrationsByEventId(Request $request) {
    $params = $this->getParams($request);
    if (!empty($params['email'] && !empty($params['eventId']))) {
      $registrations = $this->entityTypeManager()
        ->getStorage('event_registration')
        ->getQuery()
        ->condition('field_event', $params['eventId'])
        ->condition('field_guest_email', $params['email'])
        ->execute();
      return JsonResponse::create($registrations, 200);
    }
    return JsonResponse::create();
  }

}
