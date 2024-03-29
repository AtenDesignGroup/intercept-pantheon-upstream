<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Provides a base class for OfficeHoursSlot form element.
 */
class OfficeHoursBaseSlot extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => [[static::class, 'processOfficeHoursSlot']],
      '#element_validate' => [[static::class, 'validateOfficeHoursSlot']],
    ];

    return $info;
  }

  /**
   * Gets this list element's default operations.
   *
   * @param array $element
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  public static function getDefaultOperations(array $element) {
    $operations = [];

    // Add explicitely, needed when dropbuttons are used.
    $operations['add'] = [];
    $operations['clear'] = [];
    $operations['copy'] = [];

    // The valueCallback() has populated the #value array.
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $item = $element['#value'];
    $day = $item->day;
    $day_delta = $element['#day_delta'];
    $max_delta = $element['#field_settings']['cardinality_per_day'] - 1;
    // For 'link', add dummy URL - it will be catch-ed by js.
    $url = Url::fromRoute('<front>');
    $suffix = ' ';

    // Note: the operations key is also using in JS, e.g., $('[id$=add]').
    // Note: When using '#type' => 'operations', the weight is not used,
    // so ordering must be OK here.
    //
    // Add 'Add time slot' link to all-but-last slots of each day.
    if ($day_delta < $max_delta) {
      $operations['add'] = [
        '#type' => 'link',
        '#title' => t('Add @type', ['@type' => t('time slot')]),
        '#weight' => 11,
        '#url' => $url,
        '#suffix' => $suffix,
        '#attributes' => [
          'class' => ['office-hours-link'],
        ],
      ];
    }

    // Show a 'Clear this line' js-link to each element.
    // Use text 'Remove', which has lots of translations.
    // Show this link always, even if empty, to allow not-committed entries.
    $operations['clear'] = [
      '#type' => 'link',
      '#title' => t('Remove'),
      '#weight' => 12,
      '#url' => $url,
      '#suffix' => $suffix,
      '#attributes' => [
        'class' => ['office-hours-link'],
      ],
    ];

    // Add 'Copy' link to first slot of each day.
    // First day copies from last day.
    if ($day_delta == 0) {
      $operations['copy'] = [
        '#type' => 'link',
        '#title' => ($day !== OfficeHoursDateHelper::getFirstDay())
          ? t('Copy previous day') : t('Copy last day'),
        '#weight' => 16,
        '#url' => $url,
        '#suffix' => $suffix,
        '#attributes' => [
          'class' => ['office-hours-link'],
        ],
      ];
    }

    // Wrap the operations in a div with specific class that will be used
    // in JS to target only elements coming from this module.
    if (!empty($operations)) {
      $operations['#prefix'] = "<div class='js-office-hours-operations-wrapper'>";
      $operations['#suffix'] = "</div>";
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {

    if ($input ?? FALSE) {
      // Massage, normalize value after pressing Form button.
      // $element is also updated via reference.
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
  public static function processOfficeHoursSlot(array &$element, FormStateInterface $form_state, array &$complete_form) {

    // The valueCallback() has populated the #value array.
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $item = $element['#value'];
    $day = $item->day;
    $day_delta = $element['#day_delta'];
    // Add day_delta for label() or isEmpty() call.
    $item->day_delta = $day_delta;

    $field_settings = $element['#field_settings'];
    $time_format = $field_settings['time_format'];

    // Prepare $element['#value'] for Form element/Widget.
    $element['day'] = [];
    $element['all_day'] = !$field_settings['all_day'] ? NULL : [
      '#type' => $day_delta ? 'hidden' : 'checkbox',
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => t('Indicates if the entity is opened all day'),
      '#title_display' => 'invisible',
      '#default_value' => $item->all_day,
    ];
    $element['starthours'] = [
      '#type' => $field_settings['element_type'], // 'datelist', 'datetime'.
      '#field_settings' => $field_settings,
      '#date_increment' => $field_settings['increment'],

      // Attributes for element \Drupal\Core\Datetime\Element\Datelist - Start.
      // Get the valid, restricted hours.
      // Date API doesn't provide a straight method for this.
      '#date_part_order' => in_array($time_format, ['g', 'h'])
        ? ['hour', 'minute', 'ampm']
        : ['hour', 'minute'],
      // Attributes for element \Drupal\Core\Datetime\Element\Datelist - End.
    ];
    $element['endhours'] = $element['starthours'];
    $element['starthours']['#default_value'] = $item->starthours;
    $element['endhours']['#default_value'] = $item->endhours;
    $element['comment'] = !$field_settings['comment'] ? NULL : [
      '#type' => 'textfield',
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => t('A Comment for this time slot'),
      '#title_display' => 'invisible',
      '#default_value' => $item->comment,
      '#size' => 20,
      '#maxlength' => 255,
      '#field_settings' => $field_settings,
    ];

    // Copied from EntityListBuilder::buildOperations().
    $element['operations'] = [
      'data' => self::getDefaultOperations($element),
    ];

    $element['#attributes']['class'][] = 'form-item';
    $element['#attributes']['class'][] = 'office-hours-slot';
    if ($day_delta == 0) {
      // This is the first slot of the day.
    }
    elseif (!OfficeHoursItem::isValueEmpty($item->getValue())) {
      // @todo Use $item->isEmpty(), but that gives other result, somehow.
      // This is a following slot with contents.
      // Display the slot and display Add-link.
      // Note: value includes the $day_delta parameter.
      $element['#attributes']['class'][] = 'js-office-hours-more';
    }
    else {
      // This is an empty following slot.
      // Hide the slot and Add-link, in case shown by js.
      $element['#attributes']['class'][] = 'js-office-hours-hide';
      $element['#attributes']['class'][] = 'js-office-hours-more';
    }
    // Add a helper for JS links (e.g., copy-link previousSelector) in widget.
    $day_index = $element['#day_index'];
    $element['#attributes']['class'][] = "js-office-hours-day-$day_index";
    $element['#attributes']['office_hours_day'] = "$day_index";

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
  public static function validateOfficeHoursSlot(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $error_text = '';

    // Return an array with starthours, endhours, comment.
    // Do not use NestedArray::getValue();
    // It does not return formatted values from valueCallback().
    // The valueCallback() has populated the #value array.
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $item = $element['#value'];
    $value = $item->getValue();

    // Avoid complex validation below. Remove comment, only in validation.
    // No complex validation if empty.
    // @todo Use $item->isEmpty(), but that gives other result, somehow.
    if (OfficeHoursItem::isValueEmpty(['comment' => NULL] + $value)) {
      return;
    }
    // Also check for exception days. Extra test for analysis purposes.
    if (OfficeHoursItem::isValueEmpty(['day' => '', 'comment' => NULL] + $value)) {
      return;
    }

    $day = $item->day;
    $field_settings = $element['#field_settings'];
    $date_helper = new OfficeHoursDateHelper();
    // Add day_delta for label() or isEmpty() call.
    $item->day_delta = 0;
    $settings = ['day_format' => 'long'];
    $label = $item->label($settings);

    // Exception: end time 00:00 --> 24:00.
    $start = $date_helper->format($item->starthours, 'Gi', FALSE);
    $end = $date_helper->format($item->endhours, 'Gi', TRUE);
    $all_day = $item->all_day;

    $time_format = $date_helper->getTimeFormat($field_settings['time_format']);
    $validate_hours = $field_settings['valhrs'];
    $limit_start = $date_helper->format(intval($field_settings['limit_start']) * 100, 'Gi', FALSE);
    $limit_end = $date_helper->format(intval($field_settings['limit_end']) * 100, 'Gi', TRUE);
    $all_day_allowed = $field_settings['all_day'];

    // If any field of slot is filled, check for required time fields.
    $required_start = $validate_hours || $field_settings['required_start'] ?? FALSE;
    $required_end = $validate_hours || $field_settings['required_end'] ?? FALSE;

    // Generate message.
    if ($day !== 0 && !$day) {
      $label = t('Day');
      $error_text = 'A day is required when hours are entered.';
      $erroneous_element = &$element['day'];
    }
    elseif (!$all_day && $required_start && empty($start)) {
      $error_text = 'Opening hours must be set.';
      $erroneous_element = &$element['starthours'];
    }
    elseif (!$all_day && $required_end && empty($end)) {
      $error_text = 'Closing hours must be set.';
      $erroneous_element = &$element['endhours'];
    }
    elseif ($validate_hours && $end < $start) {
      // Both Start and End must be entered. That is validated above already.
      $error_text = 'Closing hours are earlier than Opening hours.';
      $erroneous_element = &$element;
    }
    elseif (!$all_day_allowed && (!empty($limit_start) || !empty($limit_end))) {
      if ($start && ($limit_start > $start)
        || ($end && ($limit_end < $end))
      ) {
        $error_text = 'Hours are outside limits ( @start - @end ).';
        $erroneous_element = &$element;
      }
    }

    if ($error_text) {
      $error_text = $label
        . ': '
        . t($error_text,
          [
            '@start' => $date_helper->format($limit_start, $time_format, FALSE),
            '@end' => $date_helper->format($limit_end, $time_format, FALSE),
          ],
          ['context' => 'office_hours']
        );
      $form_state->setError($erroneous_element, $error_text);
    }
  }

}
