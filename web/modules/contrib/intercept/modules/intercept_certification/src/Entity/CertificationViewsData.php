<?php

namespace Drupal\intercept_certification\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Certification entities.
 */
class CertificationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
