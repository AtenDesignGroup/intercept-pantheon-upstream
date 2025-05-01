<?php

namespace Drupal\office_hours\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Ajax controller for updating the office hours status.
 */
class StatusUpdateController implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Updates office hours status by re-rendering the whole field.
   *
   * @param string $entity_type
   *   The entity type of the office hours field.
   * @param string $entity_id
   *   The entity id.
   * @param string $field_name
   *   The office hours field in question.
   * @param string $langcode
   *   The current langcode for the entity.
   * @param string $view_mode
   *   The view mode for rendering.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response as markup.
   *
   * @see #[Route(
   *   '/office_hours/status_update/{entity_type}/{entity_id}/{field_name}/{langcode}/{view_mode}',
   *   name: 'office_hours.status_update')]
   */
  public function updateStatus(string $entity_type, string $entity_id, string $field_name, string $langcode, string $view_mode) {

    try {
      $storage = $this->entityTypeManager->getStorage($entity_type);
    }
    catch (\Exception $e) {
      throw new BadRequestHttpException();
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->load($entity_id);
    if (!$entity) {
      throw new NotFoundHttpException();
    }

    if ($entity->isTranslatable() && $entity->hasTranslation($langcode)) {
      $entity = $entity->getTranslation($langcode);
    }

    if (!$entity->access('view')) {
      throw new AccessDeniedHttpException();
    }

    if (!$entity->hasField($field_name)) {
      throw new NotFoundHttpException();
    }

    $items = $entity->get($field_name);
    if (!$items instanceof OfficeHoursItemListInterface) {
      throw new AccessDeniedHttpException();
    }

    $renderable = $items->view($view_mode);
    /*
    @see https://www.drupal.org/project/office_hours/issues/3397009
    Here, we tried in vain to use the layout_builder third_party_settings.
    This is not possible, as per core\modules\layout_builder\src\Entity\LayoutBuilderEntityViewDisplay.php::buildMultiple().
    "Layout Builder can not be enabled for the '_custom' view mode that is
    "used for on-the-fly rendering of fields in isolation from the entity.

    @see also https://www.drupal.org/project/drupal/issues/3023220 :
    "Performance: Prevent extra Layout Builder code from running
    "when rendering fields in isolation (Views results, FieldBlock, etc)"

    $entity_bundle = $entity->bundle();
    $entity_display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
    $display_settings = $entity_display->getComponent($field_name);
    $display_settings['view_mode'] = $view_mode;
    $renderable = $items->view($display_settings);
     */

    $response = new Response();
    $response->setContent($this->renderer->render($renderable));

    return $response;
  }

  /**
   * Attaches JSON-encoded attributes for StatusUpdateJS file.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   The office_hours items.
   * @param string $langcode
   *   The preferred language.
   * @param string $view_mode
   *   The current view mode.
   * @param array $third_party_settings
   *   Extra settings, e.g., layout_builder.
   * @param array $elements
   *   The render array.
   *
   * @return array
   *   Updated render array.
   */
  public static function attachStatusUpdate(OfficeHoursItemListInterface $items, $langcode, $view_mode, array $third_party_settings, array $elements) {
    // Note: when changing this, also test the Views StatusFilter.
    if (!\Drupal::currentUser()->isAnonymous()) {
      // Field cache should work properly for non-anonymous users.
      // @see https://www.drupal.org/project/office_hours/issues/3466589
      return $elements;
    }

    if (!\Drupal::moduleHandler()->moduleExists('page_cache')) {
      // This is to fix the page_cache module.
      // @see https://www.drupal.org/project/office_hours/issues/3466589
      return $elements;
    }

    if ($third_party_settings['layout_builder']['view_mode'] ?? FALSE) {
      // layout_builder module cannot display fields in isolation.
      // @see https://www.drupal.org/project/office_hours/issues/3397009
      return $elements;
    }

    $parent_entity = $items->getParent()->getEntity();
    $field_definition = $items->getFieldDefinition();
    $status_metadata = [
      'entity_type' => $parent_entity->getEntityTypeId(),
      'entity_id' => $parent_entity->id(),
      'field_name' => $field_definition->getName(),
      'langcode' => $langcode,
      'view_mode' => $view_mode,
      'request_time' => \Drupal::time()->getRequestTime(),
    ];

    // Enable dynamic field update in office_hours_status_update.js.
    $elements['#attached'] = [
      'library' => [
        'office_hours/office_hours_formatter_status_update',
      ],
    ];

    $elements['#attributes']['js-office-hours-status-data'] = json_encode($status_metadata);

    return $elements;
  }

}
