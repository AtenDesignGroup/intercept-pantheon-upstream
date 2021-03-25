import { createSelector } from 'reselect';
import moment from 'moment';
import compact from 'lodash/compact';
import forEach from 'lodash/forEach';
import get from 'lodash/get';
import { filter } from 'lodash';
import flatten from 'lodash/flatten';
import groupBy from 'lodash/groupBy';
import map from 'lodash/map';
import mapValues from 'lodash/mapValues';
import cloneDeep from 'lodash/cloneDeep';
import padStart from 'lodash/padStart';
import pickBy from 'lodash/pickBy';
import sortBy from 'lodash/sortBy';
import uniqBy from 'lodash/uniqBy';
import intercept from './intercept-client';
import * as utils from './utils';

const { constants } = intercept;
const c = constants;

export const getIdentifier = (type, id) => ({
  type,
  id,
});

export const normalizeIdentifier = (options) => {
  if (arguments.length === 2) {
    return getIdentifier(arguments);
  }

  return options;
};

/**
 * Returns an array of published records.
 *
 * @param {function} selector
 *  A records selector.
 * @returns {array}
 *  An array of published records.
 */
export const published = selector =>
  createSelector(selector, items =>
    pickBy(items, item => get(item, 'data.attributes.status') === '1'),
  );

export const peek = (selector, path) =>
  createSelector(selector, items => mapValues(items, item => get(item, path)));

export const keyValues = (selector, path) =>
  createSelector(selector, items =>
    map(items, (item, key) => ({
      key,
      value: get(item, path),
    })),
  );

const sortTerms = items => sortBy(sortBy(items, 'data.attributes.name'), 'data.attributes.weight');

const toTree = branches => item => ({
  ...item,
  children: branches[item.data.id] ? sortTerms(branches[item.data.id]).map(toTree(branches)) : [],
});

export const getTermTree = (terms) => {
  const branches = groupBy(terms, 'data.relationships.parent.data.0.id');

  // If these terms don't have parent relationships, they will be grouped
  // by 'undefined'. Return that, as it should be the full list.
  if (branches.undefined) {
    return sortTerms(branches.undefined);
  }

  // 'virtual' is the parent of root terms. If there is no virtual property,
  // there is no root to form a tree.
  if (!branches.virtual) {
    return [];
  }

  const root = sortTerms(branches.virtual);
  return root.map(toTree(branches));
};

export const record = identifier => state => state[identifier.type].items[identifier.id];

export const records = type => state => state[type].items;

export const recordIds = selector =>
  createSelector(selector, items => map(items, item => get(item, 'data.id')));

export const recordIsLoading = (type, id) =>
  createSelector(record(getIdentifier(type, id)), item => !!get(item, 'state.syncing'));

export const recordsAreLoading = type => state => state[type].syncing;
export const recordsUpdated = type => state => state[type].updated;

export const recordLabel = identifier =>
  createSelector(
    record(identifier),
    item => get(item, 'data.attributes.title') || get(item, 'data.attributes.name'),
  );

const toOptions = item => ({
  key: item.data.id,
  value: get(item, 'data.attributes.title') || get(item, 'data.attributes.name'),
  children: item.children ? item.children.map(toOptions) : [],
});

export const recordOptions = type =>
  createSelector(records(type), (items) => {
    const isTerm = type.indexOf('taxonomy_term') >= 0;
    let sorted = [];

    // If this is a taxonomy term, sort it by weight then alphabetically by name.
    if (isTerm) {
      sorted = getTermTree(items);
      // sorted = sortBy(sortBy(items, 'data.attributes.name'), 'data.attributes.weight');
    }
    // Else sort alphabetically by title
    else {
      sorted = sortBy(items, 'data.attributes.title')
        .filter(item => (get(item, 'data.attributes.status') ? (get(item, 'data.attributes.status') === true) : true));
    }

    return sorted.map(toOptions);
  });

