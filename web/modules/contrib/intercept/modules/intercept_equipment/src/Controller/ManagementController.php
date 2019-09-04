<?php

namespace Drupal\intercept_equipment\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\intercept_core\Controller\ManagementControllerBase;
use Symfony\Component\HttpFoundation\Request;

class ManagementController extends ManagementControllerBase {

  public function alter(array &$build, $page_name) {
    if ($page_name == 'system_configuration') {
      $build['sections']['main']['#actions']['equipment'] = [
        '#link' => $this->getManagementButton('Equipment', 'equipment_configuration'),
        '#weight' => '5',
      ];
    }

    if ($page_name == 'default') {
      // $build['sections']['main']['#actions']['equipment'] = [
      //   '#link' => $this->getButton('Reserve equipment', 'entity.equipment_reservation.add_form'),
      // ]
      $build['sections']['main']['#actions']['equipment'] = [
        '#link' => $this->getButton('Reserve Equipment', 'view.intercept_equipment.page'),
        '#access' => $this->currentUser->hasPermission('add equipment reservation entities'),
        '#weight' => '-10',
      ];
    }
  }

  public function viewEquipmentReservations(AccountInterface $user, Request $request) {
    return [
      'title' => $this->title('Equipment Reservations'),
      'equipment_reservation_create' => $this->getButton('Reserve equipment', 'view.intercept_equipment.page'),
      'content' => [
        '#type' => 'view',
        '#name' => 'intercept_equipment_reservations',
        '#display_id' => 'embed',
      ],
    ];
  }

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
                'destination' => \Drupal\Core\Url::fromRoute('<current>')->toString(),
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
