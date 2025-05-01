<?php

namespace Drupal\office_hours\Plugin\WebformElement;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\office_hours\Plugin\Field\FieldFormatter\OfficeHoursFormatterBase;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'office_hours' element.
 *
 * @WebformElement(
 *   id = "office_hours",
 *   label = @Translation("Office hours"),
 *   description = @Translation("Defines a 'weekly office hours' Webform element"),
 *   category = @Translation("Composite elements"),
 *   composite = TRUE,
 *   multiple = FALSE,
 *   multiline = TRUE,
 *   states_wrapper = TRUE,
 *   dependencies = {
 *     "office_hours",
 *   },
 * )
 *
 * @see \Drupal\office_hours\Element\OfficeHours
 *
 * @todo Fix support for 'required' attribute in Widget.
 * @todo Fix help text, which is now not visible in Widget.
 * @todo Add D11.1 category without translation. https://www.drupal.org/node/3375748
 * @todo Add D8.4 cardinality annotation. @see https://www.drupal.org/node/2869873
 */
class WebformOfficeHours extends WebformCompositeBase {

  /**
   * Static field definitions.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition[]
   */
  protected $fieldDefinitions = [];

  /**
   * Saving the $element, in order to better resemble Widget/Formatter.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition[]
   */
  protected $officeHoursElement = [];

  /**
   * {@inheritdoc}
   *
   * Copied from office_hours\...\OfficeHoursItem\defaultStorageSettings().
   */
  protected function defineDefaultProperties() {
    $formatter_default_settings = OfficeHoursFormatterBase::defaultSettings();
    $widget_default_settings = OfficeHoursItem::defaultStorageSettings();
    $properties = $widget_default_settings
      + $formatter_default_settings
      + parent::defineDefaultProperties();

    unset($properties['multiple__header']);

    return $properties;
  }

  /**
   * {@inheritdoc}
   *
   * Copied from office_hours\...\OfficeHoursItem\storageSettingsForm().
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Add '#afterBuild' to fix 'Limited to 1' setting.
    if (isset($form['element']['multiple'])) {
      $form['element']['multiple']['#after_build'] = [
        [static::class, 'afterBuild'],
      ];
    }

    // Get field overrider from element properties.
    $element_properties = $form_state->get('element_properties');
    $form['office_hours'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Office hours settings'),
    ] + OfficeHoursItem::getStorageSettingsElement($element_properties);

    return $form;
  }

  /**
   * After-build handler for field elements in a form.
   *
   * Make sure the field's cardinality is set correctly.
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    // @todo $element['#disabled'] = TRUE;
    $element['#description'] = t("This is fixed, by this field's nature.");

    $element['container']['cardinality']['#default_value'] = 'number';
    $element['container']['cardinality']['#value'] = 'number';
    unset($element['container']['cardinality']['#options'][-1]);

    $element['container']['cardinality_number']['#default_value'] = 1;
    $element['container']['cardinality_number']['#value'] = 1;
    $element['container']['cardinality_number']['#max'] = 1;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeCompositeElements(array &$element) {
    $element['#webform_composite_elements'] = [];
  }

  /**
   * {@inheritdoc}
   *
   * Copied from office_hours\...\OfficeHoursWeekWidget\formElement().
   */
  public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Save $element to better resemble getFieldDefinition() and getSettings().
    $this->officeHoursElement = $element;

    // Step 1: Get the field name.
    // If the element is not properly defined, do not show the formatter/widget.
    $webform_key = $this->getFieldName();
    if (!$webform_key) {
      return;
    }

    $items = $this->getItemsUnserialized($element, $webform_submission);

    // Step 2: Get the widget.
    /** @var \Drupal\Core\Field\WidgetInterface $widget */
    // @todo Use 'office_hours_exceptions' ID to add Exceptions and Seasons.
    $plugin_id = 'office_hours_default';
    $widget = $items->getWidget($plugin_id, $this->getSettings());
    $form = [];
    $form_state = new FormState();
    $form_element = $widget->formElement($items, 0, $element, $form, $form_state);

    // Step 4: Add widget to webform, to be fetched in validateOfficeHoursSlot().
    $element[$webform_key] = $form_element['value'];
    $element[$webform_key]['#office_hours_widget'] = $widget;

