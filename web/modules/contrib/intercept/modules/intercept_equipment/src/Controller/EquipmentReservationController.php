<?php

namespace Drupal\intercept_equipment\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class EquipmentReservationController.
 *
 *  Returns responses for Equipment Reservation routes.
 */
class EquipmentReservationController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Hello.
   *
   * @return string
   *   Return Hello string.
   */
  public function reserveEquipment() {
    $build = [];
    $build['#attached']['library'][] = 'intercept_equipment/reserveEquipment';
    $build['#markup'] = '';
    $build['intercept_equipment_reservation']['#markup'] = '<div id="reserveEquipmentRoot"></div>';

    return $build;
  }

  /**
   * Displays a Equipment reservation revision.
   *
   * @param int $equipment_reservation_revision
   *   The Equipment reservation revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($equipment_reservation_revision) {
    $equipment_reservation = $this->entityTypeManager()->getStorage('equipment_reservation')->loadRevision($equipment_reservation_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('equipment_reservation');

    return $view_builder->view($equipment_reservation);
  }

  /**
   * Page title callback for a Equipment reservation revision.
   *
   * @param int $equipment_reservation_revision
   *   The Equipment reservation revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($equipment_reservation_revision) {
    $equipment_reservation = $this->entityTypeManager()->getStorage('equipment_reservation')->loadRevision($equipment_reservation_revision);
    return $this->t('Revision of %title from %date', ['%title' => $equipment_reservation->label(), '%date' => \Drupal::service('date.formatter')->format($equipment_reservation->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Equipment reservation .
   *
   * @param \Drupal\intercept_equipment\Entity\EquipmentReservationInterface $equipment_reservation
   *   A Equipment reservation object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(EquipmentReservationInterface $equipment_reservation) {
    $account = $this->currentUser();
    $langcode = $equipment_reservation->language()->getId();
    $langname = $equipment_reservation->language()->getName();
    $languages = $equipment_reservation->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $equipment_reservation_storage = $this->entityTypeManager()->getStorage('equipment_reservation');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $equipment_reservation->label()]) : $this->t('Revisions for %title', ['%title' => $equipment_reservation->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all equipment reservation revisions") || $account->hasPermission('administer equipment reservation entities')));
    $delete_permission = (($account->hasPermission("delete all equipment reservation revisions") || $account->hasPermission('administer equipment reservation entities')));

    $rows = [];

    $vids = $equipment_reservation_storage->revisionIds($equipment_reservation);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\intercept_equipment\EquipmentReservationInterface $revision */
      $revision = $equipment_reservation_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $equipment_reservation->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.equipment_reservation.revision', ['equipment_reservation' => $equipment_reservation->id(), 'equipment_reservation_revision' => $vid]));
        }
        else {
          $link = $equipment_reservation->link($date);
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
              'url' => $has_translations ?
              Url::fromRoute('entity.equipment_reservation.translation_revert', [
                'equipment_reservation' => $equipment_reservation->id(),
                'equipment_reservation_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.equipment_reservation.revision_revert', ['equipment_reservation' => $equipment_reservation->id(), 'equipment_reservation_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.equipment_reservation.revision_delete', ['equipment_reservation' => $equipment_reservation->id(), 'equipment_reservation_revision' => $vid]),
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

    $build['equipment_reservation_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
