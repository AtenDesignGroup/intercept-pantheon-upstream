<?php

namespace Drupal\flag\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\FlagServiceInterface;

/**
 * Hook implementations for flag.
 */
class FlagTokensHooks {

  use StringTranslationTrait;

  public function __construct(
    protected FlagServiceInterface $flagService,
  ) {
  }

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo() {
    $types = [];
    $tokens = [];
    // Flag tokens.
    $types['flag'] = [
      'name' => $this->t('Flags'),
      'description' => $this->t('Tokens related to flag data.'),
      'needs-data' => 'flag',
    ];
    $tokens['flag']['name'] = [
      'name' => $this->t('Flag name'),
      'description' => $this->t('The flag machine-readable name.'),
    ];
    $tokens['flag']['title'] = [
      'name' => $this->t('Flag title'),
      'description' => $this->t('The human-readable flag title.'),
    ];
    // Flagging tokens.
    //
    // Attached fields are exposed as tokens via some contrib module, but we
    // need to expose other fields ourselves. Currently, 'date' is the only such
    // field we expose.
    $types['flagging'] = [
      'name' => $this->t('Flaggings'),
      'description' => $this->t('Tokens related to flaggings.'),
      'needs-data' => 'flagging',
    ];
    $tokens['flagging']['date'] = [
      'name' => $this->t('Flagging date'),
      'description' => $this->t('The date an item was flagged.'),
      'type' => 'date',
    ];
    // Flag action tokens.
    $types['flag-action'] = [
      'name' => $this->t('Flag actions'),
      'description' => $this->t('Tokens available in response to a flag action being executed by a user.'),
      'needs-data' => 'flag-action',
    ];
    $tokens['flag-action']['action'] = [
      'name' => $this->t('Flag action'),
      'description' => $this->t('The flagging action taking place, either "flag" or "unflag".'),
    ];
    $tokens['flag-action']['entity-url'] = [
      'name' => $this->t('Flag entity URL'),
      'description' => $this->t('The URL of the entity being flagged.'),
    ];
    $tokens['flag-action']['entity-title'] = [
      'name' => $this->t('Flag entity title'),
      'description' => $this->t('The title of the entity being flagged.'),
    ];
    $tokens['flag-action']['entity-type'] = [
      'name' => $this->t('Flag entity type'),
      'description' => $this->t('The type of entity being flagged, such as <em>node</em> or <em>comment</em>.'),
    ];
    $tokens['flag-action']['entity-id'] = [
      'name' => $this->t('Flag entity ID'),
      'description' => $this->t('The ID of entity being flagged, such as a nid or cid.'),
    ];
    $tokens['flag-action']['count'] = [
      'name' => $this->t('Flag count'),
      'description' => $this->t('The current count total for this flag.'),
    ];
    // Add tokens for the flag count available at the node/comment/user level.
    /** @var \Drupal\flag\FlagInterface[] $flags */
    $flags = $this->flagService->getAllFlags();
    foreach ($flags as $id => $flag) {
      $flag_entity_type_id = $flag->getFlaggableEntityTypeId();
      $tokens[$flag_entity_type_id]['flag-' . str_replace('_', '-', $id) . '-count'] = [
        'name' => $this->t('@flag flag count', [
          '@flag' => $flag->label(),
        ]),
        'description' => $this->t('Total flag count for flag @flag', [
          '@flag' => $flag->label(),
        ]),
      ];
      $tokens[$flag_entity_type_id]['flag-' . str_replace('_', '-', $id) . '-link'] = [
        'name' => $this->t('@flag flag link', [
          '@flag' => $flag->label(),
        ]),
        'description' => $this->t('Flag/unflag link for @flag', [
          '@flag' => $flag->label(),
        ]),
      ];
    }
    return [
      'types' => $types,
      'tokens' => $tokens,
    ];
  }

}
