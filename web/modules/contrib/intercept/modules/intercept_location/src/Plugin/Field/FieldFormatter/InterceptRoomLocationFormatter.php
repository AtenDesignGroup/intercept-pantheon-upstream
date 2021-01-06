<?php

namespace Drupal\intercept_location\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'intercept_room_location' formatter.
 *
 * @FieldFormatter(
 *   id = "intercept_room_location",
 *   label = @Translation("Location + Room"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class InterceptRoomLocationFormatter extends EntityReferenceLabelFormatter {

  /**
   * The machine name of room field for which this formatter applies.
   */
  const ROOM_FIELD = 'field_room';

  /**
   * The machine name of location field referenced from the room entity.
   */
  const LOCATION_FIELD = 'field_location';

  /**
   * Gets the location entities for the given room.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $roomEntity
   *   The Room entity.
   *
   * @return [Drupal\Core\Entity\EntityInterface]
   *   An array of location entities.
   */
  private static function getLocationEntitiesFromRoom(ContentEntityBase $roomEntity) {
    return $roomEntity->get(self::LOCATION_FIELD)->referencedEntities();
  }

  /**
   * Build a render array for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   A content entity.
   * @param bool $output_as_link
   *   TRUE if this should render as a link,
   *   FALSE if it should render the label as plain text.
   *
   * @return array
   *   A render array.
   */
  private static function buildRenderElement(ContentEntityBase $entity, bool $output_as_link) {
    $element = [];
    $label = $entity->label();
    // If the link is to be displayed and the entity has a uri, display a
    // link.
    if ($output_as_link && !$entity->isNew()) {
      try {
        $uri = $entity->toUrl();
      }
      catch (UndefinedLinkTemplateException $e) {
        // This exception is thrown by \Drupal\Core\Entity\Entity::urlInfo()
        // and it means that the entity type doesn't have a link template nor
        // a valid "uri_callback", so don't bother trying to output a link for
        // the rest of the referenced entities.
        $output_as_link = FALSE;
      }
    }

    if ($output_as_link && isset($uri) && !$entity->isNew()) {
      $element = [
        '#type' => 'link',
        '#title' => $label,
        '#url' => $uri,
        '#options' => $uri->getOptions(),
      ];
    }
    else {
      $element = ['#plain_text' => $label];
    }
    $element['#cache']['tags'] = $entity->getCacheTags();

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'location_link' => TRUE,
      'link' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getName() === self::ROOM_FIELD;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['location_link'] = [
      '#title' => t('Link location label to entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('location_link'),
    ];

    $elements['link'] = [
      '#title' => t('Link room label to entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('location_link') ? t('Link to location') : t('Do not link to location');
    $summary[] = $this->getSetting('link') ? t('Link to room') : t('Do not link to room');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output_as_link = $this->getSetting('link');
    $output_location_as_link = $this->getSetting('location_link');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $elements[$delta] = [
        '#theme' => 'intercept_room_location',
        '#room' => self::buildRenderElement($entity, $output_as_link, $items[$delta]),
      ];

      if (isset($elements[$delta]['#room']['#type']) && !empty($items[$delta]->_attributes)) {
        $elements[$delta]['#room']['#options'] += ['attributes' => []];
        $elements[$delta]['#room']['#options']['attributes'] += $items[$delta]->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and shouldn't be rendered in the field template.
        unset($items[$delta]->_attributes);
      }

      $locations = self::getLocationEntitiesFromRoom($entity);

      if (!empty($locations)) {
        foreach ($locations as $locationDelta => $locationEntity) {
          $elements[$delta]['#location'][$locationDelta] = self::buildRenderElement($locationEntity, $output_location_as_link);
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view label', NULL, TRUE);
  }
}
