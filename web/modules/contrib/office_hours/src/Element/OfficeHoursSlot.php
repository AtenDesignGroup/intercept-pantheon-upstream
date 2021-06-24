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
  public static function processOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {
    // Fill with default data from a List element.
    $element = parent::processOfficeHoursSlot($element, $form_state, $complete_form);

    $max_delta = $element['#field_settings']['cardinality_per_day'] - 1;
    $day_delta = $element['#daydelta'];
    if ($day_delta == 0) {
      // This is the first slot of the day.
      // Display the slot with Day name (already translated) as label.
      $label = $element['#dayname'];
      $element['#attributes']['class'][] = 'office-hours-slot';
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
      $element['#value']['comment'] = '';
    }
    elseif (isset($element['#value']['starthours']) && (!empty($element['#value']['starthours']) || $element['#value']['starthours'] === '0')) {
      // This is a following slot with contents.
      $label = t('and');
      // Display the slot and display Add-link.
      $element['#attributes']['class'][] = 'office-hours-slot';
      $element['#attributes']['class'][] = 'office-hours-more';
    }
    else {
      // This is an empty following slot.
      $label = t('and');
      // Hide the slot and add Add-link, in case shown by js.
      $element['#attributes']['class'][] = 'office-hours-hide';
      $element['#attributes']['class'][] = 'office-hours-more';
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
   * Gets this list's default operations.
   *
   * @param array $element
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected static function getDefaultOperations(array $element) {
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
          'class' => ['office-hours-delete-link', 'office-hours-link'],
        ],
      ];
    }

    // Add 'Copy' link to first slot of each day.
    // First day copies from last day.
    $operations['copy'] = [];
    if ($day_delta == 0) {
      $operations['copy'] = [
        '#type' => 'link',
        '#title' => ($element['#day'] !== OfficeHoursDateHelper::getFirstDay()) && ($day_delta == 0) ? t('Copy previous day') : t('Copy last day'),
        '#weight' => 16,
        '#url' => Url::fromRoute('<front>'),
        // dummy-url, will be catch-ed by javascript.
        '#suffix' => $suffix,
        '#attributes' => [
          'class' => ['office-hours-copy-link', 'office-hours-link'],
        ],
      ];
    }

    // Add 'Add time slot' link to all-but-last slots of each day.
    $operations['add'] = [];
    if ($day_delta < $max_delta) {
      $operations['add'] = [
        '#type' => 'link',
        '#title' => t('Add @node_type', ['@node_type' => t('time slot')]),
        '#weight' => 11,
        '#url' => Url::fromRoute('<front>'),
        // dummy-url, will be catch-ed by javascript.
        '#suffix' => $suffix,
        '#attributes' => [
          'class' => ['office-hours-add-link', 'office-hours-link'],
        ],
      ];
    }

    return $operations;
  }

}
