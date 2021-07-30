<?php

namespace Drupal\intercept_certification\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\intercept_certification\Entity\CertificationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CertificationController.
 *
 *  Returns responses for Certification routes.
 */
class CertificationController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Certification revision.
   *
   * @param int $certification_revision
   *   The Certification revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($certification_revision) {
    $certification = $this->entityTypeManager()->getStorage('certification')
      ->loadRevision($certification_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('certification');

    return $view_builder->view($certification);
  }

  /**
   * Page title callback for a Certification revision.
   *
   * @param int $certification_revision
   *   The Certification revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($certification_revision) {
    $certification = $this->entityTypeManager()->getStorage('certification')
      ->loadRevision($certification_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $certification->label(),
      '%date' => $this->dateFormatter->format($certification->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Certification.
   *
   * @param \Drupal\intercept_certification\Entity\CertificationInterface $certification
   *   A Certification object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(CertificationInterface $certification) {
    $account = $this->currentUser();
    $certification_storage = $this->entityTypeManager()->getStorage('certification');

    $langcode = $certification->language()->getId();
    $langname = $certification->language()->getName();
    $languages = $certification->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $certification->label()]) : $this->t('Revisions for %title', ['%title' => $certification->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all certification revisions") || $account->hasPermission('administer certification entities')));
    $delete_permission = (($account->hasPermission("delete all certification revisions") || $account->hasPermission('administer certification entities')));

    $rows = [];

    $vids = $certification_storage->revisionIds($certification);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\intercept_certification\CertificationInterface $revision */
      $revision = $certification_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $certification->getRevisionId()) {
          $link = $this->l($date, new Url('entity.certification.revision', [
            'certification' => $certification->id(),
            'certification_revision' => $vid,
          ]));
        }
        else {
          $link = $certification->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
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
              Url::fromRoute('entity.certification.translation_revert', [
                'certification' => $certification->id(),
                'certification_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.certification.revision_revert', [
                'certification' => $certification->id(),
                'certification_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.certification.revision_delete', [
                'certification' => $certification->id(),
                'certification_revision' => $vid,
              ]),
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

    $build['certification_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

  /**
   * Gets a list of rooms that have been marked with certification required.
   *
   * @return array
   *   The list of room node ids.
   */
  public static function getCertificationRooms() {
    $query = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'room')
        ->condition('field_requires_certification', 1);
    $options = $query->execute();
    return $options;
  }

  /**
   * Get certifications that exist for the given user.
   * 
   * @param int $uid
   *   The user id to check.
   *
   * @return array
   *   The room certifications for a user.
   */
  public static function getUserCertifications($uid) {
    $query = \Drupal::entityQuery('certification')
      ->condition('field_user', $uid);
    $result = $query->execute();
    return $result;
  }

  /**
   * Certification is being removed.
   *
   * @param int $uid
   *   The user id that the certification pertains to.
   * @param int $room_id
   *   The room node id that the certification pertains to.
   */
  public static function deleteCertification($uid, $room_id) {
    // Find the certification id.
    $query = \Drupal::entityQuery('certification')
      ->condition('field_user', $uid)
      ->condition('field_room', $room_id);
    $result = $query->execute();
    if (count($result) == 1) {
      $certification_id = reset($result);
      // Load the entity by id.
      $certification = \Drupal::entityTypeManager()->getStorage('certification')
        ->load($certification_id);
      // Then delete.
      $certification->delete();
    }
  }

  /**
   * Certification is being added.
   *
   * @param int $uid
   *   The user id that the certification pertains to.
   * @param int $room_id
   *   The room node id that the certification pertains to.
   */
  public static function addCertification($uid, $room_id) {
    // Entity add function already?
    $values = [
      'field_user' => $uid,
      'field_room' => $room_id,
    ];

    $certification = \Drupal::entityTypeManager()->getStorage('certification')
      ->create($values);
    $certification->save();
  }

}
