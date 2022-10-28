<?php

namespace Drupal\intercept_core\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLinkEdit;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("customer_edit_link")
 */
class CustomerEditLink extends EntityLinkEdit {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'customer-form';
  }

}
