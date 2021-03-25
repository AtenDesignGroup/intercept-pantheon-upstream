<?php

namespace Drupal\intercept_equipment\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\intercept_core\Controller\ManagementControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * The management controller for intercept_equipment.
 */
class ManagementController extends ManagementControllerBase {

  /**
   * {@inheritdoc}
   */
  public function alter(array &$build, $page_name) {
    if ($page_name == 'system_configuration') {
      $build['sections']['main']['#actions']['equipment'] = [
        '#link' => $this->getManagementButton('Equipment', 'equipment_configuration'),
        '#weight' => '5',
      ];
    }
  }

  /**
   * Subpage of viewSettings.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return array
   *   The build render array.
   */
  public function viewEquipmentReservations(AccountInterface $user, Request $request) {
    return [
      'title' => $this->title('Equipment Reservations'),
      'equipment_reservation_create' => $this->getButton('Reserve Equipment', 'view.intercept_equipment.page',
      [], ['attributes' => ['class' => ['button', 'create-content-button']]]),
      'content' => [
        '#type' => 'view',
        '#name' => 'intercept_equipment_reservations',
        '#display_id' => 'embed',
      ],
    ];
  }

  /**
   * Subpage of viewSettings.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return array
   *   The build render array.
   */
  public function viewEquipmentConfiguration(AccountInterface $user, Request $request) {
    $lists = $this->table();
    $link = $this->getButton('Equipment List', 'system.admin_content', [
      'type' => 'equipment',
    ]);
    $lists->row($link, $this->t('List of all equipment.'));

    return [
      'title' => $this->title('Equipment'),
      'sections' => [
        'main' => [
          '#actions' => [
            'equipment_add' => [
              '#link' => $this->getButton('Add Equipment', 'node.add', [
                'node_type' => 'equipment',
                'destination' => Url::fromRoute('<current>')->toString(),
              ]),
            ],
          ],
          '#content' => $lists->toArray(),
        ],
        'taxonomies' => $this->getTaxonomyVocabularyTable(['equipment_type']),
      ],
    ];
  }

}