// Converts the records object into an Array.
export const recordsList = type => createSelector(records(type), items => map(items, item => item));

export const bundle = identifier => (state) => {
  if (identifier.type in state === false) {
    return identifier;
  }

  const base = record(identifier)(state);
  // Just return an id if no record is found.
  // @todo This should probably be handled better and a warning or exception thrown
  //  as it could result in unintended side effects.
  if (!base) {
    return identifier;
  }

  // Create temporary entity.
  const entity = cloneDeep(base.data);
  const relationships = base.data.relationships;
  const selectors = [];

  forEach(relationships, (resourceIdentifier, rel) => {
    if (!resourceIdentifier.data) {
      return;
    }
    const relData = entity.relationships[rel].data;
    // Replace the uuid with the entity object.
    if (Array.isArray(relData)) {
      selectors.concat(relData.map(item => bundle(item)));
      entity.relationships[rel] = relData.map(item => bundle(item)(state));
    }
    else {
      selectors.push(bundle(relData));
      entity.relationships[rel] = bundle(relData)(state);
    }
  });

  return createSelector([record(identifier), ...selectors], () => entity)(state);

  // return entity;
};

export const bundles = type => state =>
  mapValues(records(type)(state), (value, id) => bundle(getIdentifier(type, id))(state));

//
// Audience
//
export const audience = id => record('taxonomy_term--audience', id);
export const audiences = records('taxonomy_term--audience');
export const audiencesOptions = keyValues(audiences, 'data.attributes.name');
export const audiencesLabels = peek(audiences, 'data.attributes.name');

//
// Images
//
export const resourceImage = identifier =>
  createSelector(bundle(identifier), resourceBundle =>
    get(resourceBundle, 'relationships.image_primary.relationships.field_media_image'),
  );

export const resourceImageStyle = (identifier, style) =>
  createSelector(resourceImage(identifier), resourceBundle =>
    get(resourceBundle, `meta.derivatives.${style}`),
  );

//
// Events
//
export const event = id => state => state[c.TYPE_EVENT].items[id];
export const events = state => state[c.TYPE_EVENT].items;
export const eventsArray = state => map(state[c.TYPE_EVENT].items, item => item);
export const getEventStartDate = item => get(item, 'data.attributes.field_date_time.value');
export const getEventEndDate = item => get(item, 'data.attributes.field_date_time.end_value');
export const eventIds = recordIds(events);
export const eventsOptions = keyValues(events, 'title');
export const eventsLabels = peek(events, 'data.attributes.title');
export const calendarEvents = createSelector(events, items => map(items, item => item));

export const eventTeasers = createSelector(events, items => map(items, item => item));

export const eventsAscending = createSelector(eventsArray, items =>
  items.sort((a, b) => getEventStartDate(b) - getEventStartDate(a)),
);

export const eventsDecending = createSelector(eventsAscending, items => items.reverse());

export const eventsByDate = createSelector(eventsAscending, items =>
  groupBy(items, item =>
    utils.getDayTimeStamp(get(item, 'data.attributes.field_date_time.value')),
  ),
);

export const eventsByDateAscending = createSelector(eventsByDate, (items) => {
  const output = map(items, (item, key) => ({
    key,
    date: key,
    items: item.map(a => a.data.id),
  })).sort((a, b) => (b.key === a.key ? 0 : b.key > a.key ? 1 : -1));
  return output;
});

export const eventsByDateDescending = createSelector(eventsByDateAscending, items =>
  items.reverse(),
);

export const mustRegisterForEvent = id =>
  createSelector(record(getIdentifier(c.TYPE_EVENT, id)), item =>
    get(item, 'data.attributes.field_must_register'),
  );

// open_pending: registration is not yet open
// open: registration is open and not full
// waitlist: registration is full and there is a waitlist that is not full
// full: registration is open and full and there is no waitlist or the waitlist is full
// closed: registration is closed but not expired
// expired: event has ended
export const eventRegistrationStatus = id =>
  createSelector(record(getIdentifier(c.TYPE_EVENT, id)), item =>
    get(item, 'data.attributes.registration.status'),
  );

