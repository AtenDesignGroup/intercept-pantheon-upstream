# Changelog

All notable changes to this project will be documented in this file.

## [2.0.12] - 2025-04-30

* Added code to delete outdated event reminder SMS messages to prevent them from going out after an event has passed
* Added updates for Drupal 11

## [2.0.11] - 2025-03-26

* Improved the visual consistency and spacing of bulleted lists
* Fixed a bug where clicking the "Calendar" button from the events list mistakenly lead to a past date
* Update tooling in the Intercept modules
* Added Drupal core security update 10.4.5

## [2.0.10] - 2025-02-26

* Removed an unused permission that allowed administrators to book overlapping room reservations
* Prevented rooms that are not reservable online from appearing on the customer-facing room reservation calendar

## [2.0.9] - 2025-01-29

* Made improvements to Google Analytics tracking for areas of the site under My Account
* Fixed a timezone issue with Room Reservation entry
* Fixed a bug with redirection to recurrences tab when saving event data on the analysis tab
* Fixed additional deprecation warnings related to PHP 8.2
* Updated contributed modules to latest versions
* Updated JavaScript build tools

## [2.0.8] - 2024-12-18

* Added validation to prevent staff from accidentally creating a double-booking when creating a recurring event with room reservations

## [2.0.7] - 2024-11-26

* Prevented double-bookings when staff are creating an event series
* Prevented a scenario that would allow customers to double-book a room

## [2.0.6] - 2024-10-30

* Replaced Twitter with X icon.

## [2.0.5] - 2024-09-25

* Fixed an issue with registration pages not working on some recurring events
* Fixed an error that could appear on the event form related to room reservation times when no reservation was being made
* Fixed an issue with some events (with missing location values) not showing up on the staff listing of events
* Fixed an error on the staff-facing form for creating event registrations on behalf of customers

## [2.0.4] - 2024-08-28

* Fixed an issue with the bulk room reservation form not showing all conflicts
* Fixed an issue with event attendance export where some programs were being listed multiple times
* Fixed an issue that prevented some customers from logging in
* Fixed errors on content creation form that prevented room reservations from being created
* Fixed an issue with the “Save Draft” button being hidden in the Gin theme
* Updated date popup, date recur modules to latest versions. Also removed unnecessary/outdated patches.

## [2.0.3] - 2024-07-31

* Updated Reserve By Room to allow staff to reserve all rooms as they can on the calendar
* Fixed an error that could sometimes occur on event registration cancellation and checkins

## [2.0.2] - 2024-06-26

* Removed Deprecation Warnings related to PHP 8.2

## [2.0.1] - 2024-05-28

* Fixed a spacing issue on location pages under "Today's Hours"
* Fixed off-center loading icon on room reservation form
* Updated Date Recur module to latest version
* Added first 2.0.x release

## [8.x-1.0-beta24] - 2024-05-01

* Fixed an issue where customers using iOS Safari would sometimes reserve rooms outside of regular 15 minute intervals
* Fixed a bug when typing in date filters on Events page
* Fixed an issue where dropdowns could lose focus when the Events view refreshed
* Fixed an issue on the Events page where deselecting options within multi-value filters required two clicks
* Fixed issue in the Intercept upstream with missing configuration files for location slideshows
* Fixed a bug where events could not be saved in a draft state
* Added additional logging for study room reservations in order to help track down double-booking issues
* Fixed an issue with event registration where registered customers were sometimes unable to view the registration page for that event

## [8.x-1.0-beta23] - 2024-03-29

* Updated the Event Data Dashboard to disable filter options that are excluded from the current set of filtered results
* Updated the room reservation denial message to ask customers to contact the library location to discuss alternate arrangements
* Updated the event entry page to display a more prominent warning message to staff when their event includes a room reservation that conflicts with an existing room reservation
* Consolidated Intercept Staff and Intercept Event Organizer roles
* Fixed issue where staff feedback on events wasn’t saving as expected
* Fixed a bug on the events listing page where customers could not de-select locations
* Removed some unnecessary configuration that was used in the old version of customer event feedback
* Redesigned the Event Feedback mechanism used by customers
* Improve customer interface for filtering rooms by location when reserving by room
* Ensured that customers can see the "Add to my Calendar" link once they reserve a room using the "by room" interface
* Fixed a bug that allowed double-booking by staff when editing an existing event with a reservation
* Fixed an issue with inconsistent room reserve button sizes on location pages
* Fixed an error when attempting to clone an existing event
* Fixed an issue where printable sign-in sheets for events were not loading
* Removed some unused code on the event list

### Note for Intercept libraries

