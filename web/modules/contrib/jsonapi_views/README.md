# JSON:API Views

[![Pipeline](https://git.drupalcode.org/project/jsonapi_views/badges/8.x-1.x/pipeline.svg)](https://git.drupalcode.org/project/jsonapi_views/-/pipelines)
[![Test](https://github.com/Decipher/jsonapi_views/actions/workflows/test.yml/badge.svg?branch=8.x-1.x)](https://github.com/Decipher/jsonapi_views/actions/workflows/test.yml?query=branch%3A8.x-1.x)
[![Coverage](https://codecov.io/gh/Decipher/jsonapi_views/branch/8.x-1.x/graph/badge.svg)](https://codecov.io/gh/Decipher/jsonapi_views/branch/8.x-1.x)

It creates a [JSON:API Resource](https://www.drupal.org/project/jsonapi_resources)
for each [Views](https://www.drupal.org/docs/8/core/modules/views) display,
allowing for easy consumption of that data outside of Drupal.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/jsonapi_views).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/jsonapi_views).

## Table of contents

- Requirements
- Installation
- Features
- Maintainers

## Requirements

- Drupal 10 or 11
- [Views](https://www.drupal.org/docs/8/core/modules/views) (Drupal core)
- [JSON:API Resources](https://www.drupal.org/project/jsonapi_resources)

## Installation

1. Download and install via Composer:

   ```bash
   composer require drupal/jsonapi_views
   ```

2. Enable the module:

   ```bash
   drush en jsonapi_views
   ```

## Features

When installed, the module activates a JSON:API resource for every enabled
View display. The resource can be disabled per-display by editing the view
and unchecking "Expose via JSON:API" in the display's JSON:API settings.

The URL of the JSON:API Views resource is shown while editing a view, based
on the view's current preview state (filters, pagination and sorts included).

- JSON:API resource per View display: `/jsonapi/views/{{ viewId }}/{{ displayId }}`
- Pagination: `?page=#`
- Exposed filters: `?views-filter[{{ filter }}]={{ value }}`
- Contextual filters: `?views-argument[]={{ value }}`
  - Multiple arguments as such: `?views-argument[]={{ value }}&views-argument[]={{ value2 }}`
- Exposed sorts: `?views-sort[sort_by]={{ sortId }}`

## Maintainers

- Stuart Clark - [deciphered](https://www.drupal.org/u/deciphered)
- John Ferris - [pixelwhip](https://www.drupal.org/u/pixelwhip)
