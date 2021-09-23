Provides a block type which renders views display exposed filters separately
from the view.
It's like [Views Block Exposed Filter Blocks]
  (https://www.drupal.org/project/views_block_filter_block "Views Block Exposed Filter Blocks") module but works for all types of view display plugins (for example for [eva view displays](https://www.drupal.org/project/eva) which was what I needed) and solves the problem "the other way around". With this module you select the view and display with the exposed filters to render within the block configuration, not within the view.
If you only need exposed filters in blocks for a views block display plugin, I suggest to use https://www.drupal.org/project/views\_block\_filter_block or simply try out which of those two fits best.
Based on the implementations like: https://blog.werk21.de/en/2017/03/08/programmatically-render-exposed-filter-form or https://drupal.stackexchange.com/questions/236576/render-exposed-filter-without-creating-block Thanks to the authors!


Installation & use
------------------
1.  Enable the module
2.  Go to block layout (admin/structure/block)
3.  Add a block of category "Views exposed filter blocks" - Simply click
    "Place block" on the block administration page and search for
    "Views exposed filter blocks". You may add as many of these blocks
    as you need.
4.  Select the view & display which holds the exposed filters
5.  Place the block into the region where to display the exposed filters
    and eventually configure display rules / paths.
6.  Disable AJAX in the view you'd like to use (with ajax is untested)
7.  Place block and result view on the same page so that the filter arguments
    can be handled by the result view


Alternative modules
-------------------
*   https://www.drupal.org/project/views\_block\_filter_block (Drupal 7 & 8 but only for views block displays)


Drupal 7
--------
This module will never have a Drupal 7 release.
Simply use the great https://www.drupal.org/project/views\_block\_filter_block



Development proudly sponsored by German Drupal Friends & Companies:
-------------------------------------------------------------------
[webks: websolutions kept simple](http://www.webks.de) (http://www.webks.de)
and [DROWL: Drupalbasierte Lösungen aus Ostwestfalen-Lippe](http://www.drowl.de) (http://www.drowl.de)