/**
 * Gets the formatted date string of the registration period's opening day
 *
 * @param {Object} eventRecord
 *  JSON API representation of an event
 * @returns {String}
 */
function getEventRegistrationOpenDate(eventRecord) {
  const openDate = get(eventRecord, 'data.attributes.field_event_register_period.value');

  if (!openDate) {
    return 'soon';
  }

  return utils.getDateDisplay(utils.dateFromDrupal(openDate));
}

/**
 * select.getEventRegistrationOpenDate
 */
export const eventRegistrationDate = id =>
  createSelector(record(getIdentifier(c.TYPE_EVENT, id)), getEventRegistrationOpenDate);

export const registerUrl = id =>
  createSelector(
    record(getIdentifier(c.TYPE_EVENT, id)),
    item => (utils.userIsStaff()
      ? `/event/${get(item, 'data.attributes.drupal_internal__nid')}/registrations`
      : `/event/${get(item, 'data.attributes.drupal_internal__nid')}/register#eventRegisterRoot`),
  );

//
// Event Registrations
//

export const eventRegistration = id => records(c.TYPE_EVENT_REGISTRATION, id);
export const eventRegistrations = records(c.TYPE_EVENT_REGISTRATION);
export const eventRegistrationsByEvent = id =>
  createSelector(recordsList(c.TYPE_EVENT_REGISTRATION), items =>
    items
      .filter(item => get(item, 'data.relationships.field_event.data.id') === id)
      .sort((a, b) => get(b, 'data.attributes.created') - get(a, 'data.attributes.created')),
  );

export const eventRegistrationsByUser = id =>
  createSelector(recordsList(c.TYPE_EVENT_REGISTRATION), items =>
    items
      .filter(item => get(item, 'data.relationships.field_user.data.id') === id)
      .sort((a, b) => get(b, 'data.attributes.created') - get(a, 'data.attributes.created')),
  );

export const eventsFromRegistrationsByUser = id => state =>
  eventRegistrationsByUser(id)(state).map(item =>
    record({
      type: c.TYPE_EVENT,
      id: get(item, 'data.relationships.field_event.data.id'),
    })(state),
  );

export const eventRegistrationsByEventByUser = (eventId, userId) =>
  createSelector(eventRegistrationsByEvent(eventId), items =>
    items.filter(item => get(item, 'data.relationships.field_user.data.id') === userId),
  );

// active: registration is confirmed
// canceled: registration has been canceled
// waitlist: on the waitlist
export const registrationStatus = id =>
  createSelector(record(getIdentifier(c.TYPE_EVENT_REGISTRATION, id)), item =>
    get(item, 'data.attributes.status'),
  );

// Saved Event Flag
export const savedEventsByUser = id =>
  createSelector(recordsList(c.TYPE_SAVED_EVENT), items =>
    items
      .filter(item => get(item, 'data.relationships.uid.data.id') === id)
      .sort((a, b) => get(b, 'data.attributes.created') - get(a, 'data.attributes.created')),
  );

export const eventsFromSavedEventsByUser = id => state =>
  savedEventsByUser(id)(state).map(item =>
    record({
      type: c.TYPE_EVENT,
      id: get(item, 'data.relationships.flagged_entity.data.id'),
    })(state),
  );

function getUserEventRegistrationStatus(registrations) {
  if (registrations.length < 0) {
    return null;
  }

  const statuses = registrations.map(r => get(r, 'data.attributes.status'));

  if (statuses.indexOf('active') >= 0) {
    return 'active';
  }

  if (statuses.indexOf('waitlist') >= 0) {
    return 'waitlist';
  }

  if (statuses.indexOf('canceled') >= 0) {
    return 'canceled';
  }

  return null;
}

