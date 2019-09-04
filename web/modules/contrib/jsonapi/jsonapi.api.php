<?php

/**
 * @file
 * Documentation related to JSON API.
 */

use Drupal\Core\Access\AccessResult;

/**
 * @defgroup jsonapi_normalizer_architecture JSON API Normalizer Architecture
 * @{
 *
 * @section overview Overview
 * The JSON API module is a Drupal-centric implementation of the JSON API
 * specification. By its own definition, the JSON API specification is "is a
 * specification for how a client should request that resources be fetched or
 * modified, and how a server should respond to those requests. [It] is designed
 * to minimize both the number of requests and the amount of data transmitted
 * between clients and servers. This efficiency is achieved without compromising
 * readability, flexibility, or discoverability."
 *
 * While "Drupal-centric", the JSON API module is committed to strict compliance
 * with the specification. Wherever possible, the module attempts to implement
 * the specification in a way which is compatible and familiar with the patterns
 * and concepts inherent to Drupal. However, when "Drupalisms" cannot be
 * reconciled with the specification, the module will always choose the
 * implementation most faithful to the specification.
 *
 * @see http://jsonapi.org/
 *
 *
 * @section resources Resources
 * Every unit of data in the specification is a "resource". The specification
 * defines how a client should interact with a server to fetch and manipulate
 * these resources.
 *
 * The JSON API module maps every entity type + bundle to a resource type.
 * Since the specification does not have a concept of resource type inheritance
 * or composition, the JSON API module implements different bundles of the same
 * entity type as *distinct* resource types.
 *
 * While it is theoretically possible to expose arbitrary data as resources, the
 * JSON API module only exposes resources from (config and content) entities.
 * This eliminates the need for another abstraction layer in order implement
 * certain features of the specification.
 *
 *
 * @section relationships Relationships
 * The specification defines semantics for the "relationships" between
 * resources. Since the JSON API module defines every entity type + bundle as a
 * resource type and does not allow non-entity resources, it is able to use
 * entity references to automatically define and represent the relationships
 * between all resources.
 *
 *
 * @section normalizers Normalizers
 * The JSON API module reuses as many of Drupal core's Serialization module's
 * normalizers as possible.
 *
 * The JSON API specification requires special handling for resources
 * (entities), relationships between those resources (entity references) and
 * resource IDs (entity UUIDs), it must override some of the Serialization
 * module's normalizers for entities and fields (most notably, entity
 * reference fields).
 *
 * This means that modules which provide additional field types must implement
 * normalizers at the "DataType" plugin level. This is a level below "FieldType"
 * plugins. Normalizers which are not implemented at this level will not be used
 * by the JSON API module.
 *
 * A benefit of implementing normalizers at this lower level is that they will
 * work automatically for both the JSON API module and core's REST module.
 *
 *
 * @section api API
 * The JSON API module provides an HTTP API that adheres to the JSON API
 * specification.
 *
 * The JSON API module provides *no PHP API to modify its behavior.* It is
 * designed to have zero configuration.
 *
 * - Adding new resources/resource types is unsupported: all entities/entity
 *   types are exposed automatically. If you want to expose more data via the
 *   JSON API module, the data must be defined as entity. See the "Resources"
 *   section.
 * - Custom field normalization is not supported; only normalizers at the
 *   "DataType" plugin level are supported (these are a level below field
 *   types).
 * - All available authentication mechanisms are allowed.
 *
 * The JSON API module does provide a PHP API to generate a JSON API
 * representation of entities:
 *
 * @code
 * \Drupal::service('jsonapi.entity.to_jsonapi')->serialize($entity)
 * @endcode
 *
 *
 * @section tests Test Coverage
 * The JSON API module comes with extensive unit and kernel tests. But most
 * importantly for end users, it also has comprehensive integration tests. These
 * integration tests are designed to:
 *
 * - ensure a great DX (Developer Experience)
 * - detect regressions and normalization changes before shipping a release
 * - guarantee 100% of Drupal core's entity types work as expected
 *
 * The integration tests test the same common cases and edge cases using
 * @code \Drupal\Tests\jsonapi\Functional\ResourceTestBase @endcode, which is a
 * base class subclassed for every entity type that Drupal core ships with. It
 * is ensured that 100% of Drupal core's entity types are tested thanks to
 * @code \Drupal\Tests\jsonapi\Functional\TestCoverageTest @endcode.
 *
 * Custom entity type developers can get the same assurances by subclassing it
 * for their entity types.
 *
 *
 * @section bc Backwards Compatibility
 * PHP API: there is no PHP API except for three security-related hooks. This
 * means that this module's implementation details are entirely free to
 * change at any time.
 *
 * Please note, *normalizers are internal implementation details.* While
 * normalizers are services, they are *not* to be used directly. This is due to
 * the design of the Symfony Serialization component, not because the JSON API
 * module wanted to publicly expose services.
 *
 * HTTP API: URLs and JSON response structures are considered part of this
 * module's public API. However, inconsistencies with the JSON API specification
 * will be considered bugs. Fixes which bring the module into compliance with
 * the specification are *not* guaranteed to be backwards compatible.
 *
 * What this means for developing consumers of the HTTP API is that *clients
 * should be implemented from the specification first and foremost.* This should
 * mitigate implicit dependencies on implementation details or inconsistencies
 * with the specification that are specific to this module.
 *
 * To help develop compatible clients, every response indicates the version of
 * the JSON API specification used under its "jsonapi" key. Future releases
 * *may* increment the minor version number if the module implements features of
 * a later specification. Remember that he specification stipulates that future
 * versions *will* remain backwards compatible as only additions may be
 * released.
 *
 * @see http://jsonapi.org/faq/#what-is-the-meaning-of-json-apis-version
 *
 * Tests: subclasses of base test classes may contain BC breaks between minor
 * releases, to allow minor releases to A) comply better with the JSON API spec,
 * B) guarantee that all resource types (and therefore entity types) function as
 * expected, C) update to future versions of the JSON API spec.
 *
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Controls access when filtering by entity data via JSON:API.
 *
 * This module supports filtering by resource object attributes referenced by
 * relationship fields. For example, a site may add a "Favorite Animal" field
 * to user entities, which would permit the following filtered query:
 * @code
 * /jsonapi/node/article?filter[uid.field_favorite_animal]=llama
 * @endcode
 * This query would return articles authored by users whose favorite animal is a
 * llama. However, the information about a user's favorite animal should not be
 * available to users without the "access user profiles" permission. The same
 * must hold true even if that user is referenced as an article's author.
 * Therefore, access to filter by this data must be restricted so that access
 * cannot be bypassed via a JSON:API filtered query.
 *
 * As a rule, clients should only be able to filter by data that they can
 * view.
 *
 * Conventionally, @code $entity->access('view') @endcode is how entity access
 * is checked. This call invokes the corresponding hooks. However, these access
 * checks require an @code $entity @endcode object. This means that they cannot
 * be called prior to executing a database query.
 *
 * In order to safely enable filtering across a relationship, modules
 * responsible for entity access must do two things:
 * - Implement this hook (or hook_jsonapi_ENTITY_TYPE_filter_access()) and
 *   return an array of AccessResults keyed by the named entity subsets below.
 * - If the AccessResult::allowed() returned by the above hook does not provide
 *   enough granularity (for example, if access depends on a bundle field value
 *   of the entity being queried), then hook_query_TAG_alter() must be
 *   implemented using the 'entity_access' or 'ENTITY_TYPE_access' query tag.
 *   See node_query_node_access_alter() for an example.
 *
 * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
 *   The entity type of the entity to be filtered upon.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account for which to check access.
 *
 * @return \Drupal\Core\Access\AccessResultInterface[]
 *   An array keyed by a constant which identifies a subset of entities. For
 *   each subset, the value is one of the following access results:
 *   - AccessResult::allowed() if all entities within the subset (potentially
 *     narrowed by hook_query_TAG_alter() implementations) are viewable.
 *   - AccessResult::forbidden() if any entity within the subset is not
 *     viewable.
 *   - AccessResult::neutral() if the implementation has no opinion.
 *   The supported subsets for which an access result may be returned are:
 *   - JSONAPI_FILTER_AMONG_ALL: all entities of the given type.
 *   - JSONAPI_FILTER_AMONG_PUBLISHED: all published entities of the given type.
 *   - JSONAPI_FILTER_AMONG_ENABLED: all enabled entities of the given type.
 *   - JSONAPI_FILTER_AMONG_OWN: all entities of the given type owned by the
 *     user for whom access is being checked.
 *   See the documentation of the above constants for more information about
 *   each subset.
 *
 * @see hook_jsonapi_ENTITY_TYPE_filter_access()
 */
