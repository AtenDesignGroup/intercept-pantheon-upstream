<?php

namespace Drupal\clientside_validation;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Utility\NestedArray;

/**
 * Implement Class CvValidatorBase.
 *
 * @package Drupal\clientside_validation
 */
abstract class CvValidatorBase extends PluginBase implements CvValidatorInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * Get the validation rules for this form element.
   *
   * @return array
   *   An array with following keys:
   *     - rules: An array with the rulename as key and the rule arguments as
   *       value.
   *     - messages: An array with the rulename as key and the message for this
   *       rule as argument.
   */
  abstract protected function getRules($element, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   */
  public function addValidation(array &$element, FormStateInterface $form_state) {
    $rules = $this->getRules($element, $form_state);
    if (isset($rules['rules'])) {
      foreach ($rules['rules'] as $rulename => $rulearg) {
        $element['#attributes']['data-rule-' . mb_strtolower($rulename)] = is_object($rulearg) || is_array($rulearg) ? Json::encode($rulearg) : $rulearg;
      }
    }
    if (isset($rules['messages'])) {
      foreach ($rules['messages'] as $rulename => $message) {
        $element['#attributes']['data-msg-' . mb_strtolower($rulename)] = $message;
      }
    }
    if (isset($rules['messages']) || isset($rules['rules'])) {
      if (!isset($element['#attached'])) {
        $element['#attached'] = [];
      }
      $element['#attached'] = NestedArray::mergeDeep($element['#attached'], $this->getPluginDefinition()['attachments']);
    }
  }

  /**
   * Get the value of an attribute of an element.
   *
   * @param array $element
   *   The element to get the attribute from.
   * @param string $attribute
   *   The attribute of which to get the value.
   *
   * @return mixed
   *   The attribute value.
   */
  protected function getAttributeValue(array $element, $attribute) {
    return $element['#' . $attribute] ?? ($element['#attributes'][$attribute] ?? NULL);
  }

  /**
   * Get title for form element.
   *
   * @param array $element
   *   Element.
   * @param string $default
   *   Default value for the title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|mixed
   *   Title value.
   */
  protected function getElementTitle(array $element, $default = '') {

    $title = $this->getAttributeValue($element, 'title');

    if (empty($title) && empty($default)) {
      return $this->t('This field');
    }

    return $title ? $title : $default;
  }

}