export const userEventRegistrationStatus = (eventId, userId) =>
  createSelector(eventRegistrationsByEventByUser(eventId, userId), getUserEventRegistrationStatus);

function canCancel(statusEvent, statusUser) {
  return ['active', 'waitlist'].indexOf(statusUser) >= 0;
}

export const registrationCancelAllowed = (eventId, userId) =>
  createSelector(
    eventRegistrationStatus(eventId),
    userEventRegistrationStatus(eventId, userId),
    canCancel,
  );

function getRegisterButtonText(mustRegister, statusEvent, cancelAllowed) {
  if (!statusEvent || !mustRegister) {
    return '';
  }

  if (cancelAllowed) {
    return 'Cancel';
  }

  switch (statusEvent) {
    case 'waitlist':
      return 'Join Waitlist';
    default:
      return 'Register';
  }
}

function getRegistrationRemaining(eventResource) {
  return get(eventResource, 'data.attributes.registration.remaining_registration');
}

function getEventCapacity(eventResource) {
  return get(eventResource, 'data.attributes.field_capacity_max');
}

function getWaitlistRemaining(eventResource) {
  return get(eventResource, 'data.attributes.registration.remaining_waitlist');
}

function getWaitlistCapacity(eventResource) {
  return get(eventResource, 'data.attributes.field_waitlist_max');
}

function getRegistrationOpenDate(eventResource) {
  const value = get(eventResource, 'data.attributes.field_event_register_period.value');
  return utils.getDateDisplay(value);
}

export const registrationButtonText = (eventId, userId) =>
  createSelector(
    mustRegisterForEvent(eventId),
    eventRegistrationStatus(eventId),
    registrationCancelAllowed(eventId, userId),
    getRegisterButtonText,
  );

function getRegisterStatusText(mustRegister, statusEvent, statusUser, eventResource) {
  if (!statusEvent || !mustRegister) {
    return null;
  }

  if (statusEvent === 'expired') {
    return 'This event has ended.';
  }

  if (statusUser === 'active') {
    return 'You are registered!';
  }

  if (statusUser === 'waitlist') {
    return 'You are on the waitlist.';
  }

  const availableCapacity = getRegistrationRemaining(eventResource);
  const totalCapacity = getEventCapacity(eventResource);
  const availableWaitlist = getWaitlistRemaining(eventResource);
  const totalWaitlist = getWaitlistCapacity(eventResource);

  switch (statusEvent) {
    case 'open_pending':
      return `Registration opens ${getRegistrationOpenDate(eventResource)}`;
    case 'waitlist':
      switch (availableWaitlist) {
        case 1:
          return `There is ${availableWaitlist} of ${totalWaitlist} waitlist seat available.`;
        default:
          return `There are ${availableWaitlist} of ${totalWaitlist} waitlist seats available.`;
      }
    case 'full':
      return 'Registration is full.';
    case 'closed':
      return 'Registration is closed.';
    case 'expired':
      return 'This event has ended.';
    case 'open':
      switch (availableCapacity) {
        case 0:
          return 'This event is full.';
        case 1:
          return `There is ${availableCapacity} of ${totalCapacity} seat available.`;
        default:
          return `There are ${availableCapacity} of ${totalCapacity} seats available.`;
      }
    default:
      return null;
  }
}

export const registrationStatusText = (eventId, userId) =>
  createSelector(
    mustRegisterForEvent(eventId),
    eventRegistrationStatus(eventId),
    userEventRegistrationStatus(eventId, userId),
    event(eventId),
    getRegisterStatusText,
  );

function canRegister(mustRegister, statusEvent, cancelAllowed) {
  if (statusEvent === 'expired') {
    return false;
  }

  if (cancelAllowed) {
    return true;
  }

  if (!mustRegister) {
    return false;
  }

  switch (statusEvent) {
    case 'open':
    case 'waitlist':
      return true;
    default:
      return false;
  }
}

