<?php

namespace Drupal\views_exposed_filter_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Views;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a separate views exposed filter block.
 *
 * @Block(
 *   id = "views_exposed_filter_blocks_block",
 *   category = @Translation("Views Exposed Filter Blocks"),
 *   admin_label = @Translation("Views exposed filter block")
 * )
 */
class ViewsExposedFilterBlocksBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;


  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ViewsExposedFilterBlocksBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('logger.factory')->get('type'),
    );
  }

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
      '#title' => $this->t('View & Display'),
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
      [$view_id, $display_id] = explode(':', $view_display);
      if (empty($view_id) || empty($display_id)) {
        $form_state->setErrorByName('view_display', $this->t('View or display could not be determined correctly from the selected value.'));
      }
      else {
        // Check if the view exists:
        $view = Views::getView($view_id);
        if (empty($view)) {
          $form_state->setErrorByName('view_display', $this->t('View "%view_id" or its given display: "%display_id" doesn\'t exist. Please check the views exposed filter block configuration.', [
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
      [$view_id, $display_id] = explode(':', $view_display);
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
        $form = $this->formBuilder->buildForm('\Drupal\views\Form\ViewsExposedForm', $form_state);
        // Override form action URL in order to allow to place
        // the exposed form block on a different page as the view results.
        if ($view->display_handler->getOption('link_display') == 'custom_url' && !empty($view->display_handler->getOption('link_url'))) {
          $form['#action'] = $view->display_handler->getOption('link_url');
        }
        return $form;
      }
      else {
        $error = $this->t('View "%view_id" or its given display: "%display_id" doesn\'t exist. Please check the views exposed filter block configuration.', [
          '%view_id' => $view_id,
          '%display_id' => $display_id,
        ]);
        $this->logger->error($error);
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
