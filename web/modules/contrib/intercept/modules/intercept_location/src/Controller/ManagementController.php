<?php

namespace Drupal\intercept_location\Controller;

use Drupal\intercept_core\Controller\ManagementControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\intercept_location\RoomListBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * The management controller for intercept_location.
 */
class ManagementController extends ManagementControllerBase {

  /**
   * {@inheritdoc}
   */
  public function alter(array &$build, $page_name) {
    if ($page_name == 'system_configuration') {
      $build['sections']['main']['#actions']['location_rooms'] = [
        '#link' => $this->getManagementButton('Locations & Rooms', 'locations_rooms'),
        '#weight' => 10,
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
  public function viewLocationsRooms(AccountInterface $user, Request $request) {
    return [
      'title' => $this->title('Locations & Rooms'),
      'sections' => [
        'main' => [
          '#actions' => [
            'location_add' => [
              '#link' => $this->getButton('Add Location', 'node.add', [
                'node_type' => 'location',
              ]),
            ],
            'room_add' => [
              '#link' => $this->getButton('Add Room', 'node.add', [
                'node_type' => 'room',
              ]),
            ],
          ],
        ],
        'list' => [
          '#content' => $this->getList(RoomListBuilder::class),
        ],
        'taxonomies' => $this->getTaxonomyVocabularyTable(['room_type']),
      ],
    ];
  }

}
