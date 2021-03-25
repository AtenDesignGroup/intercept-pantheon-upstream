<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\intercept_core\Controller\ManagementControllerBase;
use Drupal\views\Element\View;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;

/**
 * The management controller for intercept_event.
 */
class ManagementController extends ManagementControllerBase {

  /**
   * {@inheritdoc}
   */
  public function alter(array &$build, $page_name) {
    if ($page_name == 'system_configuration') {
      $build['sections']['main']['#actions']['events'] = [
        '#link' => $this->getManagementButton('Events', 'event_configuration'),
        '#weight' => 8,
      ];
    }
  }

  /**
   * Helper function to create a link to the template page for creating events.
   */
  private function getCreateEventButton() {
    $route = "intercept_event.management.event_templates";
    return $this->getButton('Create an Event', 
    $route, [
      'user' => $this->currentUser()->id(),
    ],
    ['attributes' => ['class' => ['button', 'create-content-button']]]
  );
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
  public function viewEventAttendanceExport(AccountInterface $user, Request $request) {
    return [
      'title' => $this->title('Export Scans'),
      'download_link' => $this->getButton('Download CSV',
        'view.intercept_event_attendance.rest_export',
        ['_format' => 'csv'] + $request->query->all(),
        ['attributes' => ['class' => ['button', 'create-content-button']]]
      ),
      'view' => [
        '#type' => 'view',
        '#pre_render' => [
            [$this, 'preRenderEventAttendance'],
            [View::class, 'preRenderViewElement'],
        ],
        '#name' => 'intercept_event_attendance',
        '#display_id' => 'embed',
      ],
    ];
  }

  /**
   * Overrides the event attendance element.
   *
   * @param array $element
   *   The event attendance element.
   *
   * @return array
   *   The modified event attendance element.
   */
  public static function preRenderEventAttendance(array $element) {
    $view = !isset($element['#view']) ? Views::getView($element['#name']) : $element['#view'];
    $view->override_path = 'test';
    $view->override_url = Url::fromRoute('<current>');
    $element['#view'] = $view;
    return $element;
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
  public function viewEvents(AccountInterface $user, Request $request) {
    return [
      'title' => $this->title('Events'),
      'event_create' => $this->getCreateEventButton(),
      'content' => [
        '#type' => 'view',
        '#name' => 'intercept_events',
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
  public function viewEventConfiguration(AccountInterface $user, Request $request) {
    $lists = $this->table();
    $link = $this->getButton('Event Series', 'system.admin_content', [
      'type' => 'event_series',
    ]);
    $lists->row($link, $this->t('List of all Event Series, a method for grouping events together (e.g. Summer Reading Challenge).'));
    $link = $this->getButton('Event Templates', 'intercept_event.management.event_templates', [
      'user' => $this->currentUser()->id(),
    ]);
    $lists->row($link, $this->t('List of all events categorized as a template. Only System Admins can categorize an event as a template.'));

    return [
      'title' => $this->title('Events'),
      'sections' => [
        'content_types' => [
          '#actions' => [
            'event_template' => [
              '#link' => $this->getButton(
                'Add Event Template',
                'node.add',
                [
                  'node_type' => 'event',
                  'template' => 1,
                ],
                ['attributes' => ['class' => ['button', 'create-content-button']]]
              ),
            ],
            'event_series' => [
              '#link' => $this->getButton(
                'Add Event Series',
                'node.add',
                [
                  'node_type' => 'event_series',
                ]
              ),
            ],
          ],
          '#content' => $lists->toArray(),
        ],
        'taxonomies' => $this->getTaxonomyVocabularyTable([
          'audience',
          'evaluation_criteria',
          'event_type',
          'population_segment',
          'tag',
        ]),
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
  public function viewEventTemplates(AccountInterface $user, Request $request) {
    return [
      '#type' => 'view',
      '#name' => 'intercept_event_templates',
      '#display_id' => 'embed',
    ];
  }

}
