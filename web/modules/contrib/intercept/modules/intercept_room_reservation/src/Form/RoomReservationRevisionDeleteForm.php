<?php

namespace Drupal\intercept_room_reservation\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Room reservation revision.
 *
 * @ingroup intercept_room_reservation
 */
class RoomReservationRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The Room reservation revision.
   *
   * @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface
   */
  protected $revision;

  /**
   * The Room reservation storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $roomReservationStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new RoomReservationRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection) {
    $this->roomReservationStorage = $entity_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('room_reservation'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'room_reservation_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', ['%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.room_reservation.version_history', ['room_reservation' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $room_reservation_revision = NULL) {
    $this->revision = $this->roomReservationStorage->loadRevision($room_reservation_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->roomReservationStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Room reservation: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    \Drupal::messenger()->addMessage(t('Revision from %revision-date of Room reservation %title has been deleted.', ['%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.room_reservation.canonical',
       ['room_reservation' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {room_reservation_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.room_reservation.version_history',
         ['room_reservation' => $this->revision->id()]
      );
    }
  }

}
