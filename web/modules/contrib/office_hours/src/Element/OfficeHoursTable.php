<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Table;

/**
 * Provides a render element for a table.
 *
 * @FormElement("office_hours_table")
 */
class OfficeHoursTable extends Table {

  /**
   * @inheritdoc
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // if ($input) {
    //    // Let child elements set start_hours and end_hours via their valueCallback.
    //   foreach($input as $key => $value) {
    //      unset($input[$key]['start_hours']);
    //      unset($input[$key]['end_hours']);
    //   }
    // }
    $input = parent::valueCallback($element, $input, $form_state);

    return $input;
  }

}
