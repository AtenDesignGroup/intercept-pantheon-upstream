# Drupal 9 Install Profile
Installs Drupal 9 with Intercept dependencies.

## Config Files

### Best Practices for Sharing Changes to Config Files

When a config file related to Intercept is updated, the change to the config file should also be shared as part of the Intercept codebase so that new installations can leverage that change. Also existing sites will have the option to potentially update their config by importing the updated config file. Before the config changes are ready to share, usually one or more changes to the config file(s) being shared will be necessary as outlined below.

1. Remove all of the UUIDs and default_config_hash from the shared config files so that they don't conflict with those of new sites.
2. Most of the Views-generated config files will contain references to roles that are not present by default in Intercept such as "blog_author", "page_editor", etc. These references should be removed.
3. Most of the Views-generated config files will contain one or more reference to the Views AJAX History module  (look for references to "ajax_history") which is not a part of the Intercept project. These references should be removed.

### List of Intercept Profile and Intercept Config Files
In order to make code contributions easier, we've listed all config files that are a part of either Intercept Profile or the Intercept modules here in this directory.

See: intercept_profile_configs.txt for a current list of all config files.