function hook_jsonapi_entity_filter_access(\Drupal\Core\Entity\EntityTypeInterface $entity_type, \Drupal\Core\Session\AccountInterface $account) {
  // For every entity type that has an admin permission, allow access to filter
  // by all entities of that type to users with that permission.
  if ($admin_permission = $entity_type->getAdminPermission()) {
    return ([
      JSONAPI_FILTER_AMONG_ALL => AccessResult::allowedIfHasPermission($account, $admin_permission),
    ]);
  }
}

/**
 * Controls access to filtering by entity data via JSON:API.
 *
 * This is the entity-type-specific variant of
 * hook_jsonapi_entity_filter_access(). For implementations with logic that is
 * specific to a single entity type, it is recommended to implement this hook
 * rather than the generic hook_jsonapi_entity_filter_access() hook, which is
 * called for every entity type.
 *
 * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
 *   The entity type of the entities to be filtered upon.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account for which to check access.
 *
 * @return \Drupal\Core\Access\AccessResultInterface[]
 *   The array of access results, keyed by subset. See
 *   hook_jsonapi_entity_filter_access() for details.
 *
 * @see hook_jsonapi_entity_filter_access()
 */
function hook_jsonapi_ENTITY_TYPE_filter_access(\Drupal\Core\Entity\EntityTypeInterface $entity_type, \Drupal\Core\Session\AccountInterface $account) {
  return ([
    JSONAPI_FILTER_AMONG_ALL => AccessResult::allowedIfHasPermission($account, 'administer llamas'),
    JSONAPI_FILTER_AMONG_PUBLISHED => AccessResult::allowedIfHasPermission($account, 'view all published llamas'),
    JSONAPI_FILTER_AMONG_OWN => AccessResult::allowedIfHasPermissions($account, ['view own published llamas', 'view own unpublished llamas'], 'AND'),
  ]);
}

