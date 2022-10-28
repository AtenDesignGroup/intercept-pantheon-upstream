<?php

namespace Drupal\intercept_profile_copyright\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "intercept_profile_copyright",
 *   admin_label = @Translation("Copyright block"),
 * )
 */
class Copyright extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('system.site');
    $site_name = $config->get('name');

    $output = '&copy;&nbsp;' . date('Y') . ' ' . $site_name;

    return [
      '#markup' => $output,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['intercept_profile_copyright_settings'] = $form_state->getValue('intercept_profile_copyright_settings');
  }

}
