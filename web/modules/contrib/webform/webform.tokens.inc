<?php

/**
 * @file
 * Builds placeholder replacement tokens for webforms and submissions.
 */

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Hook\WebformTokensHooks;
use Drupal\webform\Plugin\WebformElement\WebformComputedBase;
use Drupal\webform\Plugin\WebformElement\WebformMarkupBase;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformLogicHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Implements hook_token_info().
 */
#[LegacyHook]
function webform_token_info() {
  return \Drupal::service(WebformTokensHooks::class)->tokenInfo();
}

/**
 * Implements hook_tokens().
 */
#[LegacyHook]
function webform_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  return \Drupal::service(WebformTokensHooks::class)->tokens($type, $tokens, $data, $options, $bubbleable_metadata);
}

/**
 * Get webform submission token value.
 *
 * @param string $value_token
 *   A [webform_submission:value:?] token.
 * @param array $options
 *   An array of token options.
 * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
 *   A webform submission.
 * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
 *   The webform element manager.
 * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
 *   (optional) Object to collect route processors' bubbleable metadata.
 *
 * @return \Drupal\Component\Render\MarkupInterface|string
 *   Webform submission token value.
 */
function _webform_token_get_submission_value($value_token, array $options, WebformSubmissionInterface $webform_submission, WebformElementManagerInterface $element_manager, BubbleableMetadata $bubbleable_metadata) {
  $submission_data = $webform_submission->getData();

  // phpcs:disable Drupal.Commenting.InlineComment.InvalidEndChar
  // Formats:
  // [html]
  // [values:{element_key}:{format}]
  // [values:{element_key}:{format}:{items}]
  // [values:{element_key}:{format}:html]
  // [values:{element_key}:{format}:{items}:html]
  // [values:{element_key}:{format}:urlencode]
  // [values:{element_key}:{format}:{items}:urlencode]
  // [values:{element_key}:{delta}:{format}]
  // [values:{element_key}:{delta}:{sub-element}]
  // phpcs:enable Drupal.Commenting.InlineComment.InvalidEndChar
  $keys = explode(':', $value_token);
  $element_key = array_shift($keys);

  // Build HTML values.
  if ($element_key === 'html' && empty($keys)) {
    $options['html'] = TRUE;
    return _webform_token_get_submission_values($options, $webform_submission);
  }

  // Set default options.
  $options += [
    'html' => FALSE,
  ];

  // Parse suffixes and set options.
  $suffixes = [
    // Indicates the tokens should be formatted as HTML instead of plain text.
    'html',
  ];
  foreach ($suffixes as $suffix) {
    if ($keys && in_array($suffix, $keys)) {
      $keys = array_diff($keys, [$suffix]);
      $options[$suffix] = TRUE;
    }
  }

  $element = $webform_submission->getWebform()->getElement($element_key, TRUE);

  // Exit if form element does not exist.
  if (!$element) {
    return NULL;
  }

  $element_plugin = $element_manager->getElementInstance($element);

  // Always get value for a computed element.
  if ($element_plugin instanceof WebformComputedBase) {
    return $element_plugin->getValue($element, $webform_submission);
  }

  // Always get rendered markup for a markup element.
  if ($element_plugin instanceof WebformMarkupBase) {
    $format_method = (empty($options['html'])) ? 'buildText' : 'buildHtml';
    $element['#display_on'] = WebformMarkupBase::DISPLAY_ON_BOTH;
    $token_value = $element_manager->invokeMethod($format_method, $element, $webform_submission, $options);
    return \Drupal::service('renderer')->renderInIsolation($token_value);
  }

  // Exit if no submission data and form element is not a container.
  if (!isset($submission_data[$element_key]) && !$element_plugin->isContainer($element)) {
    return NULL;
  }

  // If multiple value element look for delta.
  if ($keys && $element_plugin->hasMultipleValues($element) && is_numeric($keys[0])) {
    $delta = array_shift($keys);
    $options['delta'] = $delta;
  }
  else {
    $delta = NULL;
  }

  // If composite element look for sub-element key.
  if ($keys && $element_plugin->isComposite() && method_exists($element_plugin, 'getInitializedCompositeElement') && $element_plugin->getInitializedCompositeElement($element, $keys[0])) {
    $composite_key = array_shift($keys);
    $options['composite_key'] = $composite_key;
  }
  else {
    $composite_key = NULL;
  }

  /* ************************************************************************ */
  // Get value.
  /* ************************************************************************ */

  // Set entity reference chaining.
  if ($keys && $keys[0] === 'entity' && $element_plugin instanceof WebformElementEntityReferenceInterface) {
    // Remove entity from keys.
    array_shift($keys);

    // Get entity value, type, instance, and token.
    if ($entity = $element_plugin->getTargetEntity($element, $webform_submission, $options)) {
      $entity_type = $entity->getEntityTypeId();
      // Map entity type id to entity token name.
      $entity_token_names = [
        // Taxonomy tokens are not prefixed with 'taxonomy_'.
        // @see taxonomy_token_info()
        'taxonomy_term' => 'term',
        'taxonomy_vocabulary' => 'vocabulary',
      ];
      $entity_token_name = $entity_token_names[$entity_type] ?? $entity_type;
      $entity_token = implode(':', $keys);
      $token_value = Markup::create(\Drupal::token()->replace(
        "[$entity_token_name:$entity_token]",
        [$entity_token_name => $entity],
        $options,
        $bubbleable_metadata
      ));
      return $token_value;
    }
    else {
      return '';
    }
  }

  // Set checked/selected for an options elements.
  if ($keys && in_array($keys[0], ['checked', 'selected']) && $element_plugin->hasProperty('options')) {
    $token_values = (array) $element_plugin->getValue($element, $webform_submission);
    return ($token_values && in_array($keys[1], $token_values)) ? '1' : '0';
  }

  // Set format and items format.
  if ($keys) {
    if ($composite_key) {
      // Must set '#webform_composite_elements' format.
      // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::initialize
      // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::getInitializedCompositeElement
      $element['#webform_composite_elements'][$composite_key]['#format'] = array_shift($keys);
    }
    else {
      $element['#format'] = array_shift($keys);
    }
  }
  if ($keys) {
    $element['#format_items'] = array_shift($keys);
  }

  $token = "[webform_submission:values:$value_token]";
  if (WebformLogicHelper::startRecursionTracking($token) === FALSE) {
    return '';
  }

  $format_method = (empty($options['html'])) ? 'formatText' : 'formatHtml';
  $token_value = $element_manager->invokeMethod($format_method, $element, $webform_submission, $options);
  if (is_array($token_value)) {
    // Note, tokens can't include CSS and JS libraries since they will
    // can be included in an email.
    $token_value = \Drupal::service('renderer')->renderInIsolation($token_value);
  }
  elseif (isset($element['#format']) && $element['#format'] === 'raw') {
    // Make sure raw tokens are always rendered AS-IS.
    $token_value = Markup::create((string) $token_value);
  }
  elseif (!($token_value instanceof MarkupInterface)) {
    // All strings will be escaped as HtmlEscapedText.
    // @see \Drupal\Core\Utility\Token::replace
    // @see \Drupal\Component\Render\HtmlEscapedText
    $token_value = (string) $token_value;
  }

  if (WebformLogicHelper::stopRecursionTracking($token) === FALSE) {
    return '';
  }

  return $token_value;
}

