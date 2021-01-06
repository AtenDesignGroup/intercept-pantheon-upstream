import moment from 'moment';
import drupalSettings from 'drupalSettings';
import get from 'lodash/get';
import intersection from 'lodash/intersection';

//
// User getters
//
export const getUserUtcOffset = () =>
  get(drupalSettings, 'intercept.user.utc_offset');
export const getUserTimezone = () =>
  get(drupalSettings, 'intercept.user.timezone');
export const getUserName = () => get(drupalSettings, 'intercept.user.name');
export const getUserUid = () => get(drupalSettings, 'intercept.user.id');
export const getUserUuid = () => get(drupalSettings, 'intercept.user.uuid');
export const getUserRoles = () => get(drupalSettings, 'intercept.user.roles');

/**
 * Returns a Date object set to the start of
 * the current day in the user's timezone.
 */
export const getUserStartOfDay = () =>
  moment()
    .tz(getUserTimezone())
    .startOf('day')
    .toDate();

export const getUserTimeNow = () =>
  moment()
    .tz(getUserTimezone())
    .toDate();

export const isUserLoggedIn = () => getUserUid() !== 0;

// Set default moment timezone.
// moment.tz.setDefault(getUserTimezone());


/**
 * Converts a datetime from the specified timezone, into
 * that same datetime in the local timezone. This is useful
 * for displaying different timezones in local time.
 * For example:
 *  Converting 2020/11/25 12:30pm New York time to 2020/11/25 12:30pm Los Angeles (as specified by user's device) time.
 * @param {Date} sourceDate
 *  Source Date object.
 * @param {String} timeZoneName
 *  Source timezone.
 * @returns {Date}
 *  A new Date where the hours.
 */
export const convertDateToLocal = (sourceDate, timeZoneName) => {
  const mom = moment.tz(sourceDate, timeZoneName);
  const localDate = new Date(mom.year(), mom.month(), mom.date(), mom.hour(), mom.minute(), 0);
  return localDate;
};

/**
 * Convert a local Date into the same date and time in the destination timezone
 * For example:
 *  Converting 2020/11/25 12:30pm Los Angeles (as specified by user's device) time to 2020/11/25 12:30pm New York time.
 * @param {Date} date
 *  Internal BigCalendar date
 * @param {String} destinationTimeZone
 *  Named timezone, ex. "America/New York"
 */
export const convertDateFromLocal = (localDate, destinationTimeZone) => {
  const dateM = moment.tz(localDate, moment.tz.guess());
  return moment.tz({
    year: dateM.year(),
    month: dateM.month(),
    date: dateM.date(),
    hour: dateM.hour(),
    minute: dateM.minute(),
  }, destinationTimeZone).toDate();
};

//
// Date Functions
//
export const newUserDate = (date = new Date()) =>
  moment(date)
    .utc()
    .utcOffset(moment().utcOffset() / 60 - getUserUtcOffset(), true)
    .toDate();

// Make sure the current value is a valid date object.
export const ensureDate = (date, offset) => {
  if (date instanceof moment) {
    return date.toDate();
  }
  if (date instanceof Date) {
    if (offset === true) {
      return new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
    }
    return new Date(date);
  }
  if (offset === true) {
    return new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
  }
  return new Date(date);
};

/**
 * Ensures a start of day date falls on the user's day.
 *
 * Some inputs and calendar widgets set or expect a 'day'
 * to be the start of day in local timezone (the one set in
 * in the browser or OS). This creates issues if the user's
 * desired timezone is set to a timezone ahead of the local
 * timezone. As the local timezone will actually be one calendar
 * day ahead.
 *
 * @param {Date} date
 *  The input date in the local timezone.
 * @return {Moment}
 *  The Moment date converted to match the user's timezone.
 */
export const normalizeStartOfDay = (date) => {
  const localOffset = moment(date).utcOffset();
  const userOffset = moment.tz(date, getUserTimezone()).utcOffset();
  const userDate = moment(date);

  // If the local offset is less than the
  // user offset, add 1 day.
  if (localOffset < userOffset) {
    userDate.add(1, 'days');
  }

  return userDate;
};

/**
 * Ensures a start of day date falls on the user's day.
 *
 * Some inputs and calendar widgets set or expect a 'day'
 * to be the start of day in local timezone (the one set in
 * in the browser or OS). This creates issues if the user's
 * desired timezone is set to a timezone ahead of the local
 * timezone. As the local timezone will actually be one calendar
 * day ahead.
 *
 * @param {Date} date
 *  The input date in the local timezone.
 * @return {Moment}
 *  The Moment date converted to match the user's timezone.
 */
