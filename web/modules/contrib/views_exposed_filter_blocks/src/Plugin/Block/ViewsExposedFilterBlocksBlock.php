<?php

namespace Drupal\views_exposed_filter_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Provides a separate views exposed filter block.
 *
 * @Block(
 *   id = "views_exposed_filter_blocks_block",
 *   admin_label = @Translation("Views exposed filter block")
 * )
 */
class ViewsExposedFilterBlocksBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_display' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['view_display'] = [
      '#type' => 'select',
      '#options' => Views::getViewsAsOptions(FALSE, 'enabled'),
      '#title' => $this->t('View') . ' & ' . $this->t('Display'),
      '#description' => nl2br($this->t("Select the view and its display with the exposed filters to show in this block.\nYou should disable AJAX on the selected view and ensure the view and the filter are on the same page.\nFor view displays of type 'page' better use the view built-in functionality for exposed filters in blocks.")),
      '#default_value' => $this->configuration['view_display'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['view_display'] = $form_state->getValue('view_display');
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $view_display = $form_state->getValue('view_display');
    if (!empty($view_display)) {
      // Check if the selected value is OK:
      list($view_id, $display_id) = explode(':', $view_display);
      if (empty($view_id) || empty($display_id)) {
        $form_state->setErrorByName('view_display', t('View or display coult not be determined correctly from the selected value.'));
      }
      else {
        // Check if the view exists:
        $view = Views::getView($view_id);
        if (empty($view)) {
          $form_state->setErrorByName('view_display', t('View "%view_id" or its given display: "%display_id" doesn\'t exist. Please check the views exposed filter block configuration.', [
            '%view_id' => $view_id,
            '%display_id' => $display_id,
          ]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $view_display = $this->configuration['view_display'];
    if (!empty($view_display)) {
      list($view_id, $display_id) = explode(':', $view_display);
      if (empty($view_id) || empty($display_id)) {
        return;
      }
      $view = Views::getView($view_id);
      if (!empty($view)) {
        $view->setDisplay($display_id);
        $view->initHandlers();
        $form_state = (new FormState())
          ->setStorage([
            'view' => $view,
            'display' => &$view->display_handler->display,
            'rerender' => TRUE,
          ])
          ->setMethod('get')
          ->setAlwaysProcess()
          ->disableRedirect();
        $form_state->set('rerender', NULL);
        $form = \Drupal::formBuilder()
          ->buildForm('\Drupal\views\Form\ViewsExposedForm', $form_state);
        return $form;
      }
      else {
        $error = $this->t('View "%view_id" or its given display: "%display_id" doesn\'t exist. Please check the views exposed filter block configuration.', [
          '%view_id' => $view_id,
          '%display_id' => $display_id,
        ]);
        \Drupal::logger('type')->error($error);
        return [
          '#type' => 'inline_template',
          '#template' => '{{ error }}',
          '#context' => [
            'error' => $error,
          ],
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Prevent the block from cached else the selected options will be cached.
    return 0;
  }

}