In this release we’ve restructured the customer feedback mechanism for events. This mechanism previously relied on the Voting API system and now relies on the Webform system. After upgrading to this version of Intercept, it’s recommended that you run all database updates to ensure that feedback is transitioned into this new system and then legacy feedback “votes” should be removed from your system using drush via the following command:

`drush entity:delete vote --bundle=evaluation`

Configuration files related to the old Voting API setup will be removed from Intercept in a future release.

## [8.x-1.0-beta22] - 2024-01-26

 - Fixed an issue on the room reservation calendar where staff were sometimes unable to update guest reservation information
 - Fixed an issue on Reserve By Room where invalid warnings could appear regarding the chosen time slot
 - Fixed a date discrepancy on the Events listing page
 - 3412547: Added a check for query parameters for sorting past events
 - 3412543: Fixed an issue where event registration waitlist capacity could sometimes display a negative number
 - Fixed an error in equipment reservations when the piece of equipment is missing a primary image


## [8.x-1.0-beta21] - 2023-12-15

- Updated room reservation calendar to keep the selected times when a user logs in
- Updated room reservation calendar to automatically filter to location of last reservation for logged in customers
- Fixed an issue where staff could accidentally enter negative number of attendees for an event
- Fixed an issue where staff were unable to change a reservation from Approved to Requested status

## [8.x-1.0-beta20] - 2023-12-04

- Staff events listing - Added a new event filter for no attendance recorded
- Staff events listing - Updated attendance numbers to show the difference between programs with 0 and null attendance
- Fixed a bug where study room reservations were not being automatically approved after customer edits
- Fixed a couple of room reservations double booking scenarios
- Fixed a warning that could sometimes appear when viewing room reservations
- Fixed display issue on loading icon when used in off-canvas room reservation dialogue
- Fixed display issue on room reservation calendar titles being cut off slightly when viewing room details
- Fixed an issue in the Intercept upstream where users were unable to reserve rooms by calendar easily

## [8.x-1.0-beta19] - 2023-10-27

<h2>Sprint 59 Deployment</h2>

- Added an automatic data refresh feature to the room reservation calendar
- Updated the "reserve by room" feature to keep the currently-selected filters when a customer logs in
- Updated the "reserve by room" feature to take anonymous users straight to log in page when they click green "Reserve" button
- Fixed a bug where some website requests could result in errors when JavaScript was involved (related to Material Icons library)
- 3393035: Replace calls to deprecated method OfficeHoursDateHelper::datePad()

## [8.x-1.0-beta18] - 2023-09-01

<h2>Sprint 58 Deployment</h2>

- Customers and staff can now see events color-coded by their primary audience on the printable event calendar (requires using a subtheme of intercept_base, overriding the intercept_base/fullCalendar theme library, and therein defining colors per audience).
- Clarified the "Usage" filter under the calendar view of Room Reservations
- Began research & development on reworking customer feedback options. This redesigned feature is planned to become available in Q4.
- Room Reservation Entry - Moved the Status field to a more logical position.
- Fixed an issue with group name being mistakenly required the customer re-edits an existing room reservation.

## [8.x-1.0-beta17] - 2023-09-01

<h2>Sprint 57 Deployment</h2>

We had some issues with our post-deployment process at the end of July and ended up not sharing the Drupal 10 changes as expected. We've fixed a number of those bugs in this release and everything that was planned to be included in the previous release is now included in this release (see the 8.x-1.0-beta16 section in the Intercept module's CHANGELOG.md for more specifics). Here are the items we completed in beta17 specifically:

- Staff and customers can now add room reservations to their calendars using the "Add to my Calendar" button which was previously available only on events
- Added a new "past" tab to customer room reservations list so they can more easily rebook a room they've used in the past
- Added new hooks to allow messaging and functionality to prevent customers from booking meeting spaces under children's library cards (dependent upon ILS module for gathering birthdates)
- Updated the customer email reminder for events to be sent 24 hours before the event (previously it was 72 hours)
- Updated room reservations in the “archived” status to not appear on customer & staff calendars
- Standardized some terminology for "check-in", "scan in", and "check in"
- Fixed some spacing issues on content pages
- Fixed an issue where room reservation calendar filters could be cut off on smaller screens
- Fixed a number of issues in the Intercept upstream including missing edit tabs at initial installation

## [8.x-1.0-beta16] - 2023-07-28 - Canceled

<h2>Sprint 56 Deployment</h2>

- Updated to Drupal 10
- Added the "Reserve Room" Button to "Room Details" pages for customers
- Staff are now prompted to enter a location name and address when creating a "Community Event"
- Fixed a visual bug on the website header when staff cancel a room reservation
- Fixed data export issue with blank end dates on some room reservations
- Fixed some visual issues found in our last design audit
- Removed “event designation” field which is no longer in use