export const registrationAllowed = (eventId, userId) =>
  createSelector(
    mustRegisterForEvent(eventId),
    eventRegistrationStatus(eventId),
    registrationCancelAllowed(eventId, userId),
    canRegister,
  );

//
// Event Types
//
export const eventType = id => record(getIdentifier('taxonomy_term--event_type', id));
export const eventTypes = records('taxonomy_term--event_type');
export const eventTypesOptions = keyValues(eventTypes, 'data.attributes.name');
export const eventTypesLabels = peek(eventTypes, 'data.attributes.name');

//
// Locations
//
export const location = id => state => state[c.TYPE_LOCATION].items[id];
export const locations = state => state[c.TYPE_LOCATION].items;
export const locationsArray = state => Object.values(state[c.TYPE_LOCATION].items);
export const locationsOptions = keyValues(locations, 'data.attributes.title');
export const locationsLabels = peek(locations, 'data.attributes.title');
export const locationsAscending = createSelector(locationsArray, items =>
  items.sort(
    (a, b) =>
      (b.data.attributes.title === a.data.attributes.title
        ? 0
        : b.data.attributes.title > a.data.attributes.title
          ? -1
          : 1),
  ),
);
export const locationHours = id => (state) => {
  const loc = location(id)(state);
  return get(loc, 'data.attributes.field_location_hours') || '';
};
export const locationHoursOnDate = (id, date) => (state) => {
  const hours = locationHours(id)(state);
  const day = parseInt(
    moment.tz(date, utils.getUserTimezone())
      .format('d'),
    10,
  );
  const daysHours = filter(hours, value => value.day === day);
  const startHours = daysHours.map(dayHours => dayHours.starthours);
  const endHours = daysHours.map(dayHours => dayHours.endhours);

  if (daysHours) {
    return {
      start: Math.min(...startHours),
      end: Math.max(...endHours),
    };
  }
  return null;
};
export const locationHoursTimesOnDate = (id, date) =>
  createSelector(locationHoursOnDate(id, date), (hours) => {
    // Hours are null when location is closed.
    if (!hours) {
      return hours;
    }

    const localStart = utils.getDateFromTime(padStart(hours.start, 4, '0'), date);
    const localEnd = utils.getDateFromTime(padStart(hours.end, 4, '0'), date);

    return {
      start: utils.localDateToUserDateString(localStart),
      end: utils.localDateToUserDateString(localEnd),
    };
  });

// Gets the earliest and latest open hours.
// Useful for setting defaults.
export const locationsOpenHoursLimit = createSelector(locations, (locs) => {
  const hours = flatten(
    compact(map(locs, loc => get(loc, 'data.attributes.field_location_hours'))),
  );
  if (hours.length <= 0) {
    return {
      min: '0900',
      max: '2100',
    };
  }
  return {
    min: padStart(sortBy(hours, 'starthours').shift().starthours, 4, '0'),
    max: padStart(sortBy(hours, 'endhours').pop().endhours, 4, '0'),
  };
});

export const locationOpenHours = (id, date) =>
  createSelector(location(id), (loc) => {
    const hours = get(loc, 'data.attributes.field_location_hours');
    const day = parseInt(
      moment.tz(date, utils.getUserTimezone())
        .format('d'),
      10,
    );
    const days = filter(hours, value => value.day === day);
    const startHours = days.map(dayHours => dayHours.starthours);
    const endHours = days.map(dayHours => dayHours.endhours);

    return days.length !== 0
      ? {
        min: padStart(Math.min(...startHours), 4, '0'),
        max: padStart(Math.max(...endHours), 4, '0'),
      }
      : null;
  });

