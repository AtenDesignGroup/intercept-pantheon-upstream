# Views Exposed Filter Blocks <!-- omit in toc -->


## Table of contents <!-- omit in toc -->

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Alternative modules](#alternative-modules)
- [Drupal 7](#drupal-7)
- [Maintainers](#maintainers)
- [Sponsors](#sponsors)


Provides a block type which renders views display exposed filters separately
from the view.
It's like [Views Block Exposed Filter Blocks](https://www.drupal.org/project/views_block_filter_block)
module but works for all types of view display plugins (for example for
[eva](https://www.drupal.org/project/eva) view displays which was what I needed)
and solves the problem "the other way around". With this module you select the
view and display with the exposed filters to render within the block
configuration, not within the view.

If you only need exposed filters in blocks for a views block display
plugin, I suggest to use [views_block_filter_block](
<https://www.drupal.org/project/views\_block\_filter_block>) or simply try
out which of those two fits best.

Based on the implementations like:
[Programmatically render an exposed filter form](
<https://blog.werk21.de/en/2017/03/08/programmatically-render-exposed-filter-form>)
or [Render exposed filter without creating block](https://drupal.stackexchange.com/questions/236576/render-exposed-filter-without-creating-block)
Thanks to the authors!


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. Enable the module
1. Go to block layout (admin/structure/block)
1. Add a block of category **Views exposed filter blocks** - Simply click
    **Place block** on the block administration page and search for
    **Views exposed filter blocks**. You may add as many of these blocks
    as you need.
1. Select the view & display which holds the exposed filters
1. Place the block into the region where to display the exposed filters
    and eventually configure display rules / paths.
1. Disable AJAX in the view you'd like to use (with ajax is untested)
1. Place block and result view on the same page so that the filter arguments
    can be handled by the result view


## Alternative modules

- [views_block_filter_block](https://www.drupal.org/project/views\_block\_filter_block)
(Drupal 7 & 8 but only for views block displays)


## Drupal 7

This module will never have a Drupal 7 release.
Simply use the great [views_block_filter_block](https://www.drupal.org/project/views\_block\_filter_block)


## Maintainers

Current maintainers:

- [Julian Pustkuchen (Anybody)](<https://www.drupal.org/u/anybody>)
- [Thomas Frobieter (thomasfrobieter)](<https://www.drupal.org/u/thomasfrobieter>)
- [Joshua Sedle (Grevil)](<https://www.drupal.org/u/grevil>)


## Sponsors

- [webks](https://www.webks.de/): web solutions kept simple
- [DROWL](https://www.drowl.de/): Drupalbasierte Lösungen aus Ostwestfalen-Lippe
