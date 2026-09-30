<?php

namespace Drupal\flag\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\flag\Ajax\ActionLinkFlashCommand;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagService;
use Drupal\flag\Plugin\ActionLink\FormEntryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the confirm form page for flagging an entity.
 *
 * @see \Drupal\flag\Plugin\ActionLink\ConfirmForm
 */
class FlagConfirmForm extends FlagConfirmFormBase {

  public function __construct(
    FlagService $flag_service,
    protected readonly RendererInterface $renderer,
  ) {
    parent::__construct($flag_service);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flag'),
      $container->get('renderer'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flag_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $link_plugin = $this->flag->getLinkTypePlugin();
    return $link_plugin instanceof FormEntryInterface ? $link_plugin->getFlagQuestion() : $this->t('Flag this content');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->flag->getLongText('flag');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $link_plugin = $this->flag->getLinkTypePlugin();
    return $link_plugin instanceof FormEntryInterface ? $link_plugin->getCreateButtonText() : $this->t('Create flagging');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->flagService->flag($this->flag, $this->entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?FlagInterface $flag = NULL, $entity_id = NULL) {
    $form = parent::buildForm($form, $form_state, $flag, $entity_id);
    if ($this->getRequest()->isXmlHttpRequest()) {
      $form['actions']['submit']['#ajax'] = [
        'callback' => '::handleAjaxSubmit',
        'event' => 'click',
      ];
    }
    return $form;
  }

  /**
   * AJAX callback for the submit button.
   */
  public function handleAjaxSubmit(array &$form, FormState $form_state) {
    $response = new AjaxResponse();
    $flag = $this->flag;
    $entity = $this->entity;
    $view_mode = 'full';
    $message = $flag->getMessage('flag');
    $link_type = $flag->getLinkTypePlugin();
    $link = $link_type->getAsFlagLink($flag, $entity, $view_mode);
    $selector = '.js-flag-' . Html::cleanCssIdentifier($flag->id()) . '-' . $entity->id();
    $replace = new ReplaceCommand($selector, $this->renderer->renderInIsolation($link));
    $response->addCommand($replace);
    $pulse = new ActionLinkFlashCommand($selector, $message);
    $response->addCommand($pulse);
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
