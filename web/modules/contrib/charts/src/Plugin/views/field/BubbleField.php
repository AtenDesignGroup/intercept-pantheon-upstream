<?php

namespace Drupal\charts\Plugin\views\field;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\charts\ChartViewsFieldInterface;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @file
 * Defines Drupal\charts\Plugin\views\field\BubbleField.
 */
/**
* Defines a Views field for bubble chart data.
 */
#[ViewsField("field_charts_fields_bubble")]
class BubbleField extends FieldPluginBase implements ContainerFactoryPluginInterface, ChartViewsFieldInterface {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a \Drupal\views\Plugin\Block\ViewsBlockBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger')
    );
  }

  /**
   * Sets the initial field data at zero.
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $this->field_alias = 'bubble_field';
    $options['fieldset_one']['default'] = NULL;
    $options['fieldset_two']['default'] = NULL;
    $options['fieldset_three']['default'] = NULL;
    $options['fieldset_one']['x_axis'] = ['default' => NULL];
    $options['fieldset_two']['y_axis'] = ['default' => NULL];
    $options['fieldset_three']['z_axis'] = ['default' => NULL];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $fieldList = $this->displayHandler->getFieldLabels();
    unset($fieldList['field_charts_fields_bubble']);

    $form['fieldset_one'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select the field representing the X axis.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#weight' => -10,
      '#required' => TRUE,
    ];
    $form['fieldset_one']['x_axis'] = [
      '#type' => 'radios',
      '#title' => $this->t('X Axis Field'),
      '#options' => $fieldList,
      '#default_value' => $this->options['fieldset_one']['x_axis'],
      '#weight' => -10,
    ];

    $form['fieldset_two'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Select the field representing the Y axis.'),
      '#weight' => -9,
      '#required' => TRUE,
    ];
    $form['fieldset_two']['y_axis'] = [
      '#type' => 'radios',
      '#title' => $this->t('Y Axis Field'),
      '#options' => $fieldList,
      '#default_value' => $this->options['fieldset_two']['y_axis'],
      '#weight' => -9,
    ];

    $form['fieldset_three'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Select the field representing the Z axis.'),
      '#weight' => -9,
      '#required' => TRUE,
    ];
    $form['fieldset_three']['z_axis'] = [
      '#type' => 'radios',
      '#title' => $this->t('Z Axis Field'),
      '#options' => $fieldList,
      '#default_value' => $this->options['fieldset_three']['z_axis'],
      '#weight' => -9,
    ];

    return $form;
  }

  /**
   * Get the value of a simple math field.
   *
   * @param \Drupal\views\ResultRow $values
   *   Row results.
   * @param string $fieldset
   *   The items fieldset.
   * @param string $axis
   *   Whether we are fetching field one's value.
   *
   * @return mixed
   *   The field value.
   *
   * @throws \Exception
   */
  protected function getFieldValue(ResultRow $values, $fieldset, $axis) {

    $field = $this->options[$fieldset][$axis];

    $data = NULL;

    // Fetch the data from the database alias.
    if (isset($this->view->field[$field])) {
      if ($field == 'bubble_field') {
        $data = $this->view->field['field_charts_fields_bubble']->getValue($values);
      }
      else {
        $data = $this->view->field[$field]->getValue($values);
      }
    }

    if (!isset($data)) {
      // There's no value. Default to 0.
      $data = 0;
    }

    // Ensure the input is numeric.
    if (!empty($data) && !is_numeric($data)) {
      $this->messenger->addError($this->t('Check the formatting of your
        Bubble Field inputs: one or both of them are not numeric.'));
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function getValue(ResultRow $values, $field = NULL) {
    parent::getValue($values, $field);

    $xAxisFieldValue = $this->getFieldValue($values, 'fieldset_one', 'x_axis');
    $yAxisFieldValue = $this->getFieldValue($values, 'fieldset_two', 'y_axis');
    $zAxisFieldValue = $this->getFieldValue($values, 'fieldset_three', 'z_axis');

    return Json::encode([
      Json::decode($xAxisFieldValue),
      Json::decode($yAxisFieldValue),
      Json::decode($zAxisFieldValue),
    ]);
  }

  /**
   * Set the data type for the chart field to be an array.
   *
   * @return string
   *   The data type.
   */
  public function getChartFieldDataType(): string {
    return 'array';
  }

}