export const denormalizeStartOfDay = (date) => {
  const localOffset = moment(date).utcOffset();
  const userOffset = moment.tz(date, getUserTimezone()).utcOffset();
  const userDate = moment(date);

  // If the local offset is less than the
  // user offset, add 1 day.
  if (localOffset < userOffset) {
    userDate.subtract(1, 'days');
  }

  return userDate;
};

export const getDateFromTime = (time, date) =>
  moment
    .tz(date, getUserTimezone())
    .hour(time.slice(0, 2))
    .minute(time.slice(2))
    .startOf('minute')
    .toDate();

export const getTimeFromDate = date =>
  moment(date)
    .tz(getUserTimezone())
    .format('HHmm');

// Normalize a date object to a single day. Used to compare days for different dates.
// export const getDayTimeStamp = date => ensureDate(date).setHours(0, 0, 0, 0);
export const getDayTimeStamp = date =>
  moment(date)
    .tz(getUserTimezone())
    .startOf('day')
    .format('YYYY-MM-DD');

export const getDateFromDayTimeStamp = timestamp =>
  moment.tz(timestamp, 'YYYY-MM-DD', getUserTimezone()).toDate();

// Get a formatted date string.
export const getDayDisplay = (date) => {
  const d = getDayTimeStamp(date);
  const today = moment()
    .tz(getUserTimezone())
    .startOf('day');
  const tomorrow = today.clone().add(1, 'days');

  // Today
  if (d === getDayTimeStamp(today)) {
    return 'Today';
  }
  // Tommorrow
  if (d === getDayTimeStamp(tomorrow)) {
    return 'Tomorrow';
  }
  // Friday, October 20, 2017
  return moment(date)
    .tz(getUserTimezone())
    .format('dddd, MMMM D, YYYY');
};

// Get a formatted short date string.
export const getDateDisplay = (date) => {
  const d = getDayTimeStamp(date);

  // Today
  if (d === getDayTimeStamp(new Date())) {
    return 'Today';
  }
  // Tommorrow
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  if (d === getDayTimeStamp(tomorrow)) {
    return 'Tomorrow';
  }
  // Friday, October 20, 2017
  return moment(date)
    .tz(getUserTimezone())
    .format('M/D/YYYY');
};

// Get a formatted time string
//   Example: '2p.m.'
export const getTimeDisplay = date =>
  // 2p.m.
  moment(date)
    .tz(getUserTimezone())
    .format('h:mm a')
    .replace('m', '.m.');

// Get a formatted time string
//   Example: '07/13/79 2p.m. to 4p.m.'
export const getDateTimespanDisplay = ({ date, start, end }) =>
  // const { date, start, end } = this.props.values;
  `${getDateDisplay(date)} ${getTimeDisplay(
    getDateFromTime(start),
  )} to ${getTimeDisplay(getDateFromTime(end))}`;

// Gets the duration in minutes of an event based on start and end time.
export const getDurationInMinutes = (start, end) =>
  moment.duration(moment(end).diff(moment(start))).asMinutes();

// Converts a Date object to a Drupal compatible string.
//   Trims `.000Z` off the end.
export const dateToDrupal = (date, offset) => {
  offset = (typeof (offset) !== 'undefined') ? offset : false;
  return ensureDate(date, offset)
    .toISOString()
    .replace('.000Z', '')
    .replace('.999Z', ''); // @todo Replace with regex
};

// Converts a Drupal compatible string to a Date object.
export const dateFromDrupal = date =>
  moment.utc(date).toDate();
  // moment.utc(date, 'YYYY-MM-DDTHH:mm:ss').toDate();

export const localDateToUserDateString = date => moment(date).tz(getUserTimezone()).format();

export const roundTo = (
  date,
  value = 15,
  units = 'minutes',
  method = 'ceil',
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
export const userIsSuperAdmin = () => getUserUid() === '1';

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
    'intercept_event_manager',
    'intercept_event_organizer',
    'intercept_staff',
    'intercept_system_admin',
    'intercept_room_reservation_approver',
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
    'intercept_event_manager',
    'intercept_event_organizer',
    'intercept_system_admin',
    'intercept_room_reservation_approver',
  ]);
