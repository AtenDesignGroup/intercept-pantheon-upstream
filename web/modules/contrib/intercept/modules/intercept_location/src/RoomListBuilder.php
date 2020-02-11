<?php

namespace Drupal\intercept_location;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\NodeListBuilder;

/**
 * List builder for Room Nodes.
 */
class RoomListBuilder extends NodeListBuilder {

  /**
   * A list of Locations.
   *
   * @var array
   */
  private $locations = [];

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Enable language column and filter if multiple languages are added.
    $header = [
      'title' => $this->t('Title'),
      'capacity' => [
        'data' => $this->t('Capacity'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'type' => [
        'data' => $this->t('Type'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    if (\Drupal::languageManager()->isMultilingual()) {
      $header['language_name'] = [
        'data' => $this->t('Language'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }
    $header['operations'] = $this->t('Operations');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\node\NodeInterface $entity */
    if ($entity->bundle() == 'location') {
      $row['title'] = mb_strtoupper($entity->label());
      $row['capacity'] = [
        'data' => '',
        'colspan' => 2,
      ];
      $row['operations']['data'] = $this->buildOperations($entity);
      return $row;
    }

    $languages = $entity->getTranslationLanguages();
    $langcode = $entity->language()->getId();
    $uri = $entity->toUrl();
    $options = $uri->getOptions();
    $options += ($langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED && isset($languages[$langcode]) ? ['language' => $languages[$langcode]] : []);
    $uri->setOptions($options);
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => $uri,
    ];
    if (!$entity->isPublished()) {
      $row['title']['#suffix'] = ' ' . $this->t('(not published)');
    }
    $row['capacity'] = implode('-', [
      $entity->field_capacity_min->getString(),
      $entity->field_capacity_max->getString(),
    ]);
    $row['type'] = !empty($entity->field_room_type->entity) ? $entity->field_room_type->entity->label() : '';
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort('field_location')
      ->condition('type', 'room', '=');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There is no @label yet.', ['@label' => $this->entityType->getLabel()]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
    foreach ($this->load() as $entity) {
      $location = $entity->field_location->entity;

      if (!empty($location) && !isset($this->locations[$location->id()]) && ($row = $this->buildRow($location))) {
        $build['table']['#rows'][$location->id()] = $row;
        $this->locations[$location->id()] = $location;
      }

      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }
    return $build;
  }

}
