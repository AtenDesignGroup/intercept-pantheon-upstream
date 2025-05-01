<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\office_hours\Controller\StatusUpdateController;
use Drupal\office_hours\OfficeHoursCacheHelper;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract plugin implementation of the formatter.
 */
abstract class OfficeHoursFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The maximum horizon for Exception days in formatter.
   *
   * @var int
   */
  public const EXCEPTION_HORIZON_MAX = 999;

  /**
   * Entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Indicates whether cache data must be attached. (FALSE for subformatters).
   *
   * @var bool
   */
  public $attachCache = TRUE;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, ModuleHandlerInterface $module_handler) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = [
      'day_format' => 'long',
      'time_format' => 'G',
      'compress' => FALSE,
      'grouped' => FALSE,
      'show_closed' => 'all',
      'show_empty' => FALSE,
      'closed_format' => 'Closed',
      'all_day_format' => 'All day open',
      'separator' => [
        'days' => '<br />',
        'grouped_days' => ' - ',
        'day_hours' => ': ',
        'hours_hours' => '-',
        'more_hours' => ', ',
      ],
      'current_status' => [
        'position' => '', // Hidden.
        'open_text' => 'Currently open!',
        'closed_text' => 'Currently closed',
      ],
      'exceptions' => [
        'replace_exceptions' => FALSE,
        'restrict_exceptions_to_num_days' => 7,
        'restrict_seasons_to_num_days' => 366,
        'date_format' => 'long',
        'title' => 'Exception hours',
        'all_day_format' => 'All day open',
      ],
      'schema' => [
        'enabled' => FALSE,
      ],
      'timezone_field' => '',
      'office_hours_first_day' => '',
    ];
    return $default_settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function mergeDefaults() {
    // Override parent, since that does not support sub-arrays.
    if (isset($this->settings['exceptions'])) {
      if (!is_array($this->settings['exceptions'])) {
        $this->settings['exceptions'] = [];
      }
      $this->settings['exceptions'] += static::defaultSettings()['exceptions'];
    }
    if (isset($this->settings['schema'])) {
      if (!is_array($this->settings['schema'])) {
        $this->settings['schema'] = [];
      }
      $this->settings['schema'] += static::defaultSettings()['schema'];
    }
    parent::mergeDefaults();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $settings = $this->getSettings();
    $day_names = OfficeHoursDateHelper::weekDays(FALSE);
    $day_names[''] = $this->t("- system's Regional settings -");

    /*
    // Find timezone fields, to be used in 'Current status'-option.
    $timezone_fields = [];
    $fields = field_info_instances(
      $form['#entity_type'] ?? NULL,
      $form['#bundle'] ?? NULL
    );
    foreach ($fields as $field_name => $timezone_instance) {
      if ($field_name !== $field['field_name']) {
        $timezone_field = field_read_field($field_name);
        if (in_array($timezone_field['type'], ['tzfield'])) {
          $timezone_fields[$timezone_instance['field_name']]
          = $timezone_instance['label']
          . ' ('
          . $timezone_instance['field_name'] . ')';
        }
      }
    }
    if ($timezone_fields) {
      $timezone_fields = ['' => '<None>'] + $timezone_fields;
    }
     */

    $element['show_closed'] = [
      '#title' => $this->t('Number of days to show'),
      '#type' => 'select',
      '#options' => $this->getShowDaysOptions(),
      '#default_value' => $settings['show_closed'],
      '#description' => $this->t('The days to show in the formatter. Useful in combination with the Current Status block.'),
    ];
    // First day of week, copied from system.variable.inc.
    $element['office_hours_first_day'] = [
      '#title' => $this->t('First day of week'),
      '#type' => 'select',
      '#options' => $day_names,
      '#default_value' => $this->getSetting('office_hours_first_day'),
    ];
    $element['day_format'] = [
      '#title' => $this->t('Day notation'),
      '#type' => 'select',
      '#options' => [
        'long' => $this->t('long'),
        'short' => $this->t('3-letter weekday abbreviation'),
        'two_letter' => $this->t('2-letter weekday abbreviation'),
        'number' => $this->t('number'),
        'none' => $this->t('none'),
      ],
      '#default_value' => $settings['day_format'],
    ];
    // @todo D8 Align with DateTimeDatelistWidget.
    $element['time_format'] = [
      '#title' => $this->t('Time notation'),
      '#type' => 'select',
      '#options' => [
        'G' => $this->t('24 hour time') . ' (9:00)',
        'H' => $this->t('24 hour time') . ' (09:00)',
        'g' => $this->t('12 hour time') . ' (9:00 am)',
        'h' => $this->t('12 hour time') . ' (09:00 am)',
      ],
      '#default_value' => $settings['time_format'],
      '#required' => FALSE,
      '#description' => $this->t('Format of the clock in the formatter.'),
    ];
    $element['compress'] = [
      '#title' => $this->t('Compress all hours of a day into one set'),
      '#type' => 'checkbox',
      '#default_value' => $settings['compress'],
      '#description' => $this->t('Even if more hours is allowed, you might want to show a compressed form. E.g., 7:00-12:00, 13:30-19:00 becomes 7:00-19:00.'),
      '#required' => FALSE,
    ];
    $element['grouped'] = [
      '#title' => $this->t('Group consecutive days with same hours into one set'),
      '#type' => 'checkbox',
      '#default_value' => $settings['grouped'],
      '#description' => $this->t('E.g., Mon: 7:00-19:00; Tue: 7:00-19:00 becomes Mon-Tue: 7:00-19:00.'),
      '#required' => FALSE,
    ];
    $element['show_empty'] = [
      '#title' => $this->t('Show the hours, even when fully empty'),
      '#type' => 'checkbox',
      '#default_value' => $settings['show_empty'],
      // @todo #3501768 Add description about which title is displayed.
      '#description' => $this->t('If not set, the field is hidden when no time slots are maintained.'),
      '#required' => FALSE,
    ];
    $element['closed_format'] = [
      '#title' => $this->t('Empty day notation'),
      '#type' => 'textfield',
      '#size' => 30,
      '#default_value' => $settings['closed_format'],
      '#required' => FALSE,
      '#description' => $this->t('Format of empty (closed) days.
        String can be translated when the
        <a href=":install">Interface Translation module</a> is installed.',
        [
          ':install' => Url::fromRoute('system.modules_list')->toString(),
        ]
      ),
    ];
    $element['all_day_format'] = [
      '#title' => $this->t('All day notation'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['all_day_format'],
      '#required' => FALSE,
      '#description' => $this->t('Format for all-day-open days.
        String can be translated when the
        <a href=":install">Interface Translation module</a> is installed.',
        [
          ':install' => Url::fromRoute('system.modules_list')->toString(),
        ]
      ),
    ];

    // Taken from views_plugin_row_fields.inc.
    $element['separator'] = [
      '#title' => $this->t('Separators'),
      '#type' => 'details',
      '#open' => FALSE,
    ];
    $element['separator']['days'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['days'],
      '#description' => $this->t("This separator will be placed between the days.
        Use &#39&ltbr&gt&#39 or &#39&lthr&gt&#39 to show each day on a new line.
        &#39&ltdiv&gt&#39 or &#39&ltspan&gt&#39 are accepted, too."),
    ];
    $element['separator']['grouped_days'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['grouped_days'],
      '#description' => $this->t('This separator will be placed between the labels of grouped days.'),
    ];
    $element['separator']['day_hours'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['day_hours'],
      '#description' => $this->t('This separator will be placed between the day and the hours.'),
    ];
    $element['separator']['hours_hours'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['hours_hours'],
      '#description' => $this->t('This separator will be placed between the hours of a day.'),
    ];
    $element['separator']['more_hours'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['more_hours'],
      '#description' => $this->t('This separator will be placed between the hours and more_hours of a day.'),
    ];

    // Show a 'Current status' option.
    $element['current_status'] = [
      '#title' => $this->t('Current status'),
      '#type' => 'details',
      '#open' => FALSE,
      '#description' => $this->t('Below strings can be translated when the
        <a href=":install">Interface Translation module</a> is installed.',
        [
          ':install' => Url::fromRoute('system.modules_list')->toString(),
        ]),
    ];
    $element['current_status']['position'] = [
      '#title' => $this->t('Current status position'),
      '#type' => 'select',
      '#options' => [
        '' => $this->t('Hidden'),
        'before' => $this->t('Before hours'),
        'after' => $this->t('After hours'),
      ],
      '#default_value' => $settings['current_status']['position'],
      '#description' => $this->t('Where should the current status be located?'),
    ];
    $element['current_status']['open_text'] = [
      '#title' => $this->t('Status strings'),
      '#type' => 'textfield',
      '#size' => 40,
      '#default_value' => $settings['current_status']['open_text'],
      '#description' => $this->t('Format of the message displayed when currently open.'),
    ];
    $element['current_status']['closed_text'] = [
      '#type' => 'textfield',
      '#size' => 40,
      '#default_value' => $settings['current_status']['closed_text'],
      '#description' => $this->t('Format of message displayed when currently closed.'),
    ];

    $element['exceptions'] = [
      '#title' => $this->t('Exception days and Seasons'),
      '#type' => 'details',
      '#open' => FALSE,
      '#description' => $this->t("Note: Exception days and Seasons
        can only be maintained using the '(week) with exceptions' widget."),
    ];
    // Get the exception day formats.
    $formats = $this->entityTypeManager->getStorage('date_format')->loadMultiple();
    // @todo Set date format options using OptionsProviderInterface.
    $options = [];
    foreach ($formats as $format) {
      $options[$format->id()] = $format->get('label');
    }
    $element['exceptions']['replace_exceptions'] = [
      '#title' => $this->t('Replace weekday time slots with exception dates'),
      '#type' => 'checkbox',
      '#default_value' => $settings['exceptions']['replace_exceptions'],
      '#description' => $this->t("The normal weekday time slots will be replaced
        with time slots from an exception date, if any exists.
        This will generate a 'rolling' calendar for the regular week
        (On Wednesday, the next tuesday will be replaced,
        not the previous tuesday). Seasonal weeks are not affected."),
    ];
    $element['exceptions']['restrict_exceptions_to_num_days'] = [
      '#title' => $this->t('Restrict exceptions display to x days in future'),
      '#type' => 'number',
      '#default_value' => $settings['exceptions']['restrict_exceptions_to_num_days'],
      '#min' => 0,
      '#max' => OfficeHoursFormatterBase::EXCEPTION_HORIZON_MAX,
      '#step' => 1,
      '#required' => TRUE,
    ];
    $element['exceptions']['restrict_seasons_to_num_days'] = [
      '#title' => $this->t('Restrict seasons display to x days in future'),
      '#type' => 'number',
      '#default_value' => $settings['exceptions']['restrict_seasons_to_num_days'],
      '#min' => 0,
      '#max' => OfficeHoursFormatterBase::EXCEPTION_HORIZON_MAX,
      '#step' => 1,
      '#required' => TRUE,
    ];
    // @todo Add link to admin/config/regional/date-time.
    $element['exceptions']['date_format'] = [
      '#title' => $this->t('Date format for exception day'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $settings['exceptions']['date_format'],
      '#description' => $this->t("Maintain additional date formats <a href=':url'>here</a>.", [
        ':url' => Url::fromRoute('entity.date_format.collection')->toString(),
      ]),
      '#required' => TRUE,
    ];
    // @todo Move to field settings, since used in both Formatter and Widget.
    $element['exceptions']['title'] = [
      '#title' => $this->t('Title for exceptions section'),
      '#type' => 'textfield',
      '#default_value' => $settings['exceptions']['title'],
      '#description' => $this->t('Leave empty to display no title between weekdays and exception days.'),
      '#required' => FALSE,
    ];
    $element['exceptions']['all_day_format'] = [
      '#title' => $this->t('All day notation for exceptions'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['exceptions']['all_day_format'],
      '#required' => FALSE,
      '#description' => $this->t('Format for all-day-open days.
        String can be translated when the
        <a href=":install">Interface Translation module</a> is installed.',
        [
          ':install' => Url::fromRoute('system.modules_list')->toString(),
        ]
      ),
    ];

    $element['schema'] = [
      '#title' => $this->t('Schema.org openingHours support'),
      '#type' => 'details',
      '#open' => FALSE,
    ];
    $element['schema']['enabled'] = [
      '#title' => $this->t('Add an additional Schema.org openingHours formatter'),
      '#type' => 'checkbox',
      '#default_value' => $settings['schema']['enabled'],
      '#description' => $this->t('Enable meta tags with property for https://schema.org/openingHours.'),
      '#required' => FALSE,
    ];

    /*
    if ($timezone_fields) {
      $element['timezone_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Timezone') . ' ' . $this->t('Field'),
        '#options' => $timezone_fields,
        '#default_value' => $settings['timezone_field'],
        '#description' => $this->t('Should we use another field to set the timezone for these hours?'),
      ];
    }
    else {
      $element['timezone_field'] = [
        '#type' => 'hidden',
        '#title' => $this->t('Timezone') . ' ' . $this->t('Field'),
        '#value' => $settings['timezone_field'],
      ];
    }
     */

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $settings = $this->getSettings();
    $date = strtotime('today midnight');
    $weekday = OfficeHoursDateHelper::getWeekday($date);
    $first_day = OfficeHoursDateHelper::getFirstDay($settings['office_hours_first_day']);
    $current_status = $settings['current_status']['position'];

    $summary[] = $this->t('Display Office hours in different formats.');
    $summary[] = $this->t("@show and time slots as @label @time, starting with @first_day.", [
      '@show' => $this->getShowDaysOptions()[$settings['show_closed']],
      '@label' => OfficeHoursItem::formatLabel(
        $settings['day_format'], ['day' => $weekday]),
      '@time' => OfficeHoursDateHelper::format('1100', OfficeHoursDateHelper::getTimeFormat(
        $settings['time_format']), FALSE),
      '@first_day' => OfficeHoursItem::formatLabel(
        $settings['day_format'], ['day' => $first_day]),
    ]);
    $summary[] = $this->t("Show '@title' until @time days in the future. Example: @label.", [
      '@time' => $settings['exceptions']['restrict_exceptions_to_num_days'],
      '@title' => $this->t($settings['exceptions']['title'] ?: 'Exception days'),
      '@label' => OfficeHoursItem::formatLabel(
        $settings['exceptions']['date_format'], ['day' => $date]),
    ]);
    $summary[] = $this->t("Show '@title' until @time days in the future.", [
      '@time' => $settings['exceptions']['restrict_seasons_to_num_days'],
      '@title' => $this->t('Seasons'),
    ]);
    $summary[] = $this->t("Show @yesno current opening status @status the time slots.", [
      '@yesno' => $current_status == '' ? $this->t('no') : '',
      '@status' => $current_status == 'after' ? $this->t($current_status) : $this->t('before'),
    ]);
    $summary[] = $this->t("A schema.org/openingHours formatter is @yesno added.", [
      '@yesno' => $settings['schema']['enabled'] ? '' : $this->t('not'),
    ]);

    return $summary;
  }

  /**
   * Returns the possible options for.
   *
   * @return string[]
   *   The possible options.
   */
  private function getShowDaysOptions(): array {
    return [
      'all' => $this->t('Show all days'),
      'open' => $this->t('Show only open days'),
      'next' => $this->t('Show next open day'),
      'none' => $this->t('Hide all days'),
      'current' => $this->t('Show only current day'),
    ];
  }

  /**
   * Returns the protected field definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The wrapped field definition.
   */
  public function getFieldDefinition() {
    return $this->fieldDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
    $elements = [];

    $formatter_settings = $this->getSettings();
    // Hide the formatter if no data is filled for this entity,
    // or if empty fields must be hidden.
    if ($items->isEmpty() && !$formatter_settings['show_empty']) {
      return $elements;
    }

    $elements[] = [
      '#theme' => 'office_hours',
      '#parent' => $items->getFieldDefinition(),
      '#weight' => 10,
      // Pass filtered office_hours structures to twig theming.
      '#office_hours' => [],
      // Pass (unfiltered) office_hours items to twig theming.
      '#office_hours_field' => $items,
      // Pass formatting options to twig theming.
      '#is_open' => $items->isOpen(),
      '#item_separator' => Xss::filter(
        $formatter_settings['separator']['days'], ['br', 'hr', 'span', 'div']
      ),
      '#slot_separator' => $formatter_settings['separator']['more_hours'],
      '#attributes' => [
        'class' => ['office-hours'],
      ],
      // '#empty' => $this->t('This location has no opening hours.'),
      '#attached' => [
        'library' => [
          'office_hours/office_hours_formatter',
        ],
      ],
    ];

    return $elements;
  }

  /**
   * Add an 'openingHours' formatter from https://schema.org/openingHours.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   The office hours.
   * @param string $langcode
   *   The required language code.
   * @param array $elements
   *   Elements.
   *
   * @return array
   *   A formatter element.
   */
  protected function attachSchemaFormatter(OfficeHoursItemListInterface $items, $langcode, array &$elements) {

    if (empty($this->settings['schema']['enabled'])) {
      return $elements;
    }

    $formatter = new OfficeHoursFormatterSchema(
      $this->pluginId, $this->pluginDefinition, $this->fieldDefinition,
      $this->settings, $this->viewMode, $this->label, $this->thirdPartySettings, $this->entityTypeManager,
      $this->currentUser, $this->moduleHandler);
    $formatter->attachCache = FALSE;
    $element = $formatter->viewElements($items, $langcode);
    $element = reset($element);
    $element['#weight'] = 0;

    $elements[] = $element;
    return $elements;
  }

  /**
   * Add a 'status' formatter before or after the hours, if necessary.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   The office hours.
   * @param string $langcode
   *   The required language code.
   * @param array $elements
   *   Elements.
   *
   * @return array
   *   A formatter element.
   */
  protected function attachStatusFormatter(OfficeHoursItemListInterface $items, $langcode, array &$elements) {
    $position = $this->settings['current_status']['position'];

    if (empty($position)) {
      return $elements;
    }

    $formatter = new OfficeHoursFormatterStatus(
      $this->pluginId, $this->pluginDefinition, $this->fieldDefinition,
      $this->settings, $this->viewMode, $this->label, $this->thirdPartySettings, $this->entityTypeManager,
      $this->currentUser, $this->moduleHandler);
    $formatter->attachCache = FALSE;
    $element = $formatter->viewElements($items, $langcode);
    $element = reset($element);
    $element['#weight'] = $position == 'before' ? -10 : 999999;

    $elements[] = $element;
    return $elements;
  }

  /**
   * Add caching data to $elements, if necessary.
   *
   * Enable dynamic field update in office_hours_status_update.js.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   The office hours.
   * @param string $langcode
   *   The required language code.
   *
   * @return array
   *   An array of 'attachments' for the formatter element.
   */
  protected function attachCacheData(OfficeHoursItemListInterface $items, $langcode) {
    $elements = [];

    $formatter_settings = $this->getSettings();
    $cache_helper = new OfficeHoursCacheHelper($formatter_settings, $items);

    if (!$cache_helper->isCacheNeeded()) {
      // If no cache needed, do not bother.
      return $elements;
    }

    $max_age = $cache_helper->getCacheMaxAge();

    if ($max_age == Cache::PERMANENT) {
      // If page is valid forever, do not bother.
      return $elements;
    }

    // Attach Javascript for isolated field update,
    // when page cache is not refreshed on time.
    if ($this->currentUser->isAnonymous()) {
      // Notes to consider for Anonymous users:
      // - Page is cached forever when no JS statusUpdate is triggered
      // and caching is set in admin/config/development/performance.
      // then page is cached Permanently for Anonymous users.
      // - Page will not be cached after (dummy) message, since this will
      // trigger killSwitch().
      // \Drupal::messenger()->addMessage($max_age .' / '. __FUNCTION__);
      $view_mode = $this->viewMode;
      // Fetch layout_builder data.
      $third_party_settings = $this->thirdPartySettings;
      $elements = StatusUpdateController::attachStatusUpdate($items, $langcode, $view_mode, $third_party_settings, $elements);
    }

    if ($this->currentUser->isAnonymous()) {
      // Manipulate '#cache' max_age when page_cache module enabled.
      if ($this->moduleHandler->moduleExists('page_cache')) {
        // The Internal Page Cache module handles
        // page cache for anonymous users.
        // It should be used for small/medium websites
        // when external page cache is not available.
        // However, it caches a page always,
        // which is unwanted for office_hours,
        // where the open/closed status can change any minute.
        // Setting the max-age to 0 prevents the caching.
        //
        // Note: this is a workaround. IMO the page_cache module is flawed.
        // @see https://www.drupal.org/project/office_hours/issues/3351280
        // where js callback is implemented, to re-read the field each call.
        // @see https://www.drupal.org/project/drupal/issues/2835068
        $max_age = 0;
      }
    }

    // Attach '#cache' data.
    // @see https://www.drupal.org/docs/drupal-apis/cache-api
    $elements['#cache'] = [
      'max-age' => $max_age,
      'tags' => $cache_helper->getCacheTags(),
      // 'contexts' => $cache_helper->getCacheContexts(),
    ];

    return $elements;
  }

}