## [8.x-1.0-beta15] - 2023-06-30

<h2>Sprint 55 Deployment</h2>

One of the biggest changes you'll notice in this release is the change to the events page. We've transitioned to a non-React version of the events page and calendar to make development changes easier to manage. Existing Intercept site admins may want to re-import the events view configuration from the intercept_events module directory in order to be sure that they have the latest changes in place for the events listing page and calendar.

- Added "Add to Calendar" links in event registration confirmation emails
- Added Certifications to My Account Room Reservations
- Updated the field order on event edit form
- Added improvements to help event creators create good alt text for images
- Added character limit information to event title description
- Added a reminder email for staff to enter event attendance once the event has ended
- Removed Terms of Service Checkboxes for staff creating/editing room reservations
- Updated the back end infrastructure of the events page to make development changes easier
- Updated holiday closings staff listing to be sortable
- Fixed error with “Today’s Hours” incorrectly displaying on location pages

## [8.x-1.0-beta14] - 2023-05-26

<h2>Sprint 54 Deployment</h2>

- Updated the "End date" field on the staff room reservation form to update automatically when the start date is updated
- Fixed an issue on the staff events list where no events would appear if a single day was selected
- Improved the visibility of room reservation conflict messages when staff are creating an event
- Add more updates to prepare for the Drupal 10 upgrade
- Fixed an issue with the publishing options not appearing in the sidebar when creating an event from a template
- Fixed issues in printable sign-in sheets for events where canceled customer registrations were being shown and where guest registrations were not being shown

## [8.x-1.0-beta13] - 2023-04-28

<h2>Sprint 53 Deployment</h2>

- Added warnings to event creators when creating events that take place during a library closing
- Made it easier for staff who administer closings to see and administer events listed during closings
- Fixed color of checkboxes on My Events
- Updated green colors in room reservation screens to match green used throughout site
- Updated the event form so that the teaser text is required
- Updated customer search screen to display customer certification notes

## [8.x-1.0-beta12] - 2023-04-06

<h2>Sprint 52 Deployment</h2>

- Added an equipment request cancellation email notification
- Fixed issue with customer profile settings not appearing correctly in Intercept upstream
- Created a way for staff to view event organizer details
- Updated text reminder to customers to be timed at 24 hours before the event
- Updated the “by calendar” version of the room reservation form to include the same description of refreshments that is on the “by room” version
- Fixed a bug that prevented customers from making room reservations immediately after the ending of a reservation
- Fixed PHP error related to tallies on events for HMCPL
- Updated Intercept, Intercept Base theme, and Intercept Profile in preparation for Drupal 10

## [8.x-1.0-beta11] - 2023-02-24

<h2>Sprint 51 Deployment</h2>

- Added a new data export option for equipment reservation reporting
- Ensured a new revision is recorded each time field changes happen on room reservations
- Fixed a bug regarding form field focus for the room reservation form
- Prevented customers from entering 0 attendees when creating a room reservation
- Fixed a bug on the room reservation calendar that prevented drag and drop functionality from working correctly
- Fixed a visual issue with focus outlines in the My Account menu

## [8.x-1.0-beta10] - 2023-01-27

- Added "hosting location" field for online events
- Fixed bug when staff re-generate recurring events
- Added Drupal 9.5 update

## [8.x-1.0-beta9] - 2022-12-16

- Ensured uneditable room reservation detail displays after saving
- Fixed misaligned fields on room reservation calendar view
- Made event email notifications style-able
- 3326093: Fixed issue with mobile logo in Intercept Base theme
- Added Drupal 9.4.9 update

## [8.x-1.0-beta8] - 2022-12-02

- Fixed issue in Intercept Profile with image uploads on rooms
- Allow customers to opt in to text and email notifications during event registration
- Added the ability to review customer feedback on the event data dashboard
- Fixed unnecessary redirect when staff change room reservation status
- Fixed disappearing options when clicking "EDIT" on customer reservation
- Fixed room reservation calendar date selection error
- Fixed issue with automatic room reservation approvals not working

## [8.x-1.0-beta7] - 2022-10-28

- Added the ability to view staff comments on the event data dashboard
- Updated a few My Account menu items for easier understanding
- Rebuilt the Bulk Room Reservations page to be more user friendly for staff
- Reviewed Intercept code and made improvements via Coder
- Bulk Room Reservation - Fixed warning messages when invalid times are entered
- Fixed automatic approval bug in staff room reservations when status is set to "requested"
- Fixed triangle alignment on location detail pages
- Fixed text field label overlap on form fields
- Fixed bug in customer certification lookup when customer notes are present
- Limited customer’s ability to edit and cancel a room reservation after the reservation has begun
- Fixed issue with unpkg.com scripts not loading