//
// Rooms
//
export const room = id => record(getIdentifier(c.TYPE_ROOM, id));
export const rooms = records(c.TYPE_ROOM);
export const roomsArray = state => Object.values(state[c.TYPE_ROOM].items);
export const roomsOptions = keyValues(rooms, 'data.attributes.title');
export const roomsLabels = peek(rooms, 'data.attributes.title');
export const roomsAscending = createSelector(roomsArray, items =>
  items.sort(
    (a, b) =>
      (b.data.attributes.title === a.data.attributes.title
        ? 0
        : b.data.attributes.title > a.data.attributes.title
          ? -1
          : 1),
  ),
);

// Get the room's title
export const roomLabel = id =>
  createSelector(room(id), item => get(item, 'data.attributes.title') || '');

export const roomLocation = id => createSelector(room(id), (item) => {
  const fieldLocation = get(item, 'data.relationships.field_location.data');
  // Handle field_location whether or not it's a multi-value field.
  if (Array.isArray(fieldLocation) && fieldLocation.length > 0) {
    return get(fieldLocation[0], 'id');
  }
  return get(fieldLocation, 'id');
});

export const roomCapacity = id =>
  createSelector(room(id), item => ({
    min: get(item, 'data.attributes.field_capacity_min'),
    max: get(item, 'data.attributes.field_capacity_max'),
  }));

export const roomLocationRecord = id => state => location(roomLocation(id)(state))(state);

export const roomLocationLabel = id => (state) => {
  const loc = location(roomLocation(id)(state))(state);
  return get(loc, 'data.attributes.title') || '';
};

export const roomLocationHours = (id, date) => (state) => {
  const loc = roomLocation(id)(state);
  return locationOpenHours(loc, date)(state);
};

function getReservationStatusText(resource) {
  const status = get(resource, 'data.attributes.field_status');

  switch (status) {
    case 'denied':
      return 'Denied';
    case 'approved':
      return 'Approved';
    case 'cancelled':
      return 'Cancelled';
    case 'requested':
      return 'Awaiting Approval';
    default:
      return null;
  }
}

function getReservationButtonText(resource) {
  const status = get(resource, 'data.attributes.field_status');

  switch (status) {
    case 'denied':
      return 'Rerequest';
    case 'approved':
      return 'Cancel';
    case 'cancelled':
      return 'Cancelled';
    case 'requested':
      return 'Cancel';
    default:
      return null;
  }
}

export const roomReservation = id => records(c.TYPE_ROOM_RESERVATION, id);
export const roomReservations = records(c.TYPE_ROOM_RESERVATION);
export const roomReservationsArray = createSelector(roomReservations, Object.values);

export const reservationStatusText = (id, type) =>
  createSelector(record({ id, type }), getReservationStatusText);

export const reservationButtonText = (id, type) =>
  createSelector(record({ id, type }), getReservationButtonText);

//
// Tag
//
export const tag = id => record(getIdentifier('taxonomy_term--tag', id));
export const tags = records('taxonomy_term--tag');
export const tagsOptions = keyValues(tags, 'data.attributes.name');
export const tagsLabels = peek(tags, 'data.attributes.name');

// User
export const usersSavedEvents = eventsFromSavedEventsByUser;
export const usersRegisteredEvents = eventsFromRegistrationsByUser;

export const onlyPastEvents = (items) => {
  const now = new Date();
  return items.filter(item => utils.dateFromDrupal(getEventEndDate(item)) < now);
};

export const onlyUpcomingEvents = (items) => {
  const now = new Date();
  return items.filter(item => utils.dateFromDrupal(getEventStartDate(item)) > now);
};

export const usersEvents = userId => (state) => {
  const registrations = usersSavedEvents(userId)(state);
  const saves = usersRegisteredEvents(userId)(state);
  return uniqBy([].concat(registrations, saves), item => item.data.id).sort(
    (a, b) => getEventStartDate(a) - getEventStartDate(b),
  );
};

export const usersPastEvents = userId =>
  createSelector(usersEvents(userId), items => onlyPastEvents(items).reverse());

export const usersUpcomingEvents = userId =>
  createSelector(usersEvents(userId), items => onlyUpcomingEvents(items));
