# Webform Autosave

This module enables webforms to be saved as drafts automatically whenever a
user makes changes to any input or select element on the form. The module
utilizes jQuery to identify load and change events and automatically submits
the form when these events occur.

The webform autosave module has an Optimistic Locking option that prevents
multiple users from accidentally overwriting each other's changes when editing
the same webform submission.

## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers

## Requirements

This module requires the following modules:
- [Webform](https://www.drupal.org/project/webform)

## Installation

After [enabling the module](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules),
head to the general settings page of your webform and switch on the auto-save
function in the third-party settings section.

## Configuration

1. Enable the module at Administration > Extend.
2. Go to your webform's general settings page and enable the auto-save feature
   under the third-party settings section

## Maintainers

Current maintainers:

- Ryan McVeigh - [rymcveigh](https://www.drupal.org/u/rymcveigh)

Supporting organizations:

- [Morris Animal Foundation](https://www.drupal.org/morris-animal-foundation)
