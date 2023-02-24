# Changelog

All notable changes to this project will be documented in this file.

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
