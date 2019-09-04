Intercept Base

## Installation

Intercept Base requires a few modules to be enabled in order to work correctly.

- [Component Libraries|https://www.drupal.org/project/components]
- [Twig Field Value|https://www.drupal.org/project/twig_field_value]
- [Twig Tweak|https://www.drupal.org/project/twig_tweak]

The modules will be downloaded automatically if you've installed Intercept Base with composer. Otherwise you will need to download them manually. In either case the must be enabled manually as Drupal does not allow themes to set dependencies on modules.

```
drush en intercept_base
drush en components twig_field_value twig_tweak
```
