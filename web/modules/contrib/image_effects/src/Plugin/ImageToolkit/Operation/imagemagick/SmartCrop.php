<?php

namespace Drupal\image_effects\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\image_effects\Plugin\ImageToolkit\Operation\gd\GDOperationTrait;
use Drupal\image_effects\Plugin\ImageToolkit\Operation\SmartCropTrait;
use Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick\ImagemagickImageToolkitOperationBase;

/**
 * Defines Imagemagick Scale and Smart Crop operation.
 *
 * @ImageToolkitOperation(
 *   id = "image_effects_imagemagick_smart_crop",
 *   toolkit = "imagemagick",
 *   operation = "smart_crop",
 *   label = @Translation("Smart Crop"),
 *   description = @Translation("Similar to Crop, but preserves the portion of the image with the most entropy.")
 * )
 */
class SmartCrop extends ImagemagickImageToolkitOperationBase {

  use SmartCropTrait;
  use GDOperationTrait;

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments = []) {
    $file_system = \Drupal::service('file_system');
    $image_factory = \Drupal::service('image.factory');

    // Convert the image to disk at the current state, and reopen it using the
    // GD toolkit to allow determining the crop rectangle.
    $temp_path = $file_system->tempnam('temporary://', 'image_effects_');
    $current_destination_format = $this->getToolkit()->arguments()->getDestinationFormat();
    $this->getToolkit()->arguments()->setDestinationFormatFromExtension('png');
    $this->getToolkit()->save($temp_path);
    $this->getToolkit()->arguments()->setDestinationFormat($current_destination_format);

    $temp_image = $image_factory->get($temp_path, 'gd');
    /** @var \Drupal\system\Plugin\ImageToolkit\GDToolkit $temp_toolkit */
    $temp_toolkit = $temp_image->getToolkit();
    $rect = match ($arguments['algorithm']) {
      'entropy_slice' => $this->getEntropyCropBySlicing($temp_toolkit->getResource(), $arguments['width'], $arguments['height']),
      'entropy_grid' => $this->getEntropyCropByGridding($temp_toolkit->getResource(), $arguments['width'], $arguments['height'], $arguments['simulate'], $arguments['algorithm_params']['grid_width'], $arguments['algorithm_params']['grid_height'], $arguments['algorithm_params']['grid_rows'], $arguments['algorithm_params']['grid_cols'], $arguments['algorithm_params']['grid_sub_rows'], $arguments['algorithm_params']['grid_sub_cols']),
    };
    $points = $this->getRectangleCorners($rect);

    // Do not need the temporary image file any longer.
    $file_system->unlink($temp_path);

    // Crop the image using the coordinates found above. If simulating, draw
    // a marker on the image instead.
    if (!$arguments['simulate']) {
      return $this->getToolkit()->apply('crop', [
        'x' => $points[6],
        'y' => $points[7],
        'width' => $rect->getWidth(),
        'height' => $rect->getHeight(),
      ]);
    }
    else {
      $rect->translate([-2, -2]);
      for ($i = -2; $i <= 2; $i++) {
        $this->getToolkit()->apply('draw_rectangle', [
          'rectangle' => $rect,
          'border_color' => $i !== 0 ? '#00FF00FF' : '#FF0000FF',
        ]);
        $rect->translate([1, 1]);
      }
      for ($i = 0; $i < 8; $i += 2) {
        $this->getToolkit()->apply('draw_ellipse', [
          'cx' => $points[$i],
          'cy' => $points[$i + 1],
          'width' => 6,
          'height' => 6,
          'color' => '#FF0000FF',
        ]);
      }
    }

    return TRUE;
  }

}
