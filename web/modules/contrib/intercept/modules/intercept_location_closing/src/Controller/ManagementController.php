<?php

namespace Drupal\intercept_location_closing\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\intercept_core\Controller\ManagementControllerBase;
use Drupal\intercept_location_closing\InterceptLocationClosingListBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a class to build a listing of Location Closing entities.
 *
 * @ingroup intercept_location_closing
 */
class ManagementController extends ManagementControllerBase {

  /**
   * {@inheritdoc}
   */
  public function alter(array &$build, $page_name) {
    if ($page_name == 'default') {
      $build['sections']['main']['#actions']['location_closings'] = [
        '#link' => $this->getManagementButton('Manage Closings', 'location_closings'),
        '#weight' => 10,
      ];
    }
  }

  /**
   * The view handler for the 'location_closings' intercept management page.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user account.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return array
   *   The build array needed by the intercept management plugin.
   */
  public function viewLocationClosings(AccountInterface $user, Request $request) {
    return [
      'title' => $this->title('Manage Closings'),
      'location_create' => $this->getButton('Create closing', 'entity.intercept_location_closing.add_form',
      [], ['attributes' => ['class' => ['button', 'create-content-button']]]),
      'sections' => [
        'list' => [
          '#content' => $this->getList(InterceptLocationClosingListBuilder::class, 'intercept_location_closing'),
          '#actions' => [
            'location_add' => [
              '#link' => $this->getButton('Add Closing', 'intercept_location_closing.add', []),
            ],
          ],
        ],
      ],
    ];
  }

}
