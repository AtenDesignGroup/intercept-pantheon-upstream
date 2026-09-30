<?php

namespace Drupal\charts\Element;

use Drupal\Core\Render\Element\RenderElementBase;

/**
 * Provides a chart render element.
 */
abstract class ChartAxisBase extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      // Options: linear, logarithmic, datetime, labels.
      '#axis_type' => '',
      '#title' => '',
      '#title_color' => '#000',
      // Options: normal, bold.
      '#title_font_weight' => 'normal',
      // Options: normal, italic.
      '#title_font_style' => 'normal',
      '#title_font_size' => 12,
      // CSS value for font size, e.g. 1em or 12px.
      '#labels' => NULL,
      '#labels_color' => '#000',
      // Options: normal, bold.
      '#labels_font_weight' => 'normal',
      // Options: normal, italic.
      '#labels_font_style' => 'normal',
      // CSS value for font size, e.g. 1em or 12px.
      '#labels_font_size' => NULL,
      // Integer rotation value, e.g. 30, -60 or 90.
      '#labels_rotation' => NULL,
      '#grid_line_color' => '#ccc',
      '#base_line_color' => '#ccc',
      '#minor_grid_line_color' => '#e0e0e0',
      // Integer max value on this axis.
      '#max' => NULL,
      // Integer minimum value on this axis.
      '#min' => NULL,
      // Display axis on opposite normal side.
      '#opposite' => FALSE,
      // An array of plot lines drawn across the plot area at fixed values on
      // this axis. Attach lines to a chart_yaxis element for horizontal lines
      // and to a chart_xaxis element for vertical lines. Each item is an
      // associative array with the following keys:
      // - value: (float|int) Required. The axis value the line is drawn at.
      //   On a categorical x-axis this is the zero-based category index.
      // - label: (string) Optional label rendered alongside the line.
      // - color: (string) Optional hexadecimal color. Not supported by every
      //   charting library (e.g. C3.js and Billboard.js ignore it).
      '#plot_lines' => [],
      // Allows properties or options not coded in the Charts module.
      '#raw_options' => [],
    ];
  }

}