/**
 * Get webform submission values.
 *
 * @param array $options
 *   An array of token options.
 * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
 *   A webform submission.
 *
 * @return \Drupal\Component\Render\MarkupInterface|string
 *   Webform submission values.
 */
function _webform_token_get_submission_values(array $options, WebformSubmissionInterface $webform_submission) {
  $token = (!empty($options['html'])) ? '[webform_submission:values:html]' : '[webform_submission:values]';

  if (WebformLogicHelper::startRecursionTracking($token) === FALSE) {
    return '';
  }

  $submission_format = (!empty($options['html'])) ? 'html' : 'text';
  /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
  $form_elements = $webform_submission->getWebform()->getElementsInitialized();
  $token_value = $view_builder->buildElements($form_elements, $webform_submission, $options, $submission_format);

  // Note, tokens can't include CSS and JS libraries since they can be
  // included in an email.
  $value = \Drupal::service('renderer')->renderInIsolation($token_value);

  if (WebformLogicHelper::stopRecursionTracking($token) === FALSE) {
    return '';
  }

  return $value;
}

/**
 * Render webform more element (slideouts) for token descriptions.
 *
 * @param string $more_title
 *   More title.
 * @param string $more
 *   More content.
 *
 * @return string
 *   Rendered webform more element.
 */
function _webform_token_render_more($more_title, $more) {
  $build = [
    '#type' => 'webform_more',
    '#more' => $more,
    '#more_title' => $more_title,
  ];

  // Token info might be called via CLI and not all modules are loaded
  // or an active theme is defined.
  //
  // Prevent the below exceptions:
  // - The theme implementations may not be rendered until all modules
  //   are loaded.
  // - Call to a member function setParser() on array in Twig\Parser->parse().
  //
  // @see \Drupal\Core\Theme\ThemeManager::render
  /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
  $module_handler = \Drupal::service('module_handler');
  /** @var \Drupal\webform\WebformThemeManagerInterface $theme_manager */
  $theme_manager = \Drupal::service('webform.theme_manager');
  if (!$module_handler->isLoaded() || !$theme_manager->hasActiveTheme()) {
    return '';
  }

  return (string) \Drupal::service('renderer')->renderInIsolation($build);
}

/**
 * Get interval wait time.
 *
 * @param string $interval_setting
 *   Interval setting name.
 * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
 *   (optional) Object to collect path processors' bubbleable metadata.
 * @param \Drupal\webform\WebformInterface|null $webform
 *   (optional) A webform. If set the total number of submissions for the
 *   Webform will be returned.
 * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
 *   (optional) A webform submission source entity.
 * @param \Drupal\Core\Session\AccountInterface|null $account
 *   (optional) A user account.
 *
 * @return string
 *   Formatted interval wait time.
 */
function _webform_token_get_interval_wait($interval_setting, BubbleableMetadata $bubbleable_metadata, ?WebformInterface $webform = NULL, ?EntityInterface $source_entity = NULL, ?AccountInterface $account = NULL) {
  // Get last submission completed time.
  /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
  $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
  $options = ['access_check' => FALSE];
  $last_submission = $submission_storage->getLastSubmission($webform, $source_entity, $account, $options);
  if (!$last_submission) {
    return '';
  }

  $completed_time = $last_submission->getCompletedTime();

  // Get interval.
  $interval = $webform->getSetting($interval_setting);

  // Get wait time.
  $wait_time = ($completed_time + $interval);

  // Get request time.
  $request_time = \Drupal::request()->server->get('REQUEST_TIME');

  // Set cache max age.
  $max_age = ($wait_time - $request_time);
  $bubbleable_metadata->setCacheMaxAge(($max_age > 0) ? $max_age : 0);

  // Format time diff until next allows submission.
  /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
  $date_formatter = \Drupal::service('date.formatter');
  return $date_formatter->formatTimeDiffUntil($wait_time);
}
