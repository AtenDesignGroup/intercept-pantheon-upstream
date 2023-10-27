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
   * @param string $field
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

    $fieldItemList = $entity->get($field_name);
    if (!$fieldItemList instanceof OfficeHoursItemListInterface) {
      throw new AccessDeniedHttpException();
    }

    $renderable = $fieldItemList->view($view_mode);

    $response = new Response();
    $response->setContent($this->renderer->render($renderable));

    return $response;
  }

  public static function attachStatusUpdateJS(OfficeHoursItemListInterface $items, $langcode, $view_mode, array $elements)
  {
    $parent_entity = $items->getParent()->getEntity();
    $field_definition = $items->getFieldDefinition();
    $status_metadata = [
      'entity_type' => $parent_entity->getEntityTypeId(),
      'entity_id' => $parent_entity->id(),
      'field_name' => $field_definition->getName(),
      'langcode' => $langcode,
      'view_mode' => $view_mode,
    ];

    // Enable dynamic field update in office_hours_status_update.js.
    $elements['#attached'] = [
      'library' => [
        'office_hours/office_hours_formatter_status_update',
      ],
    ];

    $elements['#attributes']['data-drupal-office-hours-status'] = json_encode($status_metadata);

    return $elements;
  }
}
