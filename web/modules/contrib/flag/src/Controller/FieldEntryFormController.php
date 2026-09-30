<?php

namespace Drupal\flag\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\flag\FlaggingInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a controller for the Field Entry link type.
 */
class FieldEntryFormController extends ControllerBase {

  public function __construct(
    protected FlagServiceInterface $flagService,
  ) {
  }

  /**
   * Performs a flagging when called via a route.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The flaggable ID.
   *
   * @return array
   *   The processed form to create the flagging.
   *
   * @see \Drupal\flag\Plugin\ActionLink\AJAXactionLink
   */
  public function flag(FlagInterface $flag, $entity_id) {
    // Set account and session ID to NULL to get the current user.
    $account = $session_id = NULL;
    $this->flagService->populateFlaggerDefaults($account, $session_id);

    $this->flagService->ensureSession();
    $entity = $this->flagService->getFlaggableById($flag, $entity_id);
    $flagging = $this->flagService->getFlagging($flag, $entity, NULL, NULL);

    if ($flagging) {
      return $this->getForm($flagging, 'add');
    }

    $flagging = $this->entityTypeManager()->getStorage('flagging')->create([
      'flag_id' => $flag->id(),
      'entity_type' => $flag->getFlaggableEntityTypeId(),
      'entity_id' => $entity_id,
      'uid' => $account->id(),
      'session_id' => $session_id,
      'global' => $flag->isGlobal(),
    ]);

    return $this->getForm($flagging, 'add');
  }

  /**
   * Return the flagging edit form.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param mixed $entity_id
   *   The entity ID.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown if the flagging could not be found.
   *
   * @return array
   *   The processed edit form for the given flagging.
   */
  public function edit(FlagInterface $flag, $entity_id) {
    $entity = $this->flagService->getFlaggableById($flag, $entity_id);

    // If we couldn't find the flaggable, throw a 404.
    if (!$entity) {
      throw new NotFoundHttpException('The flagged entity could not be found.');
    }

    // Load the flagging from the flag and flaggable.
    $flagging = $this->flagService->getFlagging($flag, $entity);

    // If we couldn't find the flagging, we can't edit. Throw a 404.
    if (!$flagging) {
      throw new NotFoundHttpException('The flagged entity could not be found.');
    }

    return $this->getForm($flagging, 'edit');
  }

  /**
   * Performs an unflagging when called via a route.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The entity ID to unflag.
   *
   * @return array
   *   The processed delete form for the given flagging.
   *
   * @see \Drupal\flag\Plugin\ActionLink\AJAXactionLink
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown if the flagging could not be found.
   */
  public function unflag(FlagInterface $flag, $entity_id) {
    $entity = $this->flagService->getFlaggableById($flag, $entity_id);

    // If we can't find the flaggable entity, throw a 404.
    if (!$entity) {
      throw new NotFoundHttpException('The flagging could not be found.');
    }

    // Load the flagging. If we can't find it, we can't unflag and throw a 404.
    $flagging = $this->flagService->getFlagging($flag, $entity);
    if (!$flagging) {
      throw new NotFoundHttpException('The flagging could not be found.');
    }

    return $this->getForm($flagging, 'delete');
  }

  /**
   * Title callback when creating a new flagging.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The entity ID to unflag.
   *
   * @return string
   *   The flag field entry form title.
   */
  public function flagTitle(FlagInterface $flag, $entity_id) {
    /** @var \Drupal\flag\Plugin\ActionLink\FormEntryTypeBase $link_type */
    $link_type = $flag->getLinkTypePlugin();
    return $link_type->getFlagQuestion();
  }

  /**
   * Title callback when editing an existing flagging.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The entity ID to unflag.
   *
   * @return string
   *   The flag field entry form title.
   */
  public function editTitle(FlagInterface $flag, $entity_id) {
    /** @var \Drupal\flag\Plugin\ActionLink\FormEntryTypeBase $link_type */
    $link_type = $flag->getLinkTypePlugin();
    return $link_type->getEditFlaggingTitle();
  }

  /**
   * Get the flag's field entry form.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flagging from which to get the form.
   * @param string|null $operation
   *   (optional) The operation identifying the form variant to return.
   *   If no operation is specified then 'default' is used.
   *
   * @return array
   *   The processed form for the given flagging and operation.
   */
  protected function getForm(FlaggingInterface $flagging, $operation = 'default') {
    return $this->entityFormBuilder()->getForm($flagging, $operation);
  }

}