/**
 * Restricts filtering access to the given field.
 *
 * Some fields may contain sensitive information. In these cases, modules are
 * supposed to implement hook_entity_field_access(). However, this hook receives
 * an optional @code $items @endcode argument and often must return
 * AccessResult::neutral() when @code $items === NULL @endcode. This is because
 * access may or may not be allowed based on the field items or based on the
 * entity on which the field is attached (if the user is the entity owner, for
 * example).
 *
 * Since JSON:API must check field access prior to having a field item list
 * instance available (access must be checked before a database query is made),
 * it is not sufficiently secure to check field 'view' access alone.
 *
 * This hook exists so that modules which cannot return
 * AccessResult::forbidden() from hook_entity_field_access() can still secure
 * JSON:API requests where necessary.
 *
 * If a corresponding implementation of hook_entity_field_access() *can* be
 * forbidden for one or more values of the @code $items @endcode argument, this
 * hook *MUST* return AccessResult::forbidden().
 *
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
 *   The field definition of the field to be filtered upon.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account for which to check access.
 *
 * @return \Drupal\Core\Access\AccessResultInterface
 *   The access result.
 */
function hook_jsonapi_entity_field_filter_access(\Drupal\Core\Field\FieldDefinitionInterface $field_definition, \Drupal\Core\Session\AccountInterface $account) {
  if ($field_definition->getTargetEntityTypeId() === 'node' && $field_definition->getName() === 'field_sensitive_data') {
    $has_sufficient_access = FALSE;
    foreach (['administer nodes', 'view all sensitive field data'] as $permission) {
      $has_sufficient_access = $has_sufficient_access ?: $account->hasPermission($permission);
    }
    return AccessResult::forbiddenIf(!$has_sufficient_access)->cachePerPermissions();
  }
  return AccessResult::neutral();
}

/**
 * @} End of "addtogroup hooks".
 */
