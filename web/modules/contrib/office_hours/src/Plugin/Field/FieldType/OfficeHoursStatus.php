<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Component\Utility\Html;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for displaying the open/closed status of a field.
 *
 * @see OfficeHoursItemBase~propertyDefinitions()
 *
 * @FieldType(
 *   id = "office_hours_status",
 *   label = @Translation("Office hours status"),
 *   description = @Translation("Status: open/closed/never."),
 *   default_formatter = "office_hours_status",
 *   no_ui = TRUE,
 * )
 */
class OfficeHoursStatus extends TypedData implements OptionsProviderInterface {
  // @todo #3501772 Convert to complex datatype with key/value formatter.
  // ItemList, MapItem, Map, TypedData {
  //
  public const CLOSED = 0;
  public const OPEN = 1;
  public const NEVER = 2;

  /**
   * Cached open/closed status.
   *
   * @var int
   */
  protected $value = FALSE;

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getValue().
   */
  public function getValue() {
    if (!isset($this->value)) {
      $items = $this->getParent()->getParent();

      switch (TRUE) {
        case $items === NULL:
        case $items->isEmpty():
          $status = OfficeHoursStatus::NEVER;
          break;

        default:
          $time = 0;
          $status = $items->isOpen($time);
          break;
      }
      $this->setValue((int) $status);
    }
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(?AccountInterface $account = NULL, array $formatter_settings = []) {
    return array_keys($this->getPossibleOptions($account, $formatter_settings));
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(?AccountInterface $account = NULL, array $formatter_settings = []) {
    return $this->getSettableOptions($account, $formatter_settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(?AccountInterface $account = NULL, array $formatter_settings = []) {
    return array_keys($this->getSettableOptions($account, $formatter_settings));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(?AccountInterface $account = NULL, array $formatter_settings = []) {
    return $this->getOptions($account, $formatter_settings);
  }

  /**
   * A work-around to not have to instantiate a TypedData object.
   *
   * @param mixed $account
   *   An optional user account.
   * @param array $formatter_settings
   *   The formatter settings with text values for each key.
   *
   * @return array
   *   An array of key-value pairs with status options for ItemList.
   */
  public static function getOptions(?AccountInterface $account = NULL, array $formatter_settings = []) {
    // @todo Avoid passing $formatter_settings.
    $settings = $formatter_settings;
    return [
      OfficeHoursStatus::OPEN => t(
        Html::escape($settings['current_status']['open_text'] ?? 'Open now'),
        [],
        [
          'langcode' => $account ? $account->getPreferredLangcode() : NULL,
          'context' => 'office_hours',
        ]
      ),
      OfficeHoursStatus::CLOSED => t(
        Html::escape($settings['current_status']['closed_text'] ?? 'Temporarily closed'),
        [],
        [
          'langcode' => $account ? $account->getPreferredLangcode() : NULL,
          'context' => 'office_hours',
        ]
      ),
      OfficeHoursStatus::NEVER => t(
        Html::escape($settings['closed_format'] ?? 'Permanently closed'),
        [],
        [
          'langcode' => $account ? $account->getPreferredLangcode() : NULL,
          'context' => 'office_hours',
        ]
      ),
    ];
  }

}