    // @todo Webform #title display defaults to invisible.
    // $element['#title_display'] = 'invisible';
    // @todo Attach below library in Twig template.
    // @see https://www.drupal.org/node/2456753.
    // @see https://www.codimth.com/blog/web/drupal/attaching-library-pages-drupal-8 .
    $element['#attached']['library'][] = 'office_hours/office_hours_webform';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementValidateCallbacks(array &$element, ?WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementValidateCallbacks($element, $webform_submission);
    $class = get_class($this);
    $element['#element_validate'][] = [$class, 'validateOfficeHoursSlot'];
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementPreRenderCallbacks(array &$element, ?WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementPreRenderCallbacks($element, $webform_submission);
    // Replace 'form_element' theme wrapper with composite form element.
    // @see \Drupal\Core\Render\Element\PasswordConfirm
    $element['#pre_render'] = [
      [get_called_class(), 'preRenderWebformCompositeFormElement'],
    ];
  }

  /**
   * Form API callback: Validates one time slot element in Widget.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateOfficeHoursSlot(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // The validation is done by OfficeHoursBaseSlot::validateOfficeHoursSlot().
    // The result may be empty, upon preview in build mode (when not saved).
    $values = $form_state->getValue($element['#webform_key']) ?? [];

    // Fetch the widget object from the form, and massage values.
    $field_name = $element['#webform_key'];
    $widget = $element[$field_name]['#office_hours_widget'];
    $office_hours = $widget->massageFormValues($values, $complete_form, $form_state);

    // Encode Values here always, since this is expected by prepare(),
    // and since postLoad() does not have the values, yet,
    // so we cannot use preSave() unconditionally.
    // There does not seem to exist a Webform equivalent of massageFormValues().
    // $errors = $form_state->getErrors($element); if ($errors !== []) {}.
    $office_hours = self::serialize($office_hours);
    $form_state->setValueForElement($element, $office_hours);
  }

  /**
   * Builds a renderable array for a field value.
   *
   * Copied from office_hours\...\OfficeHoursFormatterDefault\viewElements().
   *
   * @todo Allow to change some Formatter settings via Webform UI.
   * @todo Add configurable $langcode to Formatter.
   *
   * @param \Drupal\Core\Field\FieldItemList $items
   *   The field values to be rendered.
   * @param string|null $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  private function viewElements(FieldItemList $items, ?string $langcode = NULL) {
    /** @var \Drupal\Core\Field\FormatterInterface $formatter */
    $plugin_id = 'office_hours';
    $formatter = $items->getFormatter($plugin_id, $this->originalMode ?? NULL, $this->getSettings());
    $elements = $formatter->viewElements($items, $langcode);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemFormat($element);
    if ($format === 'value') {
      // Save $element to better resemble getFieldDefinition(), getSettings().
      $this->officeHoursElement = $element;
      $items = $this->getItemsUnserialized($element, $webform_submission, $options);
      $build = $this->viewElements($items);
      return $build;
    }
    else {
      return parent::formatHtmlItem($element, $webform_submission, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemFormat($element);
    if ($format === 'value') {
      // Save $element to better resemble getFieldDefinition(), getSettings().
      $this->officeHoursElement = $element;
      $items = $this->getItemsUnserialized($element, $webform_submission, $options);
      $build = $this->viewElements($items);
      $html = \Drupal::service('renderer')->renderPlain($build);
      return trim(MailFormatHelper::htmlToText($html));
    }
    else {
      return parent::formatTextItem($element, $webform_submission, $options);
    }
  }

  /**
   * Gets the plugin settings of a render/form element.
   *
   * Wrapper for easier code reuse from widget, formatter.
   * Note: Prerequisite is: '$this->officeHoursElement = $element;'.
   *
   * @return array
   *   An array of Plugin settings.
   */
  private function getSettings() {
    static $field_settings = NULL;
    if (!$field_settings) {
      // Return Widget settings, reading keys from existing field.
      $formatter_default_settings = OfficeHoursFormatterBase::defaultSettings();
      $widget_default_settings = OfficeHoursItem::defaultStorageSettings();
      $settings = $widget_default_settings + $formatter_default_settings;
      foreach ($settings as $key => $value) {
        $field_settings[$key] = $this->getElementProperty($this->officeHoursElement, $key);
      }
    }
    return $field_settings;
  }

  /**
   * Gets the field definition of a render/form element.
   *
   * Wrapper for easier code reuse from widget, formatter.
   * Note: Prerequisite is: '$this->officeHoursElement = $element;'.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   An Office Hours field definition.
   */
  private function getFieldDefinition() {
    $field_name = $this->getFieldName();
    if ($field_name && !isset($this->fieldDefinitions[$field_name])) {
      $field_type = (string) $this->officeHoursElement['#type'];
      $this->fieldDefinitions[$field_name] = BaseFieldDefinition::create($field_type)
        ->setName($field_name)
        ->setSettings($this->getSettings());
    }
    return $this->fieldDefinitions[$field_name] ?? NULL;
  }

  /**
   * Gets the field name of this webform_element.
   *
   * @return string
   *   An Office Hours field name.
   */
  private function getFieldName() {
    return $this->officeHoursElement['#webform_key'] ?? '';
  }

  /**
   * Extracts the ItemList from the WebformElement.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A Webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList
   *   The list of office_hours items.
   */
  protected function getItemsUnserialized(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $office_hours = $this->getValue($element, $webform_submission, $options) ?? [];
    $items = $this->unserialize($office_hours, $element, $webform_submission);
    return $items;
  }

  /**
   * Encodes Office Hours array to serialized string.
   *
   * Convert Office Hours array to serialized string,
   * since Webform does not support sub-sub components.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Serialized Office hours array.
   */
  private static function serialize(array $office_hours) {
    // Static function, because called from static function.
    $result = [];

    foreach ($office_hours as $key => $value) {
      if (!OfficeHoursItem::isValueEmpty($value)) {
        $result[$key] = \Drupal::service('serialization.phpserialize')
          ->encode($value);
      }
    }
    return $result;

  }

  /**
   * Decodes Office Hours from serialized strings to ItemList.
   *
   * Convert Office Hours array to serialized string,
   * since Webform does not support sub-sub components.
   *
   * @param array $office_hours
   *   Office hours array.
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A Webform submission.
   *
   * @return \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface
   *   An Item list for office hours.
   */
  private function unserialize(array $values, array $element, WebformSubmissionInterface $webform_submission) {
    $office_hours = [];
    foreach ($values as $key => $value) {
      $office_hours[] = \Drupal::service('serialization.phpserialize')
        ->decode($value);
    }

    // Save $element to better resemble getFieldDefinition() and getSettings().
    $this->officeHoursElement = $element;
    $field_name = $this->getFieldName();
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $items */
    $items = \Drupal::typedDataManager()
      ->create($this->getFieldDefinition(),
        $office_hours,
        $field_name,
        $webform_submission->getTypedData()
      );
    $items->filterEmptyItems();
    return $items;
  }

}
