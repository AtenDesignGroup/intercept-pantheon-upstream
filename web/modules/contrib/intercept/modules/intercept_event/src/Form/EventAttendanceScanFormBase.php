<?php

namespace Drupal\intercept_event\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\externalauth\ExternalAuth;
use Drupal\intercept_event\Entity\EventAttendanceInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Event Attendance edit forms.
 *
 * @ingroup intercept_event
 */
class EventAttendanceScanFormBase extends ContentEntityForm {

  const SUCCESS_MESSAGE = 'You\'ve successfully scanned in!';

  const ATTENDANCE_EXISTS_MESSAGE = 'You\'ve already scanned in.';

  /**
   * @var ExternalAuth
   */
  protected $externalAuth;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, ExternalAuth $external_auth) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->externalAuth = $external_auth;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('externalauth.externalauth')
    );
  }

  /**
   * Header text for form display.
   */
  protected function instructionsHeaderText() {
    return '';
  }

  /**
   * Instructions text for form display.
   */
  protected function instructionsText() {
    return '';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\intercept_event\Entity\EventAttendance */
    $form = parent::buildForm($form, $form_state);

    $form['#theme'] = 'event_attendance_scan_form';
    $entity = $this->entity;

    $event = $this->entity->field_event->entity;
    $node_view = \Drupal::service('entity_type.manager')->getHandler('node', 'view_builder');
    $form['event'] = $node_view->view($event, 'summary');
    $form['instructions_header'] = [
      '#type' => 'html_tag',
      '#tag' => 'h4',
      '#attributes' => ['class' => ['instructions-header']],
      '#value' => $this->instructionsHeaderText(),
    ];
    $form['instructions_text'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['class' => ['instructions-text']],
      '#value' => $this->instructionsText(),
    ];
    return $form;
  }

  /**
   * Get related event node.
   *
   * @return NodeInterface
   */
  protected function event() {
    return $this->entity->field_event->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t(self::SUCCESS_MESSAGE, [
          '%label' => $entity->label(),
        ]));
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('enter');
    return $actions;
  }

  /**
   * Load or create a Drupal user from barcode/ils username.
   *
   * @param $barcode
   *   The library barcode or ILS username.
   * @return bool|UserInterface
   */
  protected function createAttendee($barcode) {
    $user = \Drupal::service('intercept_ils.mapping_manager')->loadByBarcode($barcode);
    return $user;
  }

  /**
   * Populate event attendance from registration if applicable and set user.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @param UserInterface $user
   */
  protected function populateAttendance(array $form, FormStateInterface $form_state, UserInterface $user) {
    $form_state->setValue('field_user', $user->id());
    $storage = $this->entityTypeManager->getStorage('event_registration');
    $registrations = $storage->loadByProperties(['field_event' => $this->event()->id(), 'field_user' => $user->id()]);
    if (!empty($registrations) && ($registration = reset($registrations))) {
      $value = $registration->field_registrants->getValue();
      if (!empty($value)) {
        $this->entity->field_attendees->setValue($value);
      }
    }
  }

  /**
   * Check if the attendance exists by field_event and field_user.
   *
   * @param $uid
   *   User id derived from the barcode.
   * @return bool|EventAttendanceInterface
   */
  protected function attendanceExists($uid) {
    $storage = \Drupal::service('entity_type.manager')->getStorage('event_attendance');
    $result = $storage->loadByProperties([
      'field_event' => $this->entity->field_event->entity->id(),
      'field_user' => $uid,
    ]);
    return !empty($result) ? reset($result) : FALSE;
  }

  /**
   * Common function to set an error for the barcode and clear the form.
   *
   * @param $message
   *   Text to display to the user.
   * @param $form
   * @param FormStateInterface $form_state
   */
  protected function setBarcodeError($message, &$form, FormStateInterface $form_state) {
    $form_state->setErrorByName('barcode', $this->t($message));
    // Reset completely so it can be re-scanned.
    $form['barcode']['#value'] = '';
    $form_state->setValue('barcode', '');
  }

  /**
   * Cancel link to return to base scan form.
   *
   * @return array
   */
  protected function cancelButton() {
    return [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => \Drupal\Core\Url::fromRoute('entity.node.scan', [
        'node' => $this->event()->id(),
      ]),
    ];
  }

  /**
   * Redirect form submission to base scan form.
   */
  protected function redirectToBaseForm(FormStateInterface $form_state) {
    $form_state->setRedirect('entity.node.scan', [
      'node' => $this->event()->id(),
    ]);
  }
}
