<?php

namespace Drupal\webformautosave;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_submission_log\WebformSubmissionLogManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A helper class that houses helper functions for the webformautosave module.
 *
 * @package Drupal\webformautosave
 */
class AutosaveHelper {

  /**
   * The webform submission logger.
   *
   * @var \Drupal\webform_submission_log\WebformSubmissionLogManager
   *   The webform_submission log manager
   */
  protected $webformSubmissionLogManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * AutosaveHelper constructor.
   *
   * @param \Drupal\webform_submission_log\WebformSubmissionLogManager $webform_submission_log_manager
   *   The webform_submission log manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(WebformSubmissionLogManager $webform_submission_log_manager, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack) {
    $this->webformSubmissionLogManager = $webform_submission_log_manager;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * Getter for the most recent submission url.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform_submission.
   *
   * @return \Drupal\Core\Url
   *   The url for the most recent submission.
   */
  public function getSubmissionUrl(WebformSubmissionInterface $webform_submission) {
    $submission_url = $webform_submission->getTokenUrl();
    $submission_url->setAbsolute(FALSE);
    $current_params = $this->requestStack->getCurrentRequest()->query->all();
    // Add the current params to the submission url.
    foreach ($current_params as $key => $param) {
      $submission_url->setRouteParameter($key, $param);
    }
    return $submission_url;
  }

  /**
   * Getter for the most recent submission log.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform_submission.
   *
   * @return object
   *   The most recent logged submission.
   */
  public function getCurrentSubmissionLog(WebformSubmissionInterface $webform_submission) {
    // Get the submission logs.
    $submission_log_query = $this->webformSubmissionLogManager->getQuery($webform_submission);
    $submission_log_query->orderBy('timestamp', 'DESC');
    $submission_log_query->range(0, 1);
    $submission_log = $submission_log_query->execute()->fetchObject();

    // Clean and return the record if available.
    if (!empty($submission_log)) {
      $submission_log->variables = unserialize($submission_log->variables, ['allowed_classes' => FALSE]);
      $submission_log->data = unserialize($submission_log->data, ['allowed_classes' => FALSE]);
      return $submission_log;
    }

    // Return a vanilla log with the current timestamp and UID if we get here.
    $now = new DrupalDateTime();
    $log_data = [
      'webform_id' => $webform_submission->getWebform()->id(),
      'sid' => $webform_submission->id(),
      'uid' => $this->currentUser->id(),
      'message' => 'initial log by webform_autosave',
      'timestamp' => $now->getTimestamp(),
    ];
    $this->webformSubmissionLogManager->insert($log_data);
    return (object) $log_data;
  }

  /**
   * Getter for all the fields on the current page.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform_submission.
   *
   * @return array|bool
   *   All the elements on the current page or FALSE if we don't find any.
   */
  public function getCurrentFields(WebformSubmissionInterface $webform_submission) {
    $webform = $webform_submission->getWebform();
    $elements = $webform->getElementsInitializedAndFlattened();
    $is_wizzard = $webform->hasWizardPages();
    if ($is_wizzard && is_array($elements)) {
      $current_page = $webform_submission->getCurrentPage();
      return $elements[$current_page]['#webform_children'];
    }
    elseif (is_array($elements)) {
      return $elements;
    }
    return FALSE;
  }

  /**
   * Getter for the first field on the current page.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform_submission.
   *
   * @return string|bool
   *   Returns the first field on the current page or FALSE if it is empty.
   */
  public function getFirstWebformField(WebformSubmissionInterface $webform_submission) {
    $elements = $this->getCurrentFields($webform_submission);
    if (!empty($elements) && is_array($elements)) {
      return key($elements);
    }
    return FALSE;
  }

  /**
   * Checks to see if we should prevent autosave.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission in question.
   *
   * @return bool
   *   True if autosave should be enabled.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function enableAutosave(WebformInterface $webform, WebformSubmissionInterface $webform_submission) {
    // Return false if autosave is not enabled.
    if (empty($webform->getThirdPartySetting('webformautosave', 'auto_save'))) {
      return FALSE;
    }

    // Return FALSE if the user has hit their limit of submissions.
    if ($this->checkTotalLimit($webform, $webform_submission)) {
      return FALSE;
    }

    // Return FALSE if the user has reached their entity limit.
    if ($this->checkUserLimit($webform_submission)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check webform submission total limits.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   *
   * @return bool
   *   TRUE if webform submission total limit have been met.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function checkTotalLimit(WebformInterface $webform, WebformSubmissionInterface $webform_submission) {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage */
    $webform_submission_storage = $this->entityTypeManager->getStorage('webform_submission');
    // Get limit total to unique submission per webform/source entity.
    $limit_total_unique = $webform->getSetting('limit_total_unique');

    // Check per source entity total limit.
    $entity_limit_total = $webform->getSetting('entity_limit_total');
    $entity_limit_total_interval = $webform->getSetting('entity_limit_total_interval');
    if ($limit_total_unique) {
      $entity_limit_total = 1;
      $entity_limit_total_interval = NULL;
    }
    if ($entity_limit_total && ($source_entity = $this->getLimitSourceEntity($webform_submission))) {
      if ($webform_submission_storage->getTotal($webform, $source_entity, NULL, ['interval' => $entity_limit_total_interval]) >= $entity_limit_total) {
        return TRUE;
      }
    }

    // Check total limit.
    $limit_total = $webform->getSetting('limit_total');
    $limit_total_interval = $webform->getSetting('limit_total_interval');
    if ($limit_total_unique) {
      $limit_total = 1;
      $limit_total_interval = NULL;
    }
    if ($limit_total && $webform_submission_storage->getTotal($webform, NULL, NULL, ['interval' => $limit_total_interval]) >= $limit_total) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Check webform submission user limit.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   *
   * @return bool
   *   TRUE if webform submission user limit have been met.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function checkUserLimit(WebformSubmissionInterface $webform_submission) {
    // Allow anonymous and authenticated users edit own submission.
    if ($webform_submission->id() && $webform_submission->isOwner($this->currentUser)) {
      return FALSE;
    }

    /** @var \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage */
    $webform_submission_storage = $this->entityTypeManager->getStorage('webform_submission');

    // Get the submission owner and not current user.
    // This takes into account when an API submission changes the owner id.
    // @see \Drupal\webform\WebformSubmissionForm::submitFormValues
    $account = $webform_submission->getOwner();
    $webform = $webform_submission->getWebform();

    // Check per source entity user limit.
    $entity_limit_user = $webform->getSetting('entity_limit_user');
    $entity_limit_user_interval = $webform->getSetting('entity_limit_user_interval');
    if ($entity_limit_user && ($source_entity = $this->getLimitSourceEntity($webform_submission))) {
      if ($webform_submission_storage->getTotal($webform, $source_entity, $account, ['interval' => $entity_limit_user_interval]) >= $entity_limit_user) {
        return TRUE;
      }
    }

    // Check user limit.
    $limit_user = $webform->getSetting('limit_user');
    $limit_user_interval = $webform->getSetting('limit_user_interval');
    if ($limit_user && $webform_submission_storage->getTotal($webform, NULL, $account, ['interval' => $limit_user_interval]) >= $limit_user) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get source entity for use with entity limit total and user submissions.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The webform submission's source entity.
   */
  protected function getLimitSourceEntity(WebformSubmissionInterface $webform_submission) {
    $source_entity = $webform_submission->getSourceEntity();
    if ($source_entity && $source_entity->getEntityTypeId() !== 'webform') {
      return $source_entity;
    }
    return NULL;
  }

}
