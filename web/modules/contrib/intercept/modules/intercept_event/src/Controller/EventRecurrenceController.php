<?php

namespace Drupal\intercept_event\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\intercept_event\Entity\EventRecurrenceInterface;

/**
 * Class EventRecurrenceController.
 *
 *  Returns responses for Event Recurrence routes.
 */
class EventRecurrenceController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Event Recurrence  revision.
   *
   * @param int $event_recurrence_revision
   *   The Event Recurrence  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($event_recurrence_revision) {
    $event_recurrence = $this->entityTypeManager()->getStorage('event_recurrence')->loadRevision($event_recurrence_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('event_recurrence');

    return $view_builder->view($event_recurrence);
  }

  /**
   * Page title callback for a Event Recurrence  revision.
   *
   * @param int $event_recurrence_revision
   *   The Event Recurrence  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($event_recurrence_revision) {
    $event_recurrence = $this->entityTypeManager()->getStorage('event_recurrence')->loadRevision($event_recurrence_revision);
    return $this->t('Revision of %title from %date', ['%title' => $event_recurrence->label(), '%date' => \Drupal::service('date.formatter')->format($event_recurrence->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Event Recurrence .
   *
   * @param \Drupal\intercept_event\Entity\EventRecurrenceInterface $event_recurrence
   *   A Event Recurrence  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(EventRecurrenceInterface $event_recurrence) {
    $account = $this->currentUser();
    $langcode = $event_recurrence->language()->getId();
    $langname = $event_recurrence->language()->getName();
    $languages = $event_recurrence->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $event_recurrence_storage = $this->entityTypeManager()->getStorage('event_recurrence');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $event_recurrence->label()]) : $this->t('Revisions for %title', ['%title' => $event_recurrence->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all event recurrence revisions") || $account->hasPermission('administer event recurrence entities')));
    $delete_permission = (($account->hasPermission("delete all event recurrence revisions") || $account->hasPermission('administer event recurrence entities')));

    $rows = [];

    $vids = $event_recurrence_storage->revisionIds($event_recurrence);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\intercept_event\EventRecurrenceInterface $revision */
      $revision = $event_recurrence_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $event_recurrence->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.event_recurrence.revision', ['event_recurrence' => $event_recurrence->id(), 'event_recurrence_revision' => $vid]));
        }
        else {
          $link = $event_recurrence->toLink($date)->toString();
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.event_recurrence.revision_revert', ['event_recurrence' => $event_recurrence->id(), 'event_recurrence_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.event_recurrence.revision_delete', ['event_recurrence' => $event_recurrence->id(), 'event_recurrence_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['event_recurrence_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
