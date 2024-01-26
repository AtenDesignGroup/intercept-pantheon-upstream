<?php

namespace Drupal\fullcalendar_block\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a FullCalendar Block.
 *
 * @Block(
 *   id = "fullcalendar_block",
 *   admin_label = @Translation("FullCalendar block"),
 *   category = @Translation("Calendar"),
 * )
 */
class FullCalendarBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use AjaxHelperTrait;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The Request Stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->time = $container->get('datetime.time');
    $instance->configFactory = $container->get('config.factory');
    $instance->languageManager = $container->get('language_manager');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->token = $container->get('token');
    $instance->requestStack = $container->get('request_stack');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'event_source' => '',
      'use_token' => FALSE,
      'initial_view' => 'dayGridMonth',
      'header_start' => 'prev,next today',
      'header_center' => 'title',
      'header_end' => 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
      'open_dialog' => 1,
      'dialog_width' => 800,
      'advanced' => '',
      'advanced_drupal' => '',
      'plugins' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_index = $this->generateBlockIndex();

    $config = $this->getConfiguration();
    $event_url = $this->resolveEventUrl($config['event_source']);
    $initial_view = $config['initial_view'];
    $header_start = $config['header_start'];
    $header_center = $config['header_center'];
    $header_end = $config['header_end'];
    $dialog_open = $config['open_dialog'];
    $dialog_width = $config['dialog_width'];
    $advanced_settings = $config['advanced'];

    // Fullcalendar options.
    $calendar_options = [
      'initialView' => $initial_view,
      'events' => $event_url,
      'headerToolbar' => [
        'start' => $header_start,
        'center' => $header_center,
        'end' => $header_end,
      ],
      // Pick up the default localization settings from Drupal.
      // https://fullcalendar.io/docs/localization
      'firstDay' => $this->configFactory->get('system.date')->get('first_day') ?? 0,
      'direction' => $this->languageManager->getCurrentLanguage()->getDirection(),
      'locale' => $this->languageManager->getCurrentLanguage()->getId(),
    ];

    if (!empty($advanced_settings)) {
      $calendar_options = array_merge($calendar_options, (array) $this->decodeAdvancedSettings($advanced_settings));
    }

    $block_settings = [
      'calendar_options' => $calendar_options,
      'dialog_open' => $dialog_open,
      'dialog_width' => $dialog_width,
      // Advanced Drupal settings to control the dialog behaviours amongst
      // other things. Ideally all these would be individual configs, but this
      // is the most flexible.
      'advanced' => $this->decodeAdvancedSettings($config['advanced_drupal']),
    ];

    $block_content = [
      '#theme' => 'fullcalendar_block',
      '#block_index' => $block_index,
    ];

    // Allow other modules to alter the block settings.
    $this->moduleHandler->alter('fullcalendar_block_settings', $block_settings, $block_content, $this);

    // The block settings.
    $block_index = $block_content['#block_index'];
    $block_content['#attached']['drupalSettings']['fullCalendarBlock'][$block_index] = $block_settings;

    // Attach the libraries.
    if (!empty($block_settings['advanced']['draggable']) && $this->moduleHandler->moduleExists('jquery_ui_draggable')) {
      $block_content['#attached']['library'][] = 'jquery_ui_draggable/draggable';
    }
    if (!empty($block_settings['advanced']['resizable']) && $this->moduleHandler->moduleExists('jquery_ui_resizable')) {
      $block_content['#attached']['library'][] = 'jquery_ui_resizable/resizable';
    }
    if (!empty($block_settings['advanced']['description_popup'])) {
      // Advanced popup is supported, add the DOMPurify library to sanitize
      // with.
      $block_content['#attached']['library'][] = 'fullcalendar_block/libraries.dompurify';
    }
    // Add moment.js support.
    if (in_array('moment', $this->configuration['plugins'], TRUE)) {
      $block_content['#attached']['library'][] = 'fullcalendar_block/libraries.fullcalendar_moment';
    }
    // Add rrule support.
    if (in_array('rrule', $this->configuration['plugins'], TRUE)) {
      $block_content['#attached']['library'][] = 'fullcalendar_block/libraries.fullcalendar_rrule';
    }
    // Add the fullcalendar library.
    $block_content['#attached']['library'][] = 'fullcalendar_block/fullcalendar';

    return $block_content;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['event_source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event source URL'),
      '#description' => $this->t('The URL where the calendar events data feeds'),
      '#default_value' => $config['event_source'],
      '#required' => TRUE,
    ];

    $form['use_token'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable tokens'),
      '#description' => $this->t('Enable the use of tokens for the event source URL'),
      '#default_value' => $config['use_token'],
    ];
    // Token support.
    if ($this->moduleHandler->moduleExists('token')) {
      $form['tokens'] = [
        '#title' => $this->t('Tokens (for the event source URL only)'),
        '#type' => 'container',
        '#states' => [
          'invisible' => [
            'input[name="settings[use_token]"]' => ['checked' => FALSE],
          ],
        ],
      ];
      $form['tokens']['help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => 'all',
        '#global_types' => TRUE,
        '#dialog' => TRUE,
      ];
    }

    $form['initial_view'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Initial View'),
      '#description' => $this->t('The initial view of the calendar'),
      '#default_value' => $config['initial_view'],
    ];

    // Header toolbar settings.
    $form['header_toolbar'] = [
      '#type' => 'details',
      '#title' => $this->t('Header Toolbar'),
      '#description' => $this->t('Header toolbar of the calendar. <br/>See <a href=":url" target="_blank" rel="noopener">the help document</a> for available options.', [
        ':url' => 'https://fullcalendar.io/docs/headerToolbar',
      ]),
    ];

    $form['header_toolbar']['header_start'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start of the header toolbar'),
      '#description' => $this->t('Start area will normally be on the left. if RTL, will be on the right'),
      '#default_value' => $config['header_start'],
    ];

    $form['header_toolbar']['header_center'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Center of the header toolbar'),
      '#description' => $this->t('The default value is title if leave this empty'),
      '#default_value' => $config['header_center'],
    ];

    $form['header_toolbar']['header_end'] = [
      '#type' => 'textfield',
      '#title' => $this->t('End of the header toolbar'),
      '#description' => $this->t('The default value is Week and Day view if leave this empty'),
      '#default_value' => $config['header_end'],
    ];

    // Click event settings.
    $form['click_event'] = [
      '#type' => 'details',
      '#title' => $this->t('Click event settings'),
    ];

    $form['click_event']['open_dialog'] = [
      '#type' => 'radios',
      '#title' => $this->t('Click on an event'),
      '#options' => [
        0 => $this->t('Open in a new tab'),
        1 => $this->t('Open in a dialog'),
        2 => $this->t('Open in current tab'),
      ],
      '#default_value' => $config['open_dialog'],
      '#attributes' => [
        // Define static data condition attribute so we can easier select it.
        'data-condition' => 'field-open-dialog',
      ],
    ];

    $form['click_event']['dialog_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Dialog width'),
      '#default_value' => $config['dialog_width'],
      '#size' => '5',
      '#min' => 0,
      '#states' => [
        // Show this textfield only if 'open in a dialog' is selected above.
        'visible' => [
          ':input[data-condition="field-open-dialog"]' => ['value' => 1],
        ],
      ],
    ];

    // Advanced settings.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => !empty($config['advanced']) || !empty($config['advanced_drupal']) || !empty($config['plugins']),
    ];

    $form['advanced']['plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Fullcalendar plugins'),
      '#description' => $this->t('<a href=":url" target="_blank" rel="noopener">Fullcalendar plugins</a> to enable integration for.', [
        ':url' => 'https://fullcalendar.io/docs/plugin-index',
      ]),
      '#default_value' => $config['plugins'],
      '#options' => [
        // Add support for moment, to help with unsupported locales.
        // @see https://github.com/fullcalendar/fullcalendar/issues/5565
        'moment' => $this->t('Moment <small>[<a href=":url" target="_blank" rel="noopener">docs</a>]</small>', [
          ':url' => 'https://fullcalendar.io/docs/moment-plugin',
        ]),
        'rrule' => $this->t('RRule <small>[<a href=":url" target="_blank" rel="noopener">docs</a>]</small>', [
          ':url' => 'https://fullcalendar.io/docs/rrule-plugin',
        ]),
      ],
    ];

    $form['advanced']['addition'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Advanced settings'),
      '#default_value' => $config['advanced'],
      '#description' => $this->t('It must be in valid JSON/YAML format.<br/>See <a href=":url" target="_blank" rel="noopener">Fullcalendar documentations</a> for available options.', [
        ':url' => 'https://fullcalendar.io/docs#toc',
      ]),
      '#element_validate' => [[$this, 'validateAdvancedSettings']],
      '#attributes' => [
        'spellcheck' => 'false',
      ],
    ];

    $form['advanced']['addition_drupal'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Advanced Drupal settings'),
      '#default_value' => $config['advanced_drupal'],
      '#description' => $this->t('It must be in valid JSON/YAML format. This controls the advanced Fullcalendar block behaviours.'),
      '#element_validate' => [[$this, 'validateAdvancedSettings']],
      '#attributes' => [
        'spellcheck' => 'false',
      ],
    ];

    if (!$this->isAjax()) {
      // Ignore the YAML editor enhancements when in an AJAX request. e.g.
      // Layout Builder. It can result in weird behaviours with how assets are
      // loaded in.
      if ($this->moduleHandler->moduleExists('codemirror_editor')) {
        // Integrates with https://www.drupal.org/project/codemirror_editor if
        // available (only certain options are passed in from PHP).
        $form['advanced']['addition']['#codemirror'] =
        $form['advanced']['addition_drupal']['#codemirror'] = [
          'mode' => 'text/x-yaml',
          'buttons' => ['undo', 'redo'],
          'lineNumbers' => TRUE,
          'lineWrapping' => TRUE,
        ];
      }
      elseif ($this->moduleHandler->moduleExists('webform')) {
        // Piggyback off the webform module's YAML editor.
        $form['advanced']['addition']['#type'] =
        $form['advanced']['addition_drupal']['#type'] = 'webform_codemirror';
        $form['advanced']['addition']['#mode'] =
        $form['advanced']['addition_drupal']['#mode'] = 'yaml';
        // We'll handle the validation ourself.
        $form['advanced']['addition']['#skip_validation'] =
        $form['advanced']['addition_drupal']['#skip_validation'] = TRUE;
      }
      elseif ($this->moduleHandler->moduleExists('yaml_editor')) {
        // Integrates with https://www.drupal.org/project/yaml_editor if
        // available.
        $form['advanced']['addition']['#attributes']['data-yaml-editor'] =
        $form['advanced']['addition_drupal']['#attributes']['data-yaml-editor'] = TRUE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['event_source'] = $values['event_source'];
    $this->configuration['use_token'] = (bool) $values['use_token'];
    $this->configuration['initial_view'] = $values['initial_view'];
    $this->configuration['header_start'] = $values['header_toolbar']['header_start'];
    $this->configuration['header_center'] = $values['header_toolbar']['header_center'];
    $this->configuration['header_end'] = $values['header_toolbar']['header_end'];
    $this->configuration['open_dialog'] = (int) $values['click_event']['open_dialog'];
    $this->configuration['dialog_width'] = (int) $values['click_event']['dialog_width'];
    $this->configuration['plugins'] = array_keys(array_filter($values['advanced']['plugins']));
    // Normalize the line endings.
    // https://www.drupal.org/node/3114725
    $this->configuration['advanced'] = trim(str_replace(["\r\n", "\r"], "\n", $values['advanced']['addition']));
    $this->configuration['advanced_drupal'] = trim(str_replace(["\r\n", "\r"], "\n", $values['advanced']['addition_drupal']));
  }

  /**
   * Callback to validate that the configuration is a valid YAML/JSON object.
   */
  public function validateAdvancedSettings(array $element, FormStateInterface $form_state) {
    try {
      $result = $this->decodeAdvancedSettings($element['#value'], TRUE);
      if (!is_array($result)) {
        $form_state->setError($element, $this->t('%field must be a valid JSON/JSON object. %type returned.', [
          '%field' => $element['#title'],
          '%type' => gettype($result),
        ]));
      }
    }
    catch (InvalidDataTypeException $e) {
      $form_state->setError($element, $this->t('%field must be a valid JSON/JSON object: @error.', [
        '%field' => $element['#title'],
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();

    $event_url = $this->configuration['event_source'];
    if ($event_url && !UrlHelper::isExternal($event_url)) {
      // Relative link, cache by the URL path.
      $cache_contexts = Cache::mergeContexts($cache_contexts, ['url.path']);
    }
    if ($this->languageManager->isMultilingual()) {
      // Configurations may change based on the current locale.
      $cache_contexts = Cache::mergeContexts($cache_contexts, ['languages:' . LanguageInterface::TYPE_CONTENT]);
    }

    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    // May change when the "First day of week" is updated.
    return Cache::mergeTags($cache_tags, ['config:system.date']);
  }

  /**
   * Decode advanced settings into an array object.
   *
   * YAML is a superset of JSON, and should be easily supported.
   */
  protected function decodeAdvancedSettings($settings, $validate = FALSE) {
    $settings = trim($settings);
    if ($settings) {
      try {
        return Yaml::decode($settings);
      }
      catch (InvalidDataTypeException $e) {
        /*
         * Work around issue with symfony/yaml sometimes being unable to parse
         * simple JSON as it's not fully compliant with the YAML spec.
         * https://github.com/symfony/symfony/issues/39011
         */
        $result = json_decode($settings, TRUE);
        if (json_last_error() === JSON_ERROR_NONE) {
          return $result;
        }
        if ($validate) {
          throw $e;
        }
      }
    }
    return [];
  }

  /**
   * Resolves the current event URL relative to the current URL.
   *
   * Applying the appropriate URL prefixes to the endpoint as necessary.
   * Which is useful for multilingual Drupal instances.
   *
   * Base relative links are left as is, whereas path relative links ('/') will
   * be processed and appropriately prefixed by Drupal.
   *
   * @param string $event_url
   *   The current URL.
   *
   * @return string
   *   The URL with the appropriate base prefix applied.
   */
  protected function resolveEventUrl($event_url) {
    if ($this->configuration['use_token']) {
      $event_url = $this->resolveUrlTokens($event_url);
    }
    if ($event_url && $event_url[0] === '/' && !UrlHelper::isExternal($event_url)) {
      if (!file_exists(DRUPAL_ROOT . $event_url)) {
        // Not a static file, resolve the relative path as normal.
        return Url::fromUri('internal:' . $event_url)->toString();
      }
    }
    return $event_url;
  }

  /**
   * Replace the tokens within the URL.
   */
  protected function resolveUrlTokens($event_url) {
    $types = [];
    foreach ($this->requestStack->getCurrentRequest()->attributes as $attribute_name => $attribute_value) {
      if ($attribute_value instanceof EntityInterface) {
        $types[$attribute_value->getEntityTypeId()] = $attribute_value;
      }
      elseif (is_string($attribute_value) || is_numeric($attribute_value)) {
        // If there's no param enhancer applied, attempt to load the entity from
        // its entity storage. e.g. on node previews.
        try {
          $entity_type_storage = $this->entityTypeManager->getStorage($attribute_name);
          $entity = $entity_type_storage->load($attribute_value);
          if ($entity instanceof EntityInterface) {
            $types[$entity->getEntityTypeId()] = $entity;
          }
        }
        catch (InvalidPluginDefinitionException | PluginNotFoundException $ignore) {
        }
      }
    }

    return $this->token->replace($event_url, $types, [
      // Don't clear in case there's special post-processing necessary that
      // exists outside the normal token API.
      'clear' => FALSE,
      'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
    ]);
  }

  /**
   * Generate a unique block index identifier.
   *
   * Our JavaScript needs to have some means to find the HTML belonging to
   * this block. Borrowing from views' "dom_id" implementation.
   *
   * In order to unequivocally match a block with its HTML, because multiple
   * calendar blocks may appear several times on the page.
   * We set up a hash with the current time, plugin_id, to issue a
   * "unique" identifier for each block. This identifier is used in the
   * drupalSettings and stored in the 'data-calendar-block-index' attribute of
   * the fullcalendar_block DIV.
   *
   * @return string
   *   The unique block index.
   *
   * @see template_preprocess_fullcalendar_block()
   */
  protected function generateBlockIndex() {
    return hash('sha256', $this->getPluginId() . $this->time->getRequestTime() . mt_rand());
  }

}
