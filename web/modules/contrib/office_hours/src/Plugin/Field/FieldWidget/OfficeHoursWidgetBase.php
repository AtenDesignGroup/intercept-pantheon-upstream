<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\Element\OfficeHoursDatetime;

/**
 * Base class for the 'office_hours_*' widgets.
 */
abstract class OfficeHoursWidgetBase extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Get field settings, to make it accessible for each element in other functions.
    $settings = $this->getFieldSettings();

    $element['#field_settings'] = $settings;
    $element['value'] = [
      '#field_settings' => $settings,
      '#attached' => [
        'library' => [
          'office_hours/office_hours_widget',
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // N.B. The $values are already reformatted in the subWidgets.
    foreach ($values as $key => &$item) {
      // Note: below could better be done in OfficeHoursItemList::filter().
      // However, then we have below error "value '' is not allowed".
      if (OfficeHoursDatetime::isEmpty($item)) {
        unset($values[$key]);
        continue;
      }

      // Get hours. Result can be NULL, '', 0000, or a proper time.
      $start = OfficeHoursDatetime::get($item['starthours'], 'Hi');
      $end = OfficeHoursDatetime::get($item['endhours'], 'Hi');
      // Cast the time to integer, to avoid core's error
      // "This value should be of the correct primitive type."
      // This is needed for e.g., 0000 and 0030.
      $item['starthours'] = isset($start) ? (int) $start : '';
      $item['endhours'] = isset($end) ? (int) $end : '';
      // Allow Empty time field with comment (#2070145).
      // In principle, this is prohibited by the database: value '' is not
      // allowed. The format is int(11).
      // Would changing the format to 'string' help?
      // Perhaps, but using '-1' (saved as '-001') works, too.
      if (!empty($item['comment'])) {
        $item['starthours'] = empty($start) ? -1 : $item['starthours'];
        $item['endhours'] = empty($start) ? -1 : $item['endhours'];
      }
    }
    return $values;
  }

}
