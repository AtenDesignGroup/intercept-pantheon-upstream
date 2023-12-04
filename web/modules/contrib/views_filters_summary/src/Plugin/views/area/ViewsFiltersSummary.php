<?php

declare(strict_types=1);

namespace Drupal\views_filters_summary\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\area\Result;
use Drupal\Component\Render\MarkupInterface;
use Drupal\views\Plugin\views\style\DefaultSummary;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views area handler to display some configurable result summary.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("views_filters_summary")
 */
class ViewsFiltersSummary extends Result {

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a ViewsFiltersSummary object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The bundle info service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TranslationInterface $translation_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->translationManager = $translation_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();

    $options['filters'] = ['default' => NULL];
    $options['group_values'] = ['default' => NULL];
    $options['show_labels'] = ['default' => FALSE];
    $options['show_remove_link'] = ['default' => TRUE];
    $options['show_reset_link'] = ['default' => FALSE];
    $options['filters_reset_link_title'] = ['default' => 'Reset'];
    $options['filters_summary_separator'] = ['default' => ', '];
    $options['filters_summary_prefix'] = ['default' => 'for '];
    $options['filters_result_label'] = [
      'default' => [
        'plural' => 'results',
        'singular' => 'result'
      ]
    ];
    $options['content'] = [
      'default' => $this->t('Displaying @total @result_label @exposed_filter_summary'),
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(
    &$form,
    FormStateInterface $form_state
  ): void {
    parent::buildOptionsForm($form, $form_state);

    $form['filters'] = [
      '#type' => 'select',
      '#title' => $this->t('Filters'),
      '#multiple' => TRUE,
      '#options' => $this->getFilterOptions(),
      '#default_value' => $this->options['filters'],
      '#description' => $this->t(
        'Choose the filters, if none are selected, all will be used.'
      ),
    ];
    $form['filters_summary_prefix'] = [
      '#title' => $this->t('Exposed Filters Summary Prefix'),
      '#type' => 'textfield',
      '#default_value' => $this->options['filters_summary_prefix'],
      '#description' => $this->t(
        'Shows a prefix along with the exposed filters summary.'
      ),
    ];
    $form['show_labels'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display labels'),
      '#default_value' => $this->options['show_labels'],
      '#description' => $this->t(
        'If checked, the labels will be displayed, otherwise just the value is
        printed.'
      ),
    ];
    $form['group_values'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Group multi-value filters'),
      '#default_value' => $this->options['group_values'],
      '#description' => $this->t(
        'If checked, multivalue filters will be grouped together under a single label.'
      ),
    ];
    $form['show_remove_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show remove filter link'),
      '#default_value' => $this->options['show_remove_link'],
      '#description' => $this->t(
        'If checked, a remove link for each filter will be shown.'
      ),
    ];
    $form['show_reset_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show reset filter link'),
      '#description' => $this->t('If checked, a rest filter link will be shown.'),
      '#default_value' => $this->options['show_reset_link'],
    ];
    $form['filters_reset_link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exposed Filter Reset Link Title'),
      '#description' => $this->t('Set the reset filter link title.'),
      '#default_value' => $this->options['filters_reset_link_title'],
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="options[show_reset_link]"]' => ['checked' => TRUE]
        ]
      ]
    ];
    $form['filters_summary_separator'] = [
      '#title' => $this->t('Exposed Filter Summary Separator'),
      '#type' => 'textfield',
      '#default_value' => $this->options['filters_summary_separator'],
      '#description' => $this->t(
        'How would you like your exposed filter summary items to appear?
        Example: A comma (, ) would produce Apple, Orange, Pear'
      ),
    ];
    $form['filters_result_label'] = [
      '#type' => 'details',
      '#title' => $this->t('Exposed Filter Results Label'),
      '#open' => TRUE,
    ];
    $form['filters_result_label']['singular'] = [
      '#title' => $this->t('Singular'),
      '#type' => 'textfield',
      '#default_value' => $this->options['filters_result_label']['singular'],
      '#description' => $this->t(
        'Enter the singular label for the type of result being shown.'
      ),
    ];
    $form['filters_result_label']['plural'] = [
      '#title' => $this->t('Plural'),
      '#type' => 'textfield',
      '#default_value' => $this->options['filters_result_label']['plural'],
      '#description' => $this->t(
        'Enter the plural label for the type of result being shown.'
      ),
    ];

    if (isset($form['content'])) {
      $item_list = [
        '#theme' => 'item_list',
        '#items' => [
          '@result_label -- the label of the type result being displayed',
          '@exposed_filter_summary -- the summary of selected exposed filters',
        ],
      ];
      $form['content']['#description'] .= $this->getRenderer()->render(
        $item_list
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE): array {
    if (
      !isset($this->options['content'])
      || $this->view->style_plugin instanceof DefaultSummary
    ) {
      return [];
    }

    try {
      $replacements = $this->buildReplacements();

      if (!empty($replacements['@total']) || !empty($this->options['empty'])) {
        return [
          '#markup' => str_replace(
            array_keys($replacements),
            array_values($replacements),
            $this->options['content']
          ),
        ];
      }
    } catch (\Exception $exception) {
      watchdog_exception('views_filter_summary', $exception);
    }

    return [];
  }

  /**
   * Build the filter summary.
   *
   * @return array
   *   An array of the filter summary.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildFilterSummary(): array {
    $summary = [];

    foreach ($this->getFilterDefinitions() as $definition) {
      if (!isset($definition['id'], $definition['value'])) {
        continue;
      }
      $id = $definition['id'];
      $value = $definition['value'];
      $label = $definition['label'];

      if (is_array($value)) {
        if (
          isset($this->options['group_values'])
          && $this->options['group_values']
        ) {
          $info = reset($value);

          if (!isset($info['id'], $info['value'])) {
            continue;
          }
          $summary[] = $this->buildFilterSummaryGroupedItem(
            $id, $label, $value
          );
        } else {
          foreach ($value as $info) {
            if (!isset($info['id'], $info['value'])) {
                continue;
            }
            $summary[] = $this->buildFilterSummaryItem(
              $id, $label, $info['value'], $info['raw']
            );
          }
        }
      } else {
        $summary[] = $this->buildFilterSummaryItem(
          $id, $label, $value
        );
      }
    }

    return $summary;
  }

  /**
   * Build the filter summary item.
   *
   * @param string $id
   *   The filter item identifier.
   * @param string $label
   *   The filter item label.
   * @param string $value
   *   The filter item value.
   * @param string|null $value_raw
   *   The filter item value raw.
   *
   * @return array
   *   A structured filter summary item array.
   */
  protected function buildFilterSummaryItem(
    string $id,
    string $label,
    string $value,
    ?string $value_raw = NULL
  ): array {
    $input = $value_raw ?? $value;
    return [
      'id' => $id,
      'label' => $label,
      'value' => $value,
      'link' => [
        '#type' => 'link',
        '#title' => $this->t('X'),
        '#url' => Url::fromUserInput('/'),
        '#attributes' => [
          'class' => ['remove-filter'],
          'data-remove-selector' => "{$id}:{$input}",
          'aria-label' => "clear {$value}"
        ]
      ],
    ];
  }

  /**
   * Build the filter summary grouped item.
   *
   * @param string $id
   *   The filter item identifier.
   * @param string $label
   *   The filter item label.
   * @param array $values
   *   The filter item values.
   *
   * @return array
   *   A structured filter summary item array.
   */
  protected function buildFilterSummaryGroupedItem(
    string $id,
    string $label,
    array $values
  ): array {
    $item = [
      'id' => $id,
      'label' => $label,
      'groups' => [],
    ];
    foreach ($values as $value) {
      $item['groups'][] = $this->buildFilterSummaryItem(
        $id, $label, $value['value'], $value['raw']
      );
    }
    return $item;
  }

  /**
   * Define the filter format replacements.
   *
   * @return array
   *   An array of replacement variables.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  protected function defineReplacements(): array {
    $view = $this->view;

    $replacements = [
      'label' => $this->view->storage->label(),
      'per_page' => (int) $view->getItemsPerPage(),
      'page_count' => 1,
      'current_page' => (int) $view->getCurrentPage() + 1,
      'total' => $view->total_rows ?? count($view->result),
    ];
    $replacements['start_offset'] = empty($replacements['total']) ? 0 : 1;

    $replacements['end'] = $replacements['total'];
    $replacements['start'] = $replacements['start_offset'];

    if ($replacements['per_page'] !== 0) {
      $replacements['page_count'] = (int) ceil($replacements['total'] / $replacements['per_page']);
      $total_count = $replacements['current_page'] * $replacements['per_page'];

      if ($total_count > $replacements['total']) {
        $total_count = $replacements['total'];
      }
      $replacements['end'] = $total_count;
      $replacements['start'] = ($replacements['current_page'] - 1) * $replacements['per_page'] + $replacements['start_offset'];
    }
    $replacements['current_record_count'] = ($replacements['end'] - $replacements['start']) + $replacements['start_offset'];

    $replacements['result_label'] = $this->translationManager->formatPlural(
      $replacements['total'],
      $this->options['filters_result_label']['singular'],
      $this->options['filters_result_label']['plural']
    );
    $replacements['exposed_filter_summary'] = $this->buildFilterSummaryMarkup();

    return $replacements;
  }

  /**
   * Build the filter summary markup.
   *
   * @return \Drupal\Component\Render\MarkupInterface|null
   *   The filter summary markup.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildFilterSummaryMarkup(): ?MarkupInterface {
    if ($summary = $this->buildFilterSummary()) {
      $element = [
        '#theme' => 'views_filters_summary',
        '#summary' => $summary,
        '#options' => [
          'use_ajax' => $this->view->ajaxEnabled(),
          'show_label' => $this->options['show_labels'],
          'show_remove_link' => $this->options['show_remove_link'],
          'show_reset_link' => $this->options['show_reset_link'],
          'has_group_values' => $this->options['group_values'],
          'reset_link' => [
            'title' => $this->options['filters_reset_link_title']
          ],
          'filters_summary' => [
            'prefix' => $this->options['filters_summary_prefix'],
            'separator' => $this->options['filters_summary_separator']
          ],
        ],
        '#attached' => [
          'library' => ['views_filters_summary/views_filters_summary']
        ]
      ];
      return $this->getRenderer()->render(
        $element
      );
    }

    return NULL;
  }

  /**
   * Build the filter format replacements.
   *
   * @return array
   *   An array of formatted replacements.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildReplacements(): array {
    $variables = [];

    foreach ($this->defineReplacements() as $key => $value) {
      $variables["@{$key}"] = $value;
    }

    return $variables;
  }

  /**
   * Get the filter definitions.
   *
   * @return array
   *   An array of filter definitions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFilterDefinitions(): array  {
    $definitions = [];

    foreach ($this->view->filter as $filter) {
      if (empty($filter->value) || !$filter->isExposed()) {
        continue;
      }
      $definitions[] = $this->buildFilterDefinition(
        $filter
      );
    }

    return $definitions;
  }

  /**
   * Build the filter definition.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The view filter plugin instance.
   *
   * @return array
   *   An array of definition properties.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildFilterDefinition(FilterPluginBase $filter): array {
    $info = [
      'id' => $filter->exposedInfo()['value'] ?? NULL,
      'label' => $this->getFilterLabel($filter),
      'value' => $filter->value
    ];

    if (is_array($info['value'])) {
      switch ($filter->getPluginId()) {
        case 'search_api_term':
        case 'taxonomy_index_tid':
        case 'taxonomy_index_tid_depth':
          $values = [];
          $storage = $this->entityTypeManager->getStorage('taxonomy_term');
          foreach ($filter->value as $index => $term) {
            if ($term = $storage->load($term)) {
              $values[] = [
                'id' => $index,
                'raw' => $term->id(),
                'value' => $term->label(),
              ];
            }
          }
          $info['value'] = $values;
          break;
        case 'bundle':
          if ($entity_type = $filter->getEntityType()) {
            $values = [];
            $types = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
            foreach ($info['value'] as $index => $value) {
              if (!isset($types[$value])) {
                continue;
              }
              $values[] = [
                'id' => $index,
                'raw' => $value,
                'value' => $types[$value]['label'],
              ];
            }
            $info['value'] = $values;
          }
          break;
        case 'numeric':
          $filter_options = $filter->options['group_info']['group_items'];
          $filter_id = reset($filter->value);
          foreach ($filter_options as $value) {
            if ($filter_id == $value['value']['value']) {
              $info['value'] = $value['title'];
            }
          }
          break;
      }
    }

    // Invoke hook_views_filters_summary_info_alter().
    $this->moduleHandler->alter('views_filters_summary_info', $info, $filter);

    return $info;
  }

  /**
   * Get the views filter label.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The views filter instance.
   *
   * @return string|null
   *   The views filter label.
   */
  protected function getFilterLabel(
    FilterPluginBase $filter
  ): ?string {
    return $filter->options['expose']['label']
      ?? $filter->definition['title short']
      ?? $filter->definition['title']
      ?? NULL;
  }

  /**
   * Get the views filter options.
   *
   * @return array
   *   An array of the filter options.
   */
  protected function getFilterOptions(): array {
    $options = array();

    $this->view->initHandlers();
    foreach ($this->view->filter as $id => $handler) {
      if ($handler->isExposed()) {
        $options[$id] = $handler->adminLabel();
      }
    }

    return $options;
  }

}
