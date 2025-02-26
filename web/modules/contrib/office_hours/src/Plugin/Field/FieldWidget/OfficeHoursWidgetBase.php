<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\office_hours\OfficeHoursSeason;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for the 'office_hours_*' widgets.
 */
abstract class OfficeHoursWidgetBase extends WidgetBase {

  /**
   * A warning.
   */
  protected const MESSAGE_TYPE = MessengerInterface::TYPE_WARNING;

  /**
   * The season data. Can only be changed in SeasonWidget, not WeekWidget.
   *
   * @var \Drupal\office_hours\OfficeHoursSeason
   */
  protected $season;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, MessengerInterface $messenger) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->messenger = $messenger;
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
      $configuration['third_party_settings'],
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['value'] = [
      // Add field settings, for usage in each Element.
      // @todo Still needed? Check correct usage in each Widget type.
      '#field_settings' => $this->getFieldSettings(),
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
    // Remove 'value' of the 'Add exception' button.
    unset($values['add_more']);

    $values = parent::massageFormValues($values, $form, $form_state);

    return $values;
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
   * Get the season for this Widget.
   *
   * @return \Drupal\office_hours\OfficeHoursSeason
   *   The season.
   */
  public function getSeason() {
    // Use season, or normal Weekdays (empty season).
    $this->season = $this->season ?? new OfficeHoursSeason();
    return $this->season;
  }

  /**
   * Set the season for this WeekWidget (0-6 is the regular week).
   *
   * @param \Drupal\office_hours\OfficeHoursSeason $season
   *   The season.
   *
   * @return \Drupal\office_hours\Plugin\Field\FieldWidget\OfficeHoursWidgetBase
   *   The widget object itself.
   */
  public function setSeason(?OfficeHoursSeason $season = NULL) {
    $this->season = $season;
    return $this;
  }

  /**
   * Adds a message to the user, when widget does not support items.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item
   *   An OfficeHoursItem.
   */
  protected function addMessage(OfficeHoursItem $item) {

    // First, set a general message. It will not be repeated on screen.
    // Then, add a date-specific message.
    $message1 = $this->t('This widget does not support seasons and exceptions.
       Please inform your administrator.
       Your data will be lost when saving this form.');

    $settings['day_format'] = 'long';
    $label = $item->label($settings);

    switch (TRUE) {
      case $item->isSeasonHeader():
        $message2 = $this->t(
          'Season %label is removed from the list.',
          ['%label' => $label]
        );
        break;

      case $item->isSeasonDay():
        // Weekdays are OK. No message.
        return;

      case $item->isWeekDay():
        // Season days are already processed with the season header.
        return;

      case $item->isExceptionDay():
        $message2 = $this->t(
          'Exception %label is removed from the list.',
          ['%label' => $label]
        );
        break;
    }

    $this->messenger->addMessage($message1, self::MESSAGE_TYPE);
    $this->messenger->addMessage($message2, self::MESSAGE_TYPE);
  }

}
