import moment from "moment";
import drupalSettings from "drupalSettings";
import get from "lodash/get";
import intersection from "lodash/intersection";

//
// User getters
//
export const getUserUtcOffset = () =>
  get(drupalSettings, "intercept.user.utc_offset");
export const getUserTimezone = () =>
  get(drupalSettings, "intercept.user.timezone");
export const getUserName = () => get(drupalSettings, "intercept.user.name");
export const getUserUid = () => get(drupalSettings, "intercept.user.id");
export const getUserUuid = () => get(drupalSettings, "intercept.user.uuid");
export const getUserRoles = () => get(drupalSettings, "intercept.user.roles");
export const getUserStartOfDay = () =>
  moment()
    .tz(getUserTimezone())
    .startOf("day")
    .toDate();
export const getUserTimeNow = () =>
  moment()
    .tz(getUserTimezone())
    .toDate();

export const isUserLoggedIn = () => getUserUid() !== 0;

// Set default moment timezone.
// moment.tz.setDefault(getUserTimezone());

//
// Date Functions
//
export const newUserDate = (date = new Date()) =>
  moment(date)
    .utc()
    .utcOffset(moment().utcOffset() / 60 - getUserUtcOffset(), true)
    .toDate();

// Make sure the current value is a valid date object.
export const ensureDate = date => {
  if (date instanceof moment) {
    return date.toDate();
  }
  if (date instanceof Date) {
    return new Date(date);
  }
  return new Date(date);
};

export const getDateFromTime = (time, date) =>
  moment
    .tz(date, getUserTimezone())
    .hour(time.slice(0, 2))
    .minute(time.slice(2))
    .startOf("minute")
    .toDate();

export const getTimeFromDate = date =>
  moment(date)
    .tz(getUserTimezone())
    .format("HHmm");

// Normalize a date object to a single day. Used to compare days for different dates.
// export const getDayTimeStamp = date => ensureDate(date).setHours(0, 0, 0, 0);
export const getDayTimeStamp = date =>
  moment(date)
    .tz(getUserTimezone())
    .startOf("day")
    .format("YYYY-MM-DD");

export const getDateFromDayTimeStamp = timestamp =>
  moment.tz(timestamp, "YYYY-MM-DD", getUserTimezone()).toDate();

// Get a formatted date string.
export const getDayDisplay = date => {
  const d = getDayTimeStamp(date);
  const today = moment()
    .tz(getUserTimezone())
    .startOf("day");
  const tomorrow = today.clone().add(1, "days");

  // Today
  if (d === getDayTimeStamp(today)) {
    return "Today";
  }
  // Tommorrow
  if (d === getDayTimeStamp(tomorrow)) {
    return "Tomorrow";
  }
  // Friday, October 20, 2017
  return moment(date)
    .tz(getUserTimezone())
    .format("dddd, MMMM D, YYYY");
};

// Get a formatted short date string.
export const getDateDisplay = date => {
  const d = getDayTimeStamp(date);

  // Today
  if (d === getDayTimeStamp(new Date())) {
    return "Today";
  }
  // Tommorrow
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  if (d === getDayTimeStamp(tomorrow)) {
    return "Tomorrow";
  }
  // Friday, October 20, 2017
  return moment(date)
    .tz(getUserTimezone())
    .format("M/D/YYYY");
};

// Get a formatted time string
//   Example: '2p.m.'
export const getTimeDisplay = date =>
  // 2p.m.
  moment(date)
    .tz(getUserTimezone())
    .format("h:mm a")
    .replace("m", ".m.");

// Get a formatted time string
//   Example: '07/13/79 2p.m. to 4p.m.'
export const getDateTimespanDisplay = ({ date, start, end }) =>
  // const { date, start, end } = this.props.values;
  `${getDateDisplay(date)} ${getTimeDisplay(
    getDateFromTime(start)
  )} to ${getTimeDisplay(getDateFromTime(end))}`;

// Gets the duration in minutes of an event based on start and end time.
export const getDurationInMinutes = (start, end) =>
  moment.duration(moment(end).diff(moment(start))).asMinutes();

// Converts a Date object to a Drupal compatible string.
//   Trims `.000Z` off the end.
export const dateToDrupal = date =>
  ensureDate(date)
    .toISOString()
    .replace(".000Z", "")
    .replace(".999Z", ""); // @todo Replace with regex

// Converts a Drupal compatible string to a Date object.
export const dateFromDrupal = date =>
  moment(`${date}Z`, moment.ISO_8601).toDate();

export const roundTo = (
  date,
  value = 15,
  units = "minutes",
  method = "ceil"
) => {
  const duration = moment.duration(value, units);
  return moment(Math[method](+date / +duration) * +duration);
};

//
// User Functions
//

/**
 * Check if the current user is the super admin.
 * This is useful for doing permission checks agains roles because the super admin
 * has no assigned roles and only appears as 'authenticated', despite having permission
 * to do anything.
 *
 * @return {Boolean}
 *   True if the user is super admin.
 */
export const userIsSuperAdmin = () => getUserUid() === "1";

/**
 * Check if the current user has at least one of the provided roles.
 *
 * @param roles {Array}
 *   An array of one or more roles to check against.
 * @return {Boolean}
 *   True if the user has at least one of the provided roles or is a superadmin,
 *   otherwise False.
 */
export const userHasRole = roles =>
  userIsSuperAdmin() || intersection(roles, getUserRoles()).length >= 1;

/**
 * Check if the current user is considered a staff member.
 * This is a shortcut for differenciating staff from customers.
 *
 * @return {Boolean}
 *   True if the user has at least one of:
 *     intercept_event_manager
 *     intercept_event_organizer
 *     intercept_staff
 *     intercept_system_admin
 *     intercept_room_reservation_approver
 *   otherwise False.
 */
export const userIsStaff = () =>
  userHasRole([
    "intercept_event_manager",
    "intercept_event_organizer",
    "intercept_staff",
    "intercept_system_admin",
    "intercept_room_reservation_approver"
  ]);

/**
 * Check if the current user is considered a manager.
 *
 * @return {Boolean}
 *   True if the user has at least one of:
 *     intercept_event_manager
 *     intercept_event_organizer
 *     intercept_system_admin
 *     intercept_room_reservation_approver
 *   otherwise False.
 */
export const userIsManager = () =>
  userHasRole([
    "intercept_event_manager",
    "intercept_event_organizer",
    "intercept_system_admin",
    "intercept_room_reservation_approver"
  ]);
