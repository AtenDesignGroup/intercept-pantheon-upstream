<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\OfficeHoursSeason;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Provides a one-line form element for Season header.
 *
 * @FormElement("office_hours_season_header")
 */
class OfficeHoursSeasonHeader extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => [[static::class, 'process']],
      '#value_callback' => [[static::class, 'valueCallback']],
      '#element_validate' => [[static::class, 'validate']],
    ];

    return $info;
  }

  /**
   * Gets this list element's default operations.
   *
   * @param \Drupal\office_hours\OfficeHoursSeason $season
   *   The element the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  public static function getDefaultOperations(OfficeHoursSeason $season) {
    // @todo Add better seasonal add, copy, delete JS-links.
    $operations = [];

    // For 'link', add dummy URL - it will be catch-ed by js.
    // $url = Url::fromRoute('<front>');
    $url = '';
    $suffix = ' ';

    // Add a 'Delete this season' element.
    // Use text 'Remove', which has lots of translations.
    // Show this link always, even if empty, to allow not-committed entries.
    // Set in OfficeHoursSeasonHeader, parsed in OfficeHoursSeasonWidget.
    $operations['delete'] = [
      '#type' => 'checkbox',
      // '#type' => 'link',
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => t('Remove upon save'),
      '#title_display' => 'after', // {'before', 'after', ' invisible' }
      '#required' => FALSE,
      '#weight' => 12,
      '#url' => $url,
      '#suffix' => $suffix,
      '#attributes' => [
        'class' => ['js-office-hours-season-delete-link', 'office-hours-link'],
      ],
    ];

    // @todo Add 'Copy' JS-link. (Removed in last line.)
    $operations['copy'] = [
      // '#type' => 'checkbox',
      '#type' => 'link',
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => t('Copy season'),
      '#title_display' => 'after', // {'before', 'after', ' invisible' }
      '#required' => FALSE,
      '#weight' => 16,
      '#url' => $url,
      '#suffix' => $suffix,
      '#attributes' => [
        'class' => ['js-office-hours-season-copy-link', 'office-hours-link'],
      ],
    ];
    unset($operations['copy']);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input ?? FALSE) {
      // Massage, normalize value after pressing Form button.
      // $element is also updated via reference.
      /** @var \Drupal\office_hours\OfficeHoursSeason $item */
      $item = $element['#default_value'];
      $item->setValue($input);
      return $item;
    }

    return NULL;
  }

  /**
   * Render API callback: Builds one OH-slot element.
   *
   * Build the form element. When creating a form using Form API #process,
   * note that $element['#value'] is already set.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The enriched element, identical to first parameter.
   */
  public static function process(array &$element, FormStateInterface $form_state, array &$complete_form) {

    // The valueCallback() has populated the #value array.
    /** @var \Drupal\office_hours\OfficeHoursSeason $season */
    $season = $element['#value'];
    $season_id = $season->id();

    // Add standardized labels to time slot element.
    $field_settings = $element['#field_settings'];
    $labels = OfficeHoursItem::getPropertyLabels('data', $field_settings + ['season' => TRUE]);

    // @todo Perhaps provide extra details in following elements.
    // details #description;
    // container #description;
    // container #prefix;
    // container #title;
    // name #prefix;
    $label = $season->label();
    $name = $season->getName();

    // Prepare $element['#value'] for Form element/Widget.
    $element['day'] = [];
    $element['id'] = [
      '#type' => 'value', // 'hidden',
      '#value' => $season_id,
    ];
    $element['name'] = [
      '#type' => 'textfield',
      '#required' => FALSE,
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => $labels['season']['data'],
      // '#title_display' => 'before', // {'before' | invisible'}.
      // '#prefix' => "<b>" . $labels['season']['data'] . "</b>",
      // Use the untranslated $name, here, not the translated $label.
      '#default_value' => $name,
      '#size' => 16,
      '#maxlength' => 40,
    ];
    $element['from'] = [
      '#type' => 'date',
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => $labels['from']['data'],
      // '#title_display' => 'before', // {'before' | invisible'}.
      // '#prefix' => "<b>" . $labels['from']['data'] . "</b>",
      '#default_value' => $season->getFromDate(OfficeHoursDateHelper::DATE_STORAGE_FORMAT),
      // @todo Add conditionally required from/to fields.
      '#required' => [
        // ':input[name="name"]' => ['value' => t('Season name')],
        // 'or',
        // ':input[name="'.$input_name.'"]' => ['value' => ''],
        // ':input[name="$input_name"]' => ['size' => '16'],
       ],
    ];
    $element['to'] = [
      '#type' => 'date',
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => $labels['to']['data'],
      // '#title_display' => 'before', // {'before' | invisible'}.
      // '#prefix' => "<b>" . $labels['to']['data'] . "</b>",
      '#default_value' => $season->getToDate(OfficeHoursDateHelper::DATE_STORAGE_FORMAT),
    ];

    // @todo #3135259 Add better seasonal add, copy, delete JS-links.
    // Copy from \Drupal\Core\Entity\EntityListBuilder::buildOperations().
    $element['operations'] = [
      'data' => self::getDefaultOperations($season),
    ];

    $element['#attributes']['class'][] = 'form-item';
    $element['#attributes']['class'][] = 'office-hours-slot';

    $element['#attributes']['id'] = $element['#id'];

    return $element;
  }

  /**
   * Render API callback: Validates one OH-slot element.
   *
   * Implements a callback for _office_hours_elements().
   *
   * For 'office_hours_slot' (day) and 'office_hours_datelist' (hour) elements.
   * You can find the value in $element['#value'],
   * but better in $form_state['values'],
   * which is set in validateOfficeHoursSlot().
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validate(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $error_text = '';

    // Return an array with starthours, endhours, comment.
    // Do not use NestedArray::getValue();
    // It does not return formatted values from valueCallback().
    // The valueCallback() has populated the #value array.
    /** @var \Drupal\office_hours\OfficeHoursSeason $season */
    $season = $element['#value'];
    if ($season->isEmpty()) {
      // Empty season dates will be cleared later.
      return;
    }

    $start = $season->getFromDate();
    $end = $season->getToDate();
    if (empty($start) && empty($end)) {
      $error_text = 'A start date and end date must be set for the season.';
      $erroneous_element = &$element;
    }
    elseif (empty($start)) {
      $error_text = 'A start date must be set for the season.';
      $erroneous_element = &$element['from'];
    }
    elseif (empty($end)) {
      $error_text = 'An end date must be set for the season.';
      $erroneous_element = &$element['to'];
    }
    elseif ($end < $start) {
      // Both Start and End must be entered. That is validated above already.
      $error_text = 'Seasonal end date must be later then start date.';
      $erroneous_element = &$element;
    }

    if ($error_text) {
      $error_text = t($error_text);
      $form_state->setError($erroneous_element, $error_text);
    }
  }

}
