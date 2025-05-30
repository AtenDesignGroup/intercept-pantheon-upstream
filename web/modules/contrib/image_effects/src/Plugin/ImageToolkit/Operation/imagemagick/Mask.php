<?php

declare(strict_types=1);

namespace Drupal\image_effects\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\image_effects\Plugin\ImageToolkit\Operation\MaskTrait;
use Drupal\imagemagick\PackageSuite;
use Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick\ImagemagickImageToolkitOperationBase;

/**
 * Defines ImageMagick Mask operation.
 */
#[ImageToolkitOperation(
  id: 'image_effects_imagemagick_mask',
  toolkit: 'imagemagick',
  operation: 'mask',
  label: new TranslatableMarkup('Mask'),
  description: new TranslatableMarkup('Applies a mask to the source image.'),
)]
class Mask extends ImagemagickImageToolkitOperationBase {

  use MaskTrait;

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    if ($this->getToolkit()->getExecManager()->getPackageSuite() === PackageSuite::Graphicsmagick) {
      // GraphicsMagick does not support this operation, return early.
      // @todo implement a GraphicsMagick solution if possible.
      return FALSE;
    }

    // Mask image local path.
    $local_path = $arguments['mask_image']->getToolkit()->ensureSourceLocalPath();
    if ($local_path !== '') {
      $image_path = $local_path;
    }
    else {
      $source_path = $arguments['mask_image']->getToolkit()->getSource();
      throw new \InvalidArgumentException("Missing local path for image at {$source_path}");
    }

    // Set the dimensions of the mask. Use of the scale option means that we
    // need to change the dimensions: always set them, they don't harm when the
    // scale option is not used.
    $w = $arguments['mask_width'] ?: $arguments['mask_image']->getToolkit()->getWidth();
    $h = $arguments['mask_height'] ?: $arguments['mask_image']->getToolkit()->getHeight();

    // Set offset. Offset arguments require a sign in front.
    $x = $arguments['x_offset'] >= 0 ? ('+' . $arguments['x_offset']) : $arguments['x_offset'];
    $y = $arguments['y_offset'] >= 0 ? ('+' . $arguments['y_offset']) : $arguments['y_offset'];

    $this->addArguments([
      "-gravity",
      "None",
      $image_path,
      "-geometry",
      "{$w}x{$h}!{$x}{$y}",
      "-alpha",
      "Off",
      "-compose",
      "CopyOpacity",
      "-composite",
      "-gravity",
      "none",
      "-background",
      "transparent",
      "-compose",
      "src-over",
      "-extent",
      "{$this->getToolkit()->getWidth()}x{$this->getToolkit()->getHeight()}",
    ]);
    return TRUE;
  }

}
