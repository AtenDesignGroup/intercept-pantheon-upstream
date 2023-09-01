<?php

namespace Drupal\intercept_bulk_room_reservation\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class RemoveOverrideConfirmForm extends ConfirmFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RemoveOverrideConfirmForm.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Creates a RemoveOverrideConfirmForm.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container interface.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'intercept_bulk_room_reservation_remove_override_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove the override for this room reservation?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.admin_config');
  }

  /**
   * {@inheritdoc}
   */
  public function getBulkRoomReservationUrl() {
    $bulkRoomReservationId = $this->getBulkRoomReservationId();
    return new Url('entity.bulk_room_reservation.canonical', ['bulk_room_reservation' => $bulkRoomReservationId]);
  }

  /**
   * {@inheritdoc}
   */
  public function getBulkRoomReservationId() {
    // Determine the room reservation id from the url.
    // See intercept_bulk_room_reservation.routing.yml.
    $current_path = \Drupal::service('path.current')->getPath();
    $path_array = explode('/', $current_path);
    if (!array_key_exists(2, $path_array)) {
      $this->messenger()->addError($this->t('Error: improperly formed url for route.'));
      $form_state->setRedirectUrl($this->getCancelUrl());
    }

    $roomReservationId = $path_array[2];

    return $roomReservationId;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['title'] = [
      '#type' => 'item',
      '#markup' => '<h1>' . $this->t('Remove override from a room reservation') . '</h1>',
    ];

    $form['question'] = [
      '#type' => 'item',
      '#markup' => '<p>' . $this->getQuestion() . '</p>',
    ];

    $form['description']['#weight'] = 1;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $roomReservationId = $this->getBulkRoomReservationId();
    $query = \Drupal::entityQuery('bulk_room_reservation')
      ->accessCheck(FALSE)
      ->condition('field_overridden', $roomReservationId);
    $result = $query->execute();
    if (count($result)) {
      $bulkRoomReservationId = reset($result);
      $roomReservation = $this->entityTypeManager
        ->getStorage('bulk_room_reservation')
        ->load($bulkRoomReservationId);
      foreach ($roomReservation->field_overridden as $id => $overridden) {
        if ($overridden->target_id == $roomReservationId) {
          $roomReservation->get('field_overridden')->removeItem($id);
          $roomReservation->save();
          $this->messenger()->addStatus($this->t('The override has been removed.'));
          $form_state->setRedirectUrl($this->getCancelUrl());
          return;
        }
      }
    }

    $this->messenger()->addStatus($this->t('Something went wrong in removing the override.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
