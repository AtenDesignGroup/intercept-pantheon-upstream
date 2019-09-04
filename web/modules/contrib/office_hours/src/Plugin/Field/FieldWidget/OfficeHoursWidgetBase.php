<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Base class for the 'office_hours_*' widgets.
 */
abstract class OfficeHoursWidgetBase extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // N.B. The $values are already reformatted in the subWidgets.

    foreach ($values as $key => &$item) {
      // Numeric value is set in OfficeHoursDateList/Datetime::validateOfficeHours()
      $start = isset($item['starthours']['time']) ? $item['starthours']['time'] : $item['starthours'];
      $end   = isset($item['endhours']['time'])   ? $item['endhours']['time']   : $item['endhours'];

      // Cast the time to integer, to avoid core's error
      // "This value should be of the correct primitive type."
      // This is needed for e.g., 0000 and 0030.
      $item['starthours'] = (int) OfficeHoursDateHelper::format($start, 'Hi');
      $item['endhours'] = (int) OfficeHoursDateHelper::format($end, 'Hi');
      // #2070145: Allow Empty time field with comment.
      // In principle, this prohibited by the database: value '' is not
      // allowed. The format is int(11).
      // Would changing the format to 'string' help?
      // Perhaps, but using '-1' (saved as '-001') works, too.
      if (empty($start) && !empty($item['comment'])) {
        $item['starthours'] = -1;
      }
      if (empty($end) && !empty($item['comment'])) {
        $item['endhours'] = -1;
      }

      // Note: below could better be done in OfficeHoursItemList::filter().
      // However, then we have below error "value '' is not allowed".
      if (empty($start) && empty($end) && empty($item['comment'])) {
        unset($values[$key]);
      }
    }

    return $values;
  }

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

}
