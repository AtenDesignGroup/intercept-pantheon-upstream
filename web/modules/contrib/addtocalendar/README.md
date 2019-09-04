CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Module Details
 * Recommended modules
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers


INTRODUCTION
------------
Add to Calendar Module integrates 'Add to Calendar' button provided by
addtocalendar.com which supports iCalendar, Google Calendar, Outlook,
Outlook Online and Yahoo Calendar. It's basically provides two options
* A thirdparty field formatter setting available for Datetime and Datetime Range
  field formatters.
* A new addtocalendar type field which can be used to add 'Add to Calendar'
  button to any entity and hence provide integration with views.


MODULE DETAILS
--------------
Add to Calendar Module makes use of ADDTOCALENDAR.COM's service which
provides free button for event page on website and email. Button
supports all modern browsers and platforms: iOS, iPad, iPhone, Android,
Windows, Blackberry. Provides handful of configuration for a really
flexible experience.

Module internally extends Datetime and Datetime Range field formatters using
third party settings to append the "Add to Calendar" button besides date field.
If the date field is multivalued than its configurable to show the button beside
particular date field or for all.

Module also provides a new addtocalendar field type which can be used to add
"Add to Calendar" button, Also it provides the option for end user to decide
if they want to enable "Add to Calendar" button or not.This new field can then
be easily used in views to show "Add to Calendar" button with custom listings.

When clicked on the provided button, the event is exported to the corresponding
website with proper information in the next tab where user can add the
event to their calendar.

External CSS and JS files are provided by Addtocalendar.com which are being used
by this module directly.If these are needed locally, we should consider using
Library-overrides.

SIMILAR MODULES
---------------
* Addtocal (https://www.drupal.org/project/addtocal)
  A field formatter providing a widget for exporting events to:
  Google Calendar
  Yahoo! Calendar
  iCal
  Outlook


CONFIGURATION
-------------
* Style: As of now 3 styles are available (Blue, Glow Orange and Basic).

* Display Text: Text to be displayed on the button.

* Title, Description, Location, Organizer, Organizer Email, End Date:
  These are for collecting event information, which can be configured to
  be collected from other fields, direct static data or tokens.

* Private: Use public for free access to event information from any
  places. User private if the event is closed to public access.

* Security Level: Use https to protect your users.

* List of calendars to show in button list.

Below settings are only available in Add to calendar field:-
* Disabled Text: Text to be displayed if disabled.

* Start Date: Collect event start date and provide configuration similar to end
  date.

* Show add to calendar widget: Available to end user for allowing the user to
  disable the widget on particular event or expired events or show a message
  like event expired from Disabled Text option.

TROUBLESHOOTING
---------------
As of now, Add to calendar Module uses Addtocalandar.com service and
css, js files. In case the module is not working properly, you may try:
* Rebuilding the cache
* Reinstalling the module.
* Try different style.


MAINTAINERS
-----------
Current maintainers:

 * Purushotam Rai (https://drupal.org/user/3193859)
 * Gaurav Garg (https://www.drupal.org/u/gg24)
 * Hemant Gupta (https://www.drupal.org/u/guptahemant)


This project has been sponsored by:
 * QED42
  QED42 is a web development agency focussed on helping organisations and
  individuals reach their potential, most of our work is in the space of
  publishing, e-commerce, social and enterprise.
