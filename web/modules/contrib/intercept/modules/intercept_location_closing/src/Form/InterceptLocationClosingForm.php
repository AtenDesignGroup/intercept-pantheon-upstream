<?php

namespace Drupal\intercept_location_closing\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Location Closing edit forms.
 *
 * @ingroup intercept_location_closing
 */
class InterceptLocationClosingForm extends ContentEntityForm {

  /**
   * @var \Drupal\intercept_location_closing\InterceptLocationClosingQuery
   */
  protected $locationClosingQuery;

  /**
   * {@inheritdoc}
   */
  public static function create($container) {
    $instance = new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
    );

    $instance->locationClosingQuery = $container->get('intercept_location_closing.query');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // On closing add form, check all locations by default.
    if ($this->entity->isNew()) {
      $location_options = array_keys($form['location']['widget']['#options']);
      $form['location']['widget']['#default_value'] = $location_options;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Location Closing.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Location Closing.', [
          '%label' => $entity->label(),
        ]));
    }

    // Check for event conflicts.
    $conflicts = $this->locationClosingQuery->eventsConflictingWithClosing($entity, TRUE);
    if (!empty($conflicts)) {
      $this->messenger()->addWarning(\Drupal::translation()->formatPlural(
        count($conflicts),
        'There is @count published event scheduled during this closing period. Please review the %link.',
        'There are @count published events scheduled during this closing period. Please review the %link.',
        [
          '@count' => count($conflicts),
          '%link' => $entity->toLink('list of conflicts', 'event-conflicts')->toString(),
        ]
      ));
    }

    $form_state->setRedirect('entity.intercept_location_closing.canonical', ['intercept_location_closing' => $entity->id()]);
  }

}
