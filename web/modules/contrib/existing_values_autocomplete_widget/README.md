# Existing Values Autocomplete Widget

This module provides an autocomplete widget for text fields that suggests
all existing (previously entered) values for that field. This provides more
flexibility than "allowed values" for the content editor to add new values.
At that same time, it is simpler in many cases than creating a taxonomy
vocabulary (no hierarchies, no separate system, no permissions headaches,
no rendered pages per term).

For an analysis of the different use cases for Taxonomy, Text Field with
Allowed Values, and this module read
[this blog post](https://texascreative.com/blog/new-drupal-module-existing-values-autocomplete-widget).


Note: The autocomplete matching is NOT case sensitive. This means that it will
match any cased version of the same characters. The suggestion will be cased
like the first value in the database. If anyone needs configuration on case
sensitivity, we can look into it.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/existing_values_autocomplete_widget).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/existing_values_autocomplete_widget).


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

After installing the module you can configure a form field to use the
autocomplete widget by following these steps:

1. Visit any content type's "Manage Form Display" page.
1. Change the "Textfield" widget of any form field that supports it to
"Autocomplete: existing values".
1. Configure how many suggestions to show in the widget configuration.
(Default: 15)
1. Enter values in the field on the form, and they will become suggestions for
the next time you use that field.
