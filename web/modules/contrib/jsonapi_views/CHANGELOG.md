# Changelog

All notable changes to JSON:API Views are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

## 8.x-1.2 (2026-08-09)

### Fixed

- [#3202583](https://www.drupal.org/project/jsonapi_views/issues/3202583):
  Propagated the view's full cache metadata (tags, contexts, max-age) to the
  JSON:API response and skipped view execution for access-denied requests.
- [#3265781](https://www.drupal.org/project/jsonapi_views/issues/3265781):
  Added the resource type route default for single-bundle views so route
  introspection tools (for example the OpenAPI module) can resolve them.
- [#3376193](https://www.drupal.org/project/jsonapi_views/issues/3376193):
  Keyed each exposed filter in the preview URL by its identifier instead of
  its field name, so filters are no longer dropped and no longer raise PHP
  warnings when the two differ.
- [#3484714](https://www.drupal.org/project/jsonapi_views/issues/3484714):
  Cached resource type names per entity type to reduce repeated route-build
  work when multiple views share an entity type.
- [#3503402](https://www.drupal.org/project/jsonapi_views/issues/3503402):
  Cast bundle ID to string in route resource type lookup so numeric bundle
  machine names no longer cause failing assertions.

### Changed

- [#3587949](https://www.drupal.org/project/jsonapi_views/issues/3587949):
  Applied drupal-extension-scaffold v4.17.0, added GitLab CI pipeline, and
  resolved PHPStan, ESLint, and CSpell compatibility across Drupal 10 and 11.

## 8.x-1.1 (2023-02-10)

### Fixed

- [#3288151](https://www.drupal.org/project/jsonapi_views/issues/3288151):
  Automated Drupal 10 compatibility fixes.

## 8.x-1.0 (2021-10-01)

### Added

- [#3145281](https://www.drupal.org/project/jsonapi_views/issues/3145281):
  Per-display opt-out — each Views display now has an "Expose via JSON:API"
  checkbox in its settings.
- [#3115484](https://www.drupal.org/project/jsonapi_views/issues/3115484):
  Contextual filter (argument) support with argument values shown in the
  preview URL.
- [#3199990](https://www.drupal.org/project/jsonapi_views/issues/3199990):
  Added project documentation.

### Fixed

- [#3239967](https://www.drupal.org/project/jsonapi_views/issues/3239967):
  Pagination links broken on Drupal 9.2.x.
- [#3228973](https://www.drupal.org/project/jsonapi_views/issues/3228973):
  Fixed argument order for `implode` and removed error messages.

## 8.x-1.0-beta3 (2021-06-17)

### Fixed

- [#3202583](https://www.drupal.org/project/jsonapi_views/issues/3202583):
  Views cache settings not added to response headers.

## 8.x-1.0-beta2 (2021-03-24)

### Added

- [#3205301](https://www.drupal.org/project/jsonapi_views/issues/3205301):
  JSON:API URL shown in Views query preview info.

### Fixed

- [#3200875](https://www.drupal.org/project/jsonapi_views/issues/3200875):
  Fixed error with pager.

## 8.x-1.0-beta1 (2021-02-08)

### Added

- Initial release — creates a JSON:API resource for each Views display.
- [#3180345](https://www.drupal.org/project/jsonapi_views/issues/3180345):
  Exposed sort support — Views sort can be overridden via query parameter.
- [#3187221](https://www.drupal.org/project/jsonapi_views/issues/3187221):
  Total count included in the JSON:API response.
- [#3164505](https://www.drupal.org/project/jsonapi_views/issues/3164505):
  Drupal 9 support.
- [#3121042](https://www.drupal.org/project/jsonapi_views/issues/3121042):
  Sanitized exposed filter query params for JSON:API spec compliance.
- Functional test coverage and test fixture module.

### Fixed

- [#3175541](https://www.drupal.org/project/jsonapi_views/issues/3175541):
  Caching issues with exposed filters.
- [#3196442](https://www.drupal.org/project/jsonapi_views/issues/3196442):
  Caching issues with some display types.
- [#3158566](https://www.drupal.org/project/jsonapi_views/issues/3158566):
  Pagination links missing.
- [#3172648](https://www.drupal.org/project/jsonapi_views/issues/3172648):
  Attachment display showing incorrect results.
