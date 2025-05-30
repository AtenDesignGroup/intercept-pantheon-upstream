<?php

namespace Drupal\video_embed_field_mock_provider\Plugin\video_embed_field\Provider;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\video_embed_field\ProviderPluginInterface;

/**
 * A mock video provider for use in tests.
 *
 * @VideoEmbedProvider(
 *   id = "mock",
 *   title = @Translation("Mock Provider")
 * )
 */
class MockProvider implements ProviderPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input) {
    return strpos($input, 'example.com') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function renderThumbnail($image_style, $link_url) {
    return [
      '#markup' => 'Mock provider thumbnail.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay, $title_format = NULL, $use_title_fallback = TRUE) {
    return [
      '#markup' => 'Mock provider embed code.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalThumbnailUri() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function downloadThumbnail() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    return $input;
  }

  /**
   * {@inheritdoc}
   */
  public function getName($title_format = NULL, $use_title_fallback = TRUE) {
    return $title_format ? new FormattableMarkup($title_format, [
      '@provider' => $this->getPluginDefinition()['title'],
      '@title' => 'Video Name',
    ]) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'foo';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return [];
  }

}
