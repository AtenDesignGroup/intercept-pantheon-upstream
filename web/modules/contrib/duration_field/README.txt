CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Form API Element
 * Maintainers


INTRODUCTION
------------

The Duration Field module creates a new Form API form element of type duration,
as well as a Field API field of type duration. A duration is a time period, for
which the granularity can be adjusted to collect any or all of years, months,
days, hours, minutes, and seconds.

This module makes no assumptions as to the type of duration a user would want to
collect, so as such, if they wished, a user could choose to collect years and
seconds only, though generally that wouldn't make sense.

Dates are stored in the system as ISO 8601 Durations.

 * For a full description of the module visit:
   https://www.drupal.org/project/duration_field

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/duration_field


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

INSTALLATION
------------

 * Install the Duration Field module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

To work with the field value programmatically, the field value can be used to
construct a DateInterval object as follows:

<?php
$duration = new \DateInterval($field_value);

// Output years:
$duration->format('%y');

// Output months:
$duration->format('%m');

// Output days:
$duration->format('%d');

// Output hours:
$duration->format('%h');

// Output minutes:
$duration->format('%i');

// Output seconds:
$duration->format('%s');
?>


FORM API ELEMENT
----------------

New form elements can be created within the Form API as follows:

<?php
$element['duration'] = [
  '#type' => 'duration',
  '#title' => t('Duration'),
  '#granularity' => 'y:d:m:h:i:s',
  '#required_elements' => 'y:d:m:h:i:s',
  '#default_value' => 'P1Y2M3DT4H5M6S',
];
?>

* #granularity - A list of elements to show, separated by colons. Included
  elements will be shown in the form element.
    y - years
    m - months
    d - days
    h - hours
    i - minutes
    s - seconds

    Default - y:m:d:h:i:s

* #elements - A list of elements to be required, separated by colons.
  Elements listed will be required within in the form element. Keys are the same
  as for #granularity

* #default_value' - The default value for the element, in Iso8601 duration
  format.


MAINTAINERS
-----------

 * Jay Friendly (Jaypan) - https://www.drupal.org/u/jaypan
