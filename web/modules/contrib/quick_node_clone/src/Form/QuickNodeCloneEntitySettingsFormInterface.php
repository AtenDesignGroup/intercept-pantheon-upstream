<?php

namespace Drupal\quick_node_clone\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for an Clone Entity forms.
 */
interface QuickNodeCloneEntitySettingsFormInterface {
  /**
   * Sets the entity type the settings form is for.
   *
   * @param $entityTypeId
   *    The entity type Id i.e. article
   *
   * @return mixed
   */
  public function setEntityType($entityTypeId);

  /**
   * Returns the entity type Id. i.e. article
   *
   * @return mixed
   */
  public function getEntityTypeId();

  /**
   * The array of config names.
   *
   * @return mixed
   */
  public function getEditableConfigNames();

  /**
   * Returns the bundles for the entity.
   *
   * @return mixed
   */
  public function getEntityBundles();

  /**
   * Returns the selected bundles on the form.
   *
   * @param $form_state
   *
   * @return mixed
   */
  public function getSelectedBundles(FormStateInterface $form_state);

  /**
   * Returns the description field.
   *
   * @param $form_state
   *
   * @return mixed
   */
  public function getDescription(FormStateInterface $form_state);

  /**
   * Returns the default fields.
   *
   * @param $value
   *
   * @return mixed
   */
  public function getDefaultFields($value);

  /**
   * Return the configuration.
   *
   * @param $value
   *
   * @return mixed
   */
  public function getSettings($value);
}
