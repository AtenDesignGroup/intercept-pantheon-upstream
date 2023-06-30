<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\intercept_core\HttpRequestTrait;
use Drupal\intercept_event\Form\EventNotificationsForm;
use Drupal\node\NodeInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * EventRegistrationController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(FormBuilderInterface $form_builder, AccountInterface $current_user, UserDataInterface $user_data, RendererInterface $renderer) {
    $this->formBuilder = $form_builder;
    $this->currentUser = $current_user;
    $this->userData = $user_data;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('current_user'),
      $container->get('user.data'),
      $container->get('renderer')
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
    $markup = '<div id="eventRegisterRoot" data-uuid="' . $node->uuid() . '"></div>';

    // Add the "Notification Settings" form here. Repeated from Settings.
    if (\Drupal::moduleHandler()->moduleExists('intercept_messages')) {
      // Rebuild the settings form in this context even though this isn't a
      // normal Drupal form.
      $form_id = new EventNotificationsForm;
      $form_state = new FormState();
      $form = $this->formBuilder->buildForm($form_id, $form_state);
      $markup .= '<div class="l--section materialize"><div class="form-wrapper">';
      $markup .= '<h2 class="section-title section-title--secondary">Notification Settings</h2>';
      $markup .= $this->renderer->render($form);
      $markup .= '</div></div>';
    }

    // Your Current Contact Information
    $authmap = \Drupal::service('externalauth.authmap');
    $plugin_id = \Drupal::config('intercept_ils.settings')->get('intercept_ils_plugin', '');
    if (!empty($plugin_id) && $authdata = $authmap->getAuthdata($this->currentUser->id(), $plugin_id)) {
      $authdata_data = unserialize($authdata['data']);
      if (isset($authdata_data)) {
        $contact = '<div class="l--section">
            <h2 class="section-title section-title--secondary">Your Current Contact Information</h2>
            <small>
              Telephone: ' . $authdata_data->PhoneNumber . '<br />
              Email: ' . $authdata_data->EmailAddress . '<br />
              <em>Need to update your info? After finishing your registration visit My Account &gt; Settings.</em>
            </small>
          </div>';
        $markup .= '<div class="l--subsection">' . $contact . '</div>';
      }
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
        '#markup' => '<div class="l--offset">' . $markup . '</div>',
        '#allowed_tags' => ['form', 'label', 'div', 'span', 'input', 'h1', 'h2', 'h3', 'h4', 'p', 'a', 'textarea', 'b', 'br']
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
    return $this->redirect('intercept_event.account.events');
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

  /**
   * Allows customers to download an .ics calendar file for an event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event Node entity.
   */
  public function downloadIcs(NodeInterface $node) {

    $event = $node;
    $title = $event->getTitle();
    $location = $event->get('field_location')->referencedEntities()[0]->getTitle();
    $description = $event->get('field_text_teaser')->value;
    $start_date = $event->get('field_date_time')->start_date;
    $end_date = $event->get('field_date_time')->end_date;
    $start_date = \Drupal::service('intercept_core.utility.dates')->getDrupalDate($start_date);
    $end_date = \Drupal::service('intercept_core.utility.dates')->getDrupalDate($end_date);
    $start_date_machine = \Drupal::service('intercept_core.utility.dates')->convertTimezone($start_date, 'UTC')->format('Ymd\THis\Z');
    $end_date_machine = \Drupal::service('intercept_core.utility.dates')->convertTimezone($end_date, 'UTC')->format('Ymd\THis\Z');
    $url = $GLOBALS['base_url'] . $event->path->alias;

    $response = new Response();
    $response->headers->set('Content-Type', 'text/calendar');
    $output = [
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'BEGIN:VEVENT',
      'URL:' . $url,
      'DTSTART:' . $start_date_machine,
      'DTEND:' . $end_date_machine,
      'SUMMARY:' . $title,
      'DESCRIPTION:' . $description,
      'LOCATION:' . $location,
      'END:VEVENT',
      'END:VCALENDAR',
    ];
    $output = implode("\n", $output);
    $response->setContent($output);
    return $response;
  }

}
