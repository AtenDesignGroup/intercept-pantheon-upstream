<?php

namespace Drupal\flag\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\FlagServiceInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Hook implementations for flag.
 */
class FlagViewsHooks {

  use StringTranslationTrait;

  public function __construct(
    protected FlagServiceInterface $flagService,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData() {
    $data = [];
    $data['flag_counts']['count'] = [
      'title' => $this->t('Flag counter'),
      'help' => $this->t('The number of times a piece of content is flagged by any user.'),
      'field' => [
        'id' => 'numeric',
      ],
    ];
    $data['flag_counts']['last_updated'] = [
      'title' => $this->t('Time last flagged'),
      'help' => $this->t('The time a piece of content was most recently flagged by any user.'),
      'field' => [
        'id' => 'date',
      ],
    ];
    return $data;
  }

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data) {
    $flags = $this->flagService->getAllFlags();
    $entity_type_manager = $this->entityTypeManager;
    foreach ($flags as $flag) {
      $entity_type_id = $flag->getFlaggableEntityTypeId();
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);
      if ($entity_type->hasHandlerClass('views_data')) {
        $base_table = $entity_type_manager->getHandler($entity_type_id, 'views_data')->getViewsTableForEntityType($entity_type);
        $data[$base_table]['flag_relationship'] = [
          'title' => $this->t('@entity_label flag', [
            '@entity_label' => $entity_type->getLabel(),
          ]),
          'help' => $this->t('Limit results to only those entity flagged by a certain flag; Or display information about the flag set on a entity.'),
          'relationship' => [
            'group' => $this->t('Flag'),
            'label' => $this->t('Flags'),
            'base' => 'flagging',
            'base field' => 'entity_id',
            'relationship field' => $entity_type->getKey('id'),
            'id' => 'flag_relationship',
            'flaggable' => $entity_type_id,
          ],
        ];
      }
    }
  }

}
