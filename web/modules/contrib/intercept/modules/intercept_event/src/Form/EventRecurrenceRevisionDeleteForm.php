<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Event Recurrence revision.
 *
 * @ingroup intercept_event
 */
class EventRecurrenceRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The Event Recurrence revision.
   *
   * @var \Drupal\intercept_event\Entity\EventRecurrenceInterface
   */
  protected $revision;

  /**
   * The Event Recurrence storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $eventRecurrenceStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new EventRecurrenceRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection) {
    $this->eventRecurrenceStorage = $entity_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $entity_manager->getStorage('event_recurrence'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_recurrence_revision_delete_confirm';
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
    return new Url('entity.event_recurrence.version_history', ['event_recurrence' => $this->revision->id()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $event_recurrence_revision = NULL) {
    $this->revision = $this->eventRecurrenceStorage->loadRevision($event_recurrence_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->eventRecurrenceStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Event Recurrence: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    \Drupal::messenger()->addMessage($this->t('Revision from %revision-date of Event Recurrence %title has been deleted.', ['%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.event_recurrence.canonical',
       ['event_recurrence' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {event_recurrence_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.event_recurrence.version_history',
         ['event_recurrence' => $this->revision->id()]
      );
    }
  }

}
