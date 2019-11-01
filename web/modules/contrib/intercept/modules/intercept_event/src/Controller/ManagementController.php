<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Core\Url;
use Drupal\intercept_core\Controller\ManagementControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Element\View;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;

class ManagementController extends ManagementControllerBase {

  public function alter(array &$build, $page_name) {
    if ($page_name == 'system_configuration') {
      $build['sections']['main']['#actions']['events'] = [
        '#link' => $this->getManagementButton('Events', 'event_configuration'),
        '#weight' => 8,
      ];
    }
    if ($page_name == 'default') {
      $route = "intercept_event.management.event_templates";
      $build['sections']['main']['#actions']['event'] = [
        '#link' => $this->getCreateEventButton(),
        '#weight' => -15,
      ];
      if ($this->currentUser()->hasPermission('create event content')) {
        $build['sections']['main']['#actions']['events_all'] = [
          '#link' => $this->getManagementButton('View all Events', 'events'),
        ];
      }
    }
  }

  /**
   * Helper function to create a link to the template page for creating events.
   */
  private function getCreateEventButton() {
    $route = "intercept_event.management.event_templates";
    return $this->getButton('Create an Event', $route, [
      'user' => $this->currentUser()->id(),
    ]);
  }

  public function viewEventAttendanceExport(AccountInterface $user, Request $request) {
    return [
      'title' => $this->title('Event Attendance'),
      'download_link' => $this->getButton('Download CSV',
        'view.intercept_event_attendance.rest_export',
        ['_format' => 'csv'] + $request->query->all()
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
  public static function preRenderEventAttendance($element) {
    $view = !isset($element['#view']) ? Views::getView($element['#name']) : $element['#view'];
    $view->override_path = 'test';
    $view->override_url = Url::fromRoute('<current>');
    $element['#view'] = $view;
    return $element;
  }

  public function viewEvents(AccountInterface $user, Request $request, $is_admin = FALSE) {
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
                ]
              ),
            ],
            'event_series' => [
              '#link' => $this->getButton(
                'Add Event Series',
                'node.add',
                [
                  'node_type' => 'event_series'
                ]
              ),
            ],
          ],
          '#content' => $lists->toArray(),
        ],
        'taxonomies' => $this->getTaxonomyVocabularyTable(['audience', 'evaluation_criteria', 'event_type', 'population_segment', 'tag']),
      ]
    ];
  }

  public function viewEventTemplates(AccountInterface $user, Request $request) {
    return [
      '#type' => 'view',
      '#name' => 'intercept_event_templates',
      '#display_id' => 'embed',
    ];
  }
}