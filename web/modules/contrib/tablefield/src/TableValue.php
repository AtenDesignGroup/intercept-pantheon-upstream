<?php

namespace Drupal\tablefield;

use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for Search API indexing.
 */
class TableValue extends TypedData {

  /**
   * Array of values.
   *
   * @var array
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    /** @var \Drupal\tablefield\Plugin\Field\FieldType\TablefieldItem $item */
    $item = $this->getParent();
    $value = '';
    if (isset($item->value)) {
      foreach ($item->value as $row) {
        if (is_array($row)) {
          $value .= implode(' ', $row) . ' ';
        }
        elseif (is_string($row)) {
          $value .= ' ' . $row . ' ';
        }
      }
      $value = trim($value);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
