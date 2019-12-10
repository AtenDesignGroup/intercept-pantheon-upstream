<?php

namespace Drupal\intercept_room_reservation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Room Reservation Agreement Form.
 */
class RoomReservationAgreementForm extends FormBase {

  /**
   * The private temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * RoomReservationAgreementForm constructor.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'room_reservation_agreement_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['agree'] = [
      '#type' => 'submit',
      '#value' => $this->t('Agree'),
      '#submit' => ['::agree'],
    ];

    return $form;
  }

  /**
   * Sets the room reservation agreement.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function agree(array &$form, FormStateInterface $form_state) {
    $temp_store = $this->tempStoreFactory->get('reservation_agreement');
    $temp_store->set('room', 1);
  }

}
