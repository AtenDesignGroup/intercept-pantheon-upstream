<?php

namespace Drupal\flag\Hook;

use Drupal\system\Entity\Action;
use Drupal\user\UserInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\flag\Plugin\Flag\EntityFlagType;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\Core\Form\ConfirmFormInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Action\ActionManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations for flag.
 */
class FlagHooks {

  use StringTranslationTrait;

  public function __construct(
    protected FlagServiceInterface $flagService,
    protected AccountProxyInterface $account,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RendererInterface $renderer,
    protected ModuleHandlerInterface $moduleHandler,
    #[Autowire(service: 'plugin.manager.action')]
    protected ActionManager $actionManager,
  ) {
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'entity.flag.collection':
        $output = '<p>' . $this->t('This page lists all the <em>flags</em> that are currently defined on this system.') . '</p>';
        if ($this->moduleHandler->moduleExists('views')) {
          $output .= '<p>';
          $output .= $this->t('Lists of flagged content can be displayed using views. You can configure these in the Views administration section.');
          if ($this->flagService->getFlagById('bookmark')) {
            $output .= ' ' . $this->t('Flag module automatically provides a few default views for the <em>bookmarks</em> flag. You can use these as templates by cloning these views and then customizing as desired.');
          }
          $output .= ' ' . $this->t('The <a href="@flag-handbook-url">Flag module handbook</a> contains extensive <a href="@customize-url">documentation on creating customized views</a> using flags.', [
            '@flag-handbook-url' => 'http://drupal.org/handbook/modules/flag',
            '@customize-url' => 'http://drupal.org/node/296954',
          ]);
          $output .= '</p>';
        }
        if ($this->moduleHandler->moduleExists('rules')) {
          $output .= '<p>' . $this->t('Flagging an item may trigger <a href="@rules-url">rules</a>.', [
            '@rules-url' => Url::fromRoute('entity.rules_reaction_rule.collection')->toString(),
          ]) . '</p>';
        }
        else {
          $output .= '<p>' . $this->t('Flagging an item may trigger <em>rules</em>. However, you don\'t have the <a href="@rules-url">Rules</a> module enabled, so you won\'t be able to enjoy this feature. The Rules module is a more extensive solution than Flag actions.', [
            '@rules-url' => Url::fromUri('http://drupal.org/node/407070')->toString(),
          ]) . '</p>';
        }
        $output .= '<p>' . $this->t('To learn about the various ways to use flags, please check out the <a href="@handbook-url">Flag module handbook</a>.', [
          '@handbook-url' => 'http://drupal.org/handbook/modules/flag',
        ]) . '</p>';
        return $output;

      case 'flag.add_page':
        $output = '<p>' . $this->t('Select the type of flag to create.') . '</p>';
        return $output;
    }
  }

  /**
   * Performs flagging/unflagging for the entity edit form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   * @param array $values
   *   The flag entity form values.
   *
   * @see flag_form_submit()
   */
  public static function flagFormSave(EntityInterface $entity, array $values) {
    $flag_service = \Drupal::service('flag');
    $account = \Drupal::currentUser();

    // For existing entities, get any existing flaggings per flag.
    $flagging_ids = [];
    if (!$entity->isNew()) {
      $flaggings = $flag_service->getAllEntityFlaggings($entity, $account);
      $flagging_ids = array_map(function ($flagging) {
        return $flagging->getFlagId();
      }, $flaggings);
    }

    // Load all the flags for the entity.
    $flags = $flag_service->getAllFlags($entity->getEntityTypeId(), $entity->bundle());

    /** @var \Drupal\flag\FlagInterface $flag */
    foreach ($flags as $flag) {
      // Get the flag_id from the Flag.
      $flag_id = $flag->id();

      // If the flag_id is not part of the form values, no need to do anything.
      if (!isset($values[$flag_id])) {
        continue;
      }

      // Get the form flag value.
      $value = $values[$flag_id];

      // Determine if the flagging exists.
      $flagging_exists = in_array($flag_id, $flagging_ids);

      // If the flag is checked in the form, and the flagging doesn't exist...
      if ($value && !$flagging_exists) {
        // ...flag the entity.
        $flag_service->flag($flag, $entity);
      }

      // If the flag is not checked in the form, and the flagging exists..
      if (!$value && $flagging_exists) {
        // ...unflag the entity.
        $flag_service->unflag($flag, $entity);
      }
    }
  }

  /**
   * Form submission handler for the flag module.
   *
   * @see flag_form_alter()
   */
  public static function flagFormSubmit($form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    $entity = $form_object->getEntity();
    if (!$form_state->isValueEmpty('flag')) {
      $values = $form_state->getValue('flag');
      static::flagFormSave($entity, $values);
    }
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    $object = $form_state->getFormObject();
    // We only want to operate on content entity forms.
    if (!$object instanceof ContentEntityFormInterface || $object instanceof ConfirmFormInterface) {
      return;
    }
    // Get the flags for the entity being edited by the form.
    $flag_service = $this->flagService;
    $entity = $object->getEntity();
    $flags = $flag_service->getAllFlags($entity->getEntityTypeId(), $entity->bundle());
    // Check the first flag and return early if the form isn't considered to be
    // an edit form.
    $first_flag = reset($flags);
    if ($first_flag instanceof FlagInterface) {
      /** @var \Drupal\flag\Plugin\Flag\EntityFlagType $flag_type */
      $flag_type = $first_flag->getFlagTypePlugin();
      if (!$flag_type->isAddEditForm($object->getOperation())) {
        return;
      }
    }
    // Filter the flags to those that apply here:
    // - the flag uses the entity type plugin.
    // - the plugin is configured to output the flag in the entity form.
    // - the current user has access to the flag.
    $filtered_flags = array_filter($flags, function (FlagInterface $flag) use ($object) {
        $plugin = $flag->getFlagTypePlugin();
        $entity = $object->getEntity();
        $action = $flag->isFlagged($entity) ? 'unflag' : 'flag';
        $access = $flag->actionAccess($action, NULL, $entity);
        return $plugin instanceof EntityFlagType && $plugin->showOnForm() && $access->isAllowed();
    });
    // If we still have any flags...
    if (!empty($filtered_flags)) {
      // Add a container to the form.
      $form['flag'] = [
        '#type' => 'details',
        '#title' => $this->t('Flags'),
        '#attached' => [
          'library' => [
            'flag/flag.admin',
          ],
        ],
        '#group' => 'advanced',
        '#tree' => TRUE,
      ];
      /** @var \Drupal\flag\FlagInterface $flag */
      foreach ($filtered_flags as $flag) {
        // Add each flag to the form.
        $form['flag'][$flag->id()] = [
          '#type' => 'checkbox',
          '#title' => $flag->label(),
          '#description' => $flag->getLongText('flag'),
          '#default_value' => $flag->isFlagged($entity) ? 1 : 0,
          '#return_value' => 1,
              // Used by our drupalSetSummary() on vertical tabs.
          '#attributes' => [
            'title' => $flag->label(),
          ],
        ];
      }
      foreach (array_keys($form['actions']) as $action) {
        if ($action != 'preview' && isset($form['actions'][$action]['#type']) && $form['actions'][$action]['#type'] === 'submit') {
          $form['actions'][$action]['#submit'][] = [static::class, 'flagFormSubmit'];
        }
      }
    }
  }

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo() {
    $extra = [];
    $flag_service = $this->flagService;
    $flags = $flag_service->getAllFlags();
    /** @var \Drupal\flag\FlagInterface $flag */
    foreach ($flags as $flag) {
      // Skip flags that aren't on entities.
      $flag_type_plugin = $flag->getFlagTypePlugin();
      if (!$flag_type_plugin instanceof EntityFlagType) {
        continue;
      }
      $flaggable_bundles = $flag->getApplicableBundles();
      foreach ($flaggable_bundles as $bundle_name) {
        if ($flag_type_plugin->showOnForm()) {
          $extra[$flag->getFlaggableEntityTypeId()][$bundle_name]['form']['flag'] = [
            'label' => $this->t('Flags'),
            'description' => $this->t('Checkboxes for toggling flags'),
            'weight' => 10,
          ];
        }
        if ($flag_type_plugin->showAsField()) {
          $extra[$flag->getFlaggableEntityTypeId()][$bundle_name]['display']['flag_' . $flag->id()] = [
            'label' => $this->t('Flag: %title', [
              '%title' => $flag->label(),
            ]),
            'description' => $this->t('Individual flag link'),
            'weight' => 10,
          ];
        }
      }
    }
    return $extra;
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() {
    return [
      'flag' => [
        'variables' => [
          'attributes' => [],
          'title' => NULL,
          'unflag_denied_text' => NULL,
          'action' => 'flag',
          'flag' => NULL,
          'flaggable' => NULL,
          'view_mode' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_flag')]
  public function themeSuggestionsFlag(array $variables) {
    $flag = $variables['flag'];
    $flaggable = $variables['flaggable'];
    $view_mode = $variables['view_mode'];
    return [
      'flag__' . $flag->id(),
      'flag__' . $view_mode,
      'flag__' . $flag->id() . '__' . $view_mode,
      'flag__' . $flag->id() . '_' . $flaggable->id(),
    ];
  }

  /**
   * Build flag entity links.
   *
   * @see flag_node_links_alter()
   * @see flag_comment_links_alter()
   */
  protected function flagBuildEntityLinks(array &$links, EntityInterface $entity, array &$context) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->renderer;
    /** @var \Drupal\flag\FlagServiceInterface $flagManager */
    $flagManager = $this->flagService;
    $extraLinks = [];
    $cache = ['contexts' => [], 'tags' => [], 'max-age' => NULL];

    // Adds flag type links.
    $viewMode = $context['view_mode'] === 'default' ? 'full' : $context['view_mode'];
    foreach ($flagManager->getAllFlags($entity->getEntityTypeId(), $entity->bundle()) as $flag) {
      if (in_array($viewMode, $flag->get('flagTypeConfig')['show_in_links'], TRUE)) {
        if ($link = $flag->getLinkTypePlugin()->getAsFlagLink($flag, $entity, $viewMode)) {
          $cache['contexts'] = Cache::mergeContexts($cache['contexts'], $link['#cache']['contexts']);
          $cache['tags'] = Cache::mergeTags($cache['tags'], $link['#cache']['tags']);
          $cache['max-age'] = Cache::mergeMaxAges($cache['max-age'], $link['#cache']['max-age']);
          $extraLinks["flag_{$flag->id()}"] = ['title' => $renderer->render($link)];
        }
      }
    }

    if ($extraLinks) {
      $links['flags'] = [
        '#theme' => "links__{$entity->getEntityTypeId()}__flags",
        '#links' => $extraLinks,
        '#attributes' => ['class' => ['links', 'inline']],
        '#cache' => $cache,
      ];
    }
  }

  /**
   * Implements hook_node_links_alter().
   */
  #[Hook('node_links_alter')]
  public function nodeLinksAlter(array &$links, EntityInterface $entity, array &$context) {
    $this->flagBuildEntityLinks($links, $entity, $context);
  }

  /**
   * Implements hook_comment_links_alter().
   */
  #[Hook('comment_links_alter')]
  public function commentLinksAlter(array &$links, EntityInterface $entity, array &$context) {
    $this->flagBuildEntityLinks($links, $entity, $context);
  }

  /**
   * Implements hook_entity_view().
   *
   * Handles the 'show_in_links' and 'show_as_field' flag options.
   */
  #[Hook('entity_view')]
  public function entityView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, ?string $view_mode = NULL) {
    $build['#cache']['contexts'][] = 'user.permissions';
    if (empty($build['#cache']['tags'])) {
      $build['#cache']['tags'] = [];
    }
    // Get all possible flags for this entity type.
    $flag_service = $this->flagService;
    $flags = $flag_service->getAllFlags($entity->getEntityTypeID(), $entity->bundle());
    foreach ($flags as $flag) {
      $build['#cache']['tags'] = Cache::mergeTags($build['#cache']['tags'], $flag->getCacheTags());
      // Do not display the flag if disabled.
      if (!$flag->status()) {
        continue;
      }
      /** @var \Drupal\flag\Plugin\Flag\EntityFlagType $flag_type_plugin */
      $flag_type_plugin = $flag->getFlagTypePlugin();
      // Only add cache key if flag link is displayed.
      if (!$flag_type_plugin->showAsField() || !$display->getComponent('flag_' . $flag->id())) {
        continue;
      }
      // If entity is new build link without
      // lazy builder as the entity doesn't have id yet.
      if ($entity->isNew()) {
        $link_type_plugin = $flag->getLinkTypePlugin();
        $build['flag_' . $flag->id()] = $link_type_plugin->getAsFlagLink($flag, $entity);
      }
      else {
        $build['flag_' . $flag->id()] = [
          '#lazy_builder' => [
            'flag.link_builder:build',
                  [
                    $entity->getEntityTypeId(),
                    $entity->id(),
                    $flag->id(),
                  ],
          ],
          '#create_placeholder' => TRUE,
        ];
      }
    }
  }

  /**
   * Implements hook_entity_build_defaults_alter().
   */
  #[Hook('entity_build_defaults_alter')]
  public function entityBuildDefaultsAlter(array &$build, EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\flag\FlagService $flag_service */
    $flag_service = $this->flagService;
    // Get all possible flags for this entity type.
    $flags = $flag_service->getAllFlags($entity->getEntityTypeId(), $entity->bundle());
    $no_cache = FALSE;
    foreach ($flags as $flag) {
      $flag_type_plugin = $flag->getFlagTypePlugin();
      // Make sure we're dealing with an entity flag type.
      if (!$flag_type_plugin instanceof EntityFlagType) {
        continue;
      }
      // Only add max-age to entity render array if contextual links flag
      // display is enabled.
      if (!$flag_type_plugin->showContextualLink()) {
        continue;
      }
      $no_cache = TRUE;
    }
    if ($no_cache) {
      $build['#cache']['max-age'] = 0;
    }
    return $build;
  }

  /**
   * Implements hook_entity_view_alter().
   *
   * Alters node contextual links placeholder id to contain flag metadata, so
   * that contextual links cache considers flags granularity.
   */
  #[Hook('entity_view_alter')]
  public function entityViewAlter(&$build, EntityInterface $entity, EntityViewDisplayInterface $display) {
    $entity_type = $entity->getEntityTypeId();
    if (isset($build['#contextual_links'][$entity_type])) {
      /** @var \Drupal\flag\FlagService $flag_service */
      $flag_service = $this->flagService;
      // Get all possible flags for this entity type.
      $flags = $flag_service->getAllFlags($entity_type, $entity->bundle());
      foreach ($flags as $flag) {
        $flag_type_plugin = $flag->getFlagTypePlugin();
        // Make sure we're dealing with an entity flag type.
        if (!$flag_type_plugin instanceof EntityFlagType) {
          continue;
        }
        // Only apply metadata to contextual links if plugin is enabled.
        if (!$flag_type_plugin->showContextualLink()) {
          continue;
        }
        $action = 'flag';
        if ($flag->isFlagged($entity)) {
          $action = 'unflag';
        }
        $flag_keys[] = $flag->id() . '-' . $action;
      }
      if (!empty($flag_keys)) {
        $build['#contextual_links'][$entity_type]['route_parameters']['view_mode'] = $build['#view_mode'];
        $build['#contextual_links'][$entity_type]['metadata']['flag_keys'] = implode(',', $flag_keys);
      }
    }
    // Enable placeholder on entity links to avoid them being cached with the
    // entity view mode.
    if (isset($build['links']['#lazy_builder'])) {
      $build['links']['#create_placeholder'] = TRUE;
    }
  }

  /**
   * Implements hook_contextual_links_alter().
   */
  #[Hook('contextual_links_alter')]
  public function contextualLinksAlter(array &$links, $group, array $route_parameters) {
    // Assume that $group is one of known entity types and try to load an entity
    // based on that.
    $entity_type = $group;
    if (isset($route_parameters[$entity_type]) && !is_null($this->entityTypeManager->getDefinition($entity_type, FALSE))) {
      $entity = $this->entityTypeManager->getStorage($entity_type)->load($route_parameters[$entity_type]);
    }
    if (!isset($entity)) {
      return;
    }
    // Get all possible flags for this entity type.
    $flag_service = $this->flagService;
    $flags = $flag_service->getAllFlags($entity->getEntityTypeID(), $entity->bundle());
    foreach ($flags as $flag) {
      /** @var \Drupal\flag\FlagInterface $flag */
      // Do not display the flag if disabled.
      if (!$flag->status()) {
        continue;
      }
      /** @var \Drupal\flag\Plugin\Flag\EntityFlagType $flag_type_plugin */
      $flag_type_plugin = $flag->getFlagTypePlugin();
      // Make sure we're dealing with an entity flag type.
      if (!$flag_type_plugin instanceof EntityFlagType) {
        continue;
      }
      // Skip flags for which contextual links setting is disabled.
      if (!$flag_type_plugin->showContextualLink()) {
        continue;
      }
      $flag_link = $flag->getLinkTypePlugin()->getAsLink($flag, $entity, $route_parameters['view_mode']);
      $flag_url = $flag_link->getUrl();
      $links["flag_{$flag->id()}"] = [
        'route_name' => $flag_url->getRouteName(),
        'route_parameters' => $flag_url->getRouteParameters(),
        'title' => $flag_link->getText(),
        'localized_options' => [],
      ];
    }
  }

  /**
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity) {
    // No need to process for a flagging type entity.
    if ($entity->getEntityTypeId() == 'flagging') {
      return;
    }
    // User flags handle things through user entity hooks.
    if ($entity->getEntityTypeId() == 'user') {
      return;
    }
    $this->flagService->unflagAllByEntity($entity);
  }

  /**
   * Implements hook_user_cancel().
   */
  #[Hook('user_cancel')]
  public function userCancel($edit, $account, $method) {
    $this->flagService->userFlagRemoval($account);
  }

  /**
   * Implements hook_user_predelete().
   */
  #[Hook('user_predelete')]
  public function userPredelete(UserInterface $account) {
    $this->flagService->userFlagRemoval($account);
  }

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    if ($entity instanceof FlagInterface) {
      if (!$entity->status()) {
        $operations['enable'] = [
          'title' => $this->t('Enable'),
          'url' => $entity->toUrl('enable'),
          'weight' => 50,
        ];
      }
      else {
        $operations['disable'] = [
          'title' => $this->t('Disable'),
          'url' => $entity->toUrl('disable'),
          'weight' => 50,
        ];
      }
      $operations['reset'] = [
        'title' => $this->t('Reset'),
        'url' => $entity->toUrl('reset'),
        'weight' => 100,
      ];
    }
    return $operations;
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('flag_insert')]
  public function flagInsert(FlagInterface $flag) {
    if ($flag->isSyncing()) {
      // Do not create actions when config is progress of synchronization.
      return;
    }
    // The action plugin cache needs to detect the new flag.
    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->actionManager;
    $action_manager->clearCachedDefinitions();
    // Add the flag/unflag actions for this flag and entity combination.
    $flag_id = 'flag_action.' . $flag->id() . '_flag';
    if (!Action::load($flag_id)) {
      $action = Action::create([
        'id' => $flag_id,
        'type' => $flag->getFlaggableEntityTypeId(),
        'label' => $flag->getShortText('flag'),
        'plugin' => 'flag_action:' . $flag->id() . '_flag',
        'configuration' => [
          'flag_id' => $flag->id(),
          'flag_action' => 'flag',
        ],
      ]);
      $action->trustData()->save();
    }
    $unflag_id = 'flag_action.' . $flag->id() . '_unflag';
    if (!Action::load($unflag_id)) {
      $action = Action::create([
        'id' => $unflag_id,
        'type' => $flag->getFlaggableEntityTypeId(),
        'label' => $flag->getShortText('unflag'),
        'plugin' => 'flag_action:' . $flag->id() . '_unflag',
        'configuration' => [
          'flag_id' => $flag->id(),
          'flag_action' => 'unflag',
        ],
      ]);
      $action->trustData()->save();
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   */
  #[Hook('flag_delete')]
  public function flagDelete(FlagInterface $flag) {
    // Do not delete actions when config is progress of synchronization.
    if ($flag->isSyncing()) {
      return;
    }
    $actions = Action::loadMultiple([
      'flag_action.' . $flag->id() . '_flag',
      'flag_action.' . $flag->id() . '_unflag',
    ]);
    // Remove the flag/unflag actions for this flag and entity combination.
    foreach ($actions as $action) {
      $action->delete();
    }
  }

}
