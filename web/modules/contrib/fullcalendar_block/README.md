CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Features


INTRODUCTION
------------

This module provides a block to display a calendar powered by FullCalendar 5.

It creates a block called 'FullCalendar block' which accepts event source as a json (URL) feed.

The URL can be a relative or absolute link. You may use a relative link if the event source is a Drupal view.

Links:
* For a full description of the module visit:
  https://www.drupal.org/project/fullcalendar_block

* To submit bug reports and feature suggestions, or to track changes visit:
  https://www.drupal.org/project/issues/fullcalendar_block

REQUIREMENTS
------------

This module requires FullCalendar library (https://github.com/fullcalendar/fullcalendar).

It may also make use of DOMPurify (https://github.com/cure53/DOMPurify) to handle sanitization of HTML when rendering
custom event descriptions in a dialog popup to prevent potential XSS attacks.

As well as support for the Moment (https://fullcalendar.io/docs/moment-plugin) and RRule (https://fullcalendar.io/docs/rrule-plugin) plugins.

By default, all required libraries will be loaded from a CDN (usually https://unpkg.com) automatically if they could not
be located on disk. A cache clear is typically required following a change in the library states.

INSTALLATION
------------

### COMPOSER INSTALLATION

  You can install this module as a normal installation process of Composer.

  Optionally, if you'd like to automatically install the external libraries into your local Drupal instance, then you may add `drupal/fullcalendar_block` as a `drupal-libraries-dependencies` of the [zodiacmedia/drupal-libraries-installer](https://github.com/zodiacmedia/drupal-libraries-installer#installing-libraries-declared-by-other-packages) plugin.
  * Ensure that packages of type `drupal-library` are [properly configured to install](https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies#s-define-the-directories-to-which-drupal-projects-should-be-downloaded) to the `/libraries` directory.
  * Add the composer dependency: `composer require zodiacmedia/drupal-libraries-installer`.
  * Add the library to the `zodiacmedia/drupal-libraries-installer` Drupal libraries dependencies list:
    ```json
    {
      "extra": {
        "drupal-libraries-dependencies": [
          "drupal/fullcalendar_block"
        ]
      }
    }
    ```

Then run `composer install` as normal.

### MANUAL INSTALLATION

If you are manually installing the module and require the external Javascript libraries to be loaded from your server,
then you may download and extract the following into your Drupal root directory:

| URL                                                                 | Destination                      |
|---------------------------------------------------------------------|----------------------------------|
| https://registry.npmjs.org/fullcalendar/-/fullcalendar-5.11.4.tgz   | `/libraries/drupal-fullcalendar` |
| https://registry.npmjs.org/dompurify/-/dompurify-2.4.5.tgz          | `/libraries/DOMPurify`           |
| https://registry.npmjs.org/moment/-/moment-2.29.4.tgz               | `/libraries/moment`              |
| https://registry.npmjs.org/rrule/-/rrule-2.7.1.tgz                  | `/libraries/rrule`               |
| https://registry.npmjs.org/@fullcalendar/rrule/-/rrule-5.11.4.tgz   | `/libraries/fullcalendar-rrule`  |
| https://registry.npmjs.org/@fullcalendar/moment/-/moment-5.11.4.tgz | `/libraries/fullcalendar-moment` |

If the required external libraries are unavailable locally, then **they will be loaded from a CDN** (usually https://unpkg.com).

#### EXTRA FILES

Please be advised that there is a demo folder (`rrule/dist/esm/demo`) in the RRule zip file, in which there is an
`index.html` file (see [PSA-2011-002](https://www.drupal.org/node/1189632)).

And if the libraries are being manually installed, then you should probably delete the `demo` folder on your server
post-installation.

However, if the RRule library is being loaded from the CDN or composer (with integration with the
`zodiacmedia/drupal-libraries-installer` plugin), then you do not need to worry about this step.

CONFIGURATION
-------------

1. Go to the Block layout configuration page (`/admin/structure/block`).
2. Click the 'Place block' button in the area you want the calendar block take place.
3. In the pop-up dialog search 'FullCalendar block' and then place the block.
4. Click the 'Configure' button of the block to set up the block.
5. The 'Event source URL' is a mandatory field pointing to the JSON feed URL providing the event data of the calendar.
   This can be a relative path to internal pages such as a Drupal "REST export" views, custom Controllers, or an
   absolute URL pointing to another external event data provider.
6. The 'Advanced settings' provides the capability to add or modify any options of the FullCalendar, which has to be valid JSON/YAML format text. For example, following will set the initial date at 2022-05-01,

   ```yaml
   initialDate: '2022-05-01'
   ```

7. The 'Advanced Drupal settings' provides support for more advanced Fullcalendar behaviour relating to the dialog, which has to be valid JSON/YAML format text. Available options are as follows:
   ```yaml
   # The dialog modal type.
   dialog_type: 'modal'
   # Additional options to pass through to the Drupal modal.
   dialog_options:
     # Disable Drupal's default autoResize feature on all blocks.
     autoResize: false
   # Whether to enable integration with jquery_ui_draggable if installed.
   draggable: false
   # Options to pass over to jquery_ui_draggable.
   draggable_options: {}
   # Whether to enable integration with jquery_ui_resizable if installed.
   resizable: false
   # Options to pass over to jquery_ui_resizable.
   resizable_options: {}
   # Show event description in dialog window.
   # If enabled, the description will show in a dialog window upon clicking the event.
   description_popup: false
   # Event description field name to use for the popup dialog.
   description_field: 'des'
   # Assume the event titles might contain valid HTML, so attempt to strip out the tags.
   html_title: false
   # Event field name to optionally signify when or when not to sanitize an event title.
   raw_title_field: 'rawTitle'
   # Event background color setting.
   event_background:
     # The name of the field used to distinguish event color. It could be the content type.
     field_name: field_event_type
     # The field value and the according color code, or the content type id.
     color_map:
       - '5 #ff0000'
       - '6 #ff0000'
       - '7 #0000ff'
   ```

8. After saving the configurations, you might need to clear the cache to apply the changes, but it's typically not
   necessary.

9. (Optional) If you are using a Drupal view as the data source and you want to load events via AJAX, you need to expose the view filter as the start day and end day and then name them accordingly. The view URL looks like this,
/event-feed?start=2023-01-01T00%3A00%3A00-05%3A00&end=2023-02-12T00%3A00%3A00-05%3A00
You can get an example from
https://www.drupal.org/project/fullcalendar_block/issues/3345963


DEVELOPMENT
-------------
1. During development, you may switch to non-minified assets by specifying the following in your local
   `settings.php` and clearing the Drupal cache:

    ```php
    // Enable non-minified assets.
    $settings['fullcalendar_block.dev_mode'] = TRUE;
    ```

2. There is a hook called `hook_fullcalendar_block_settings_alter` for other modules to modify the block settings,
   including the calendar options. For more details about this hook see:
   https://git.drupalcode.org/project/fullcalendar_block/-/blob/1.0.x/fullcalendar_block.api.php

3. JavaScript events.
   - `fullcalendar_block.beforebuild`
     An event to let other modules know that a calendar will be built.
     Parameters:
     - `event`: The event object.
     - `calendarOptions`: The calendar options object.

   - `fullcalendar_block.build`
     An event to let other modules know when a calendar has been built.
     Parameters:
     - `event`: The event object.
     - `blockInstance`: The block instance object containing
       - `element`: The fullcalendar block DOM element.
       - `index`: The fullcalendar block index.
       - `calendar`: The Fullcalendar instance.
       - `calendarOptions`: The calendar options object.
       - `blockSettings`: The block settings object.

FEATURES:
-------------
* FullCalendar 5.
* Fully configurable via the block setting.
* Support simple recurring events out of box.
* Support loading events via Ajax once needed.
* Support the moment and RRule fullcalendar plugins
