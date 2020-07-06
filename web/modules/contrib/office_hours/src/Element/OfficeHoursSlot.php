<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Provides a one-line text field form element.
 *
 * @FormElement("office_hours_slot")
 */
class OfficeHoursSlot extends OfficeHoursList {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function processOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {
    // Fill with default data from a List element.
    $element = parent::processOfficeHoursSlot($element, $form_state, $complete_form);
    // @todo D8 $form_state = ...
    // @todo D8 $form = ...

    $max_delta = $element['#field_settings']['cardinality_per_day'] - 1;
    $day_delta = $element['#daydelta'];
    if ($day_delta == 0) {
      // This is the first slot of the day.
      $label = $element['#dayname']; // Show Day name (already translated) as label.
      $element['#attributes']['class'][] = 'office-hours-slot'; // Show the slot.
    }
    elseif ($day_delta > $max_delta) {
      // Never show this illegal slot.
      // In case the number of slots per day was lowered by admin, this element
      // may have a value. Better clear it (in case a value was entered before).
      // The value will be removed upon the next 'Save' action.
      $label = '';
      // The following style is only needed if js isn't working.
      // The following class is the trigger for js to hide the row.
      $element['#attributes']['class'][] = 'office-hours-hide';

      $element['#value'] = empty($element['#value'] ? [] : $element['#value']);
      $element['#value']['starthours'] = '';
      $element['#value']['endhours'] = '';
      $element['#value']['comment'] = NULL;
    }
    elseif (isset($element['#value']['starthours']) && !empty($element['#value']['starthours'])) {
      // This is a following slot with contents.
      $label = t('and');
      $element['#attributes']['class'][] = 'office-hours-slot'; // Show the slot.
      $element['#attributes']['class'][] = 'office-hours-more'; // Show add-link.
    }
    else {
      // This is an empty following slot.
      $label = t('and');
      $element['#attributes']['class'][] = 'office-hours-hide'; // Hide the slot.
      $element['#attributes']['class'][] = 'office-hours-more'; // Add the add-link, in case shown by js.
    }

    // Overwrite the 'day' select-field.
    $day_number = $element['#day'];
    $element['day'] = [
      '#type' => 'hidden',
      '#prefix' => $day_delta ? "<div class='office-hours-more-label'>$label</div>" : "<div class='office-hours-label'>$label</div>",
      '#default_value' => $day_number,
    ];
    $element['#attributes']['class'][] = "office-hours-day-$day_number";

    return $element;
  }

  /**
   * Render API callback: Validates the office_hours_slot element.
   *
   * Implements a callback for _office_hours_elements().
   *
   * For 'office_hours_slot' (day) and 'office_hours_datelist' (hour) elements.
   * You can find the value in $element['#value'], but better in $form_state['values'],
   * which is set in validateOfficeHoursSlot().
   */
  //public static function validateOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {
  //   return parent::validateOfficeHoursSlot($element, $form_state, $complete_form);
  //}

  /**
   * Gets this list's default operations.
   *
   * @param array $element
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected static function getDefaultOperations($element) {
    $operations = [];
    $operations['copy'] = [];
    $operations['delete'] = [];
    $operations['add'] = [];
    $suffix = ' ';

    $max_delta = $element['#field_settings']['cardinality_per_day'] - 1;
    $day_delta = $element['#daydelta'];

    // Show a 'Clear this line' js-link to each element.
    // Use text 'Remove', which has lots of translations.
    $operations['delete'] = [];
    if (isset($element['#value']['starthours']) || isset($element['#value']['endhours'])) {
      $operations['delete'] = [
        '#type' => 'link',
        '#title' => t('Remove'),
        '#weight' => 12,
        '#url' => Url::fromRoute('<front>'),
        // dummy-url, will be catch-ed by javascript.
        '#suffix' => $suffix,
        '#attributes' => [
          'class' => ['office-hours-delete-link',],
        ],
      ];
    }

    // Add 'Copy' link to first slot of each day; first day copies from last day.
    $operations['copy'] = [];
    if ($day_delta == 0) {
      $operations['copy'] = [
        '#type' => 'link',
        '#title' => ($element['#day'] !== OfficeHoursDateHelper::getFirstDay()) && ($day_delta == 0)
          ? t('Copy previous day') : t('Copy last day'),
        '#weight' => 16,
        '#url' => Url::fromRoute('<front>'),
        // dummy-url, will be catch-ed by javascript.
        '#suffix' => $suffix,
        '#attributes' => [
          'class' => ['office-hours-copy-link',],
        ],
      ];
    }

    // Add 'Add time slot' link to all-but-last slots of each day.
    $operations['add'] = [];
    if ($day_delta < $max_delta) {
      $operations['add'] = [
        '#type' => 'link',
        '#title' => t('Add @node_type', ['@node_type' => t('time slot'),]),
        '#weight' => 11,
        '#url' => Url::fromRoute('<front>'),
        // dummy-url, will be catch-ed by javascript.
        '#suffix' => $suffix,
        '#attributes' => [
          'class' => ['office-hours-add-link',],
        ],
      ];
    }

    return $operations;
  }

}