## [8.x-1.0-beta6] - 2022-10-03

- Fixed Intercept: Event Teaser Not Using Serifed Font For Location Details
- Room Reservation Form Updates:
  - Certifications - Indicate Expired Polaris Accounts
  - Fixed: "Reserved for" section is auto-filling the admin (who is logged in) as who is reserving the room
  - Room Reservations (Staff side) should allow staff to override minimum room attendee numbers
  - Staff Room Reservation Entry: Outstanding Reservation Warning
- Updates to My Account > Settings Heading
- When Event is Canceled, No More Registrations Should Be Allowed

## [8.x-1.0-beta5] - 2022-08-26

- Chart: Attendees by Hour and Day of Week
- Fixed: Dragging Staff Room Reservation Can Result in Double-Booking (No Error Message)
- Share Navigation Updates With Intercept Base Theme
- Fixed: Bulk Reservations Timing Out When "Never" is Selected as End Date
- Allow Event Organizers (Not Just Original Event Author) to View/Edit "How'd the event go?" Field Value
- Fixed: Room Reservation Calendar Customer Double Booking
- Update "Your Current Contact Information" Instructions to Say "My Account > Settings"
- Bulk Reservation Blocks
- Favorite Event Location

## [8.x-1.0-beta4] - 2022-08-04

- Chart: Attendees by Primary Audience
- Event Data Download CSV
- Fixed: Event Organizers Cannot Access Event Data Dashboard
- Create quick links for Export Event Attendance
- RR CALENDAR Notify anonymous users on /reserve-room/by-calendar that they must be logged in
- Fixed: Customers Still Registered for Canceled Events
- Fixed: Event Entry Draft Screen
- Fixed: Customers Reserving Rooms After Library is Closed
- Fixed AJAX callback on equipment reservation form
- Fixed: Staff Can Double-Book Rooms When Editing a Room Reservation
- Fixed: Recurring Event Creation - After Number of Occurrences Input Not Appearing Immediately
- Adding Common Event Messaging Types For Disclaimers
- Add Group Name to Bulk Reservations

## [8.x-1.0-beta3] - 2022-06-23

- Text Notification to Customer when Event is Canceled
- Add Customer Email Address and Barcode to Room Reservation Export
- Event Recurrences - Error When the Recurrence End Date is Set to "Never"
- Bulk Room Reservation - Allow Staff to Exceed Max. Duration
- Improved Printable Versions of Events and Room Reservations
- Fixed: Room Reservation Calendar Allowing Negative Attendees
- Add Back to Top Button to Intercept Base
- Fixed: Minimum Attendees Not Enforced Correctly on Reserve Room by Calendar

## [8.x-1.0-beta2] - 2022-05-27

- Make Refreshments Notice Configurable
- Ability to generate and download attendance rosters/printable sign-in sheets that include blank spaces to record non-registered attendees (3279478)
- Bypass logging in when clicking link to rate event in text or email
- Customer Room Reservation Group Name
- Chart: Attendees by Event Type
- Table view of each metric
- Fix contrast on hover state of My Account menu items
- Adding Expand/Collapse Feature to Events Page (3275341)
- Fixed: Unable to reserve rooms on mobile (by Calendar)
- Fixed: Copying Room Reservation Incorrectly Lists Staff in the “Reserved For” Field
- Consolidate room reservation availability logic
- Deprecate or integrate intercept_core/delay_keyup library on RoomReservationForm
- Fixed: Unable to reserve rooms on mobile (by Room)
- Fixed Theme Error on Export Scans Page
- Remove Link to Customer Lookup on Scan Tab

## [8.x-1.0-beta1] - 2022-05-02

- Convert System Config links from cards to buttons
- Mobile Self Checkin
- Event Data Visualization Dashboard
- Updated header and search bars
- Event Form Improve field descriptions
- Fixed: Single Day View in Calendar Mode Sometimes Will Not Load All Events
- Room Reservation: Filter Out White Spaces
- Fixed: Bulk Room Reservation Safari Bug
- Fixed: Customer Room Reservation calendar makes incorrect assumptions about certifications
- Fixed: Cannot Update Room Reservation Notes
- Remove "Votes" Tab from Intercept Upstream Node Tabs
- Fix location dropdown on small laptop screens
- Fixed: Contrast Issue on Room Reservation Calendar
- Correct validation for room reservations
- Fixed: Missing Edit Button for Intercept Event Organizer Role on Event Registrations
- Add Permission to View and Use the Analysis Tab for Intercept Staff
