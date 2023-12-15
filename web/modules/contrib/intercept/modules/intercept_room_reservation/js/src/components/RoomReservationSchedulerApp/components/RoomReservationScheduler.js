import React, { useCallback, useContext, useEffect, useLayoutEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import moment from 'moment';
import interceptClient from 'interceptClient';
import forEach from 'lodash/forEach';
import get from 'lodash/get';
import isEmpty from 'lodash/isEmpty';
import map from 'lodash/map';

// Local Components
import Calendar from './ReservationCalendar';
import RoomsContext from '../context/RoomsContext';
import GroupsContext from '../context/GroupsContext';
import withAvailability from './../../ReserveRoomApp/withAvailability';
import withUserStatus from './../../ReserveRoomApp/withUserStatus';
import RoomLimitWarning from './../../ReserveRoomApp/RoomLimitWarning';
import useAvailabilityRefresh from '../hooks/useAvailabiltyRefresh';
import useEventListener from '../hooks/useEventListener';

const { constants, api, select, utils } = interceptClient;
const c = constants;

const ADD_ROOM_RESERVATION = 'intercept:addRoomReservation';
const EDIT_ROOM_RESERVATION = 'intercept:editRoomReservation';
const VIEW_ROOM_RESERVATION = 'intercept:viewRoomReservation';
const SAVE_ROOM_RESERVATION = 'intercept:saveRoomReservation';
const SAVE_ROOM_RESERVATION_ERROR = 'intercept:saveRoomReservationError';
const CHANGE_ROOM_RESERVATION = 'intercept:changeRoomReservation';
const CLOSE_ROOM_RESERVATION = 'intercept:closeRoomReservation';
const REFRESH_ROOM_RESERVATION = 'intercept:updateRoomReservation';
const saveRoomReservationSuccess = 'intercept:saveRoomReservationSuccess';

const POLL_INTERVAL = 5000;

const getResourceGroupFromLocation = (location, getHours) => ({
  id: location.data.id,
  title: location.data.attributes.title,
  hours: getHours(location.data.id),
});

const getResourceGroupsFromLocations = (locations, getHours) =>
  locations.map(location => getResourceGroupFromLocation(location, getHours));

const getResourceFromRoom = (room, getHours) => ({
  id: room.id,
  title: room.attributes.title,
  groupId: room.relationships.field_location.data[0].id,
  hours: getHours(room.relationships.field_location.data[0].id),
  drupal_internal__nid: get(room, 'attributes.drupal_internal__nid'),
});

const getResourcesFromRooms = (rooms, getHours) =>
  rooms.map(room => getResourceFromRoom(room, getHours));

const getEventFromBlockedTime = (time, roomId) => ({
  id: get(time, 'uuid'),
  title: get(time, 'message', 'Booked'),
  resourceId: get(time, 'resource', roomId),
  status: get(time, 'isEditable') ? get(time, 'status', 'disabled') : 'disabled',
  start: get(time, 'start'),
  end: get(time, 'end'),
  hasEvent: get(time, 'hasEvent'),
  isReservedByStaff: get(time, 'isReservedByStaff'),
  isEditable: get(time, 'isEditable'),
  drupal_internal__id: get(time, 'id', roomId),
});

const getEventsFromAvailability = (availability) => {
  let events = [];
  if (get(availability, 'rooms')) {
    forEach(availability.rooms, (room, roomId) => {
      if (!isEmpty(room.dates)) {
        events = [...events, ...map(room.dates, date => getEventFromBlockedTime(date, roomId))];
      }
    });
  }
  return events;
};

/**
 * Creates an Intercept ADD reservation event to be dispatched
 *
 * @param {Object} reservation
 *   Room reservation id.
 * @param {[Object]} resources
 *   An array of available resources.
 * @return {CustomEvent}
 *   A Custom Event object.
 */
function getAddRoomReservationEvent(reservation, resources) {
  const tz = utils.getUserTimezone();
  const start = moment(reservation.start).tz(tz);
  const end = moment(reservation.end).tz(tz);
  const resource = resources.find(item => item.id === reservation.resourceId);

  const event = new CustomEvent(ADD_ROOM_RESERVATION, {
    detail: {
      ...reservation.event,
      resource,
      end: {
        date: end.format('YYYY-MM-DD'),
        time: end.format('HH:mm:ss'),
      },
      start: {
        date: start.format('YYYY-MM-DD'),
        time: start.format('HH:mm:ss'),
      },
    },
  });

  return event;
}

/**
 * Creates an Intercept ADD reservation event to be dispatched
 *
 * @param {Object} reservation
 *   Room reservation id.
 * @param {[Object]} resources
 *   An array of available resources.
 * @return {CustomEvent}
 *   A Custom Event object.
 */
function getAddRoomReservationEventFromUrl(resource, startTime, endTime) {
  const tz = utils.getUserTimezone();
  const start = moment(startTime).tz(tz);
  const end = moment(endTime).tz(tz);

  const event = new CustomEvent(ADD_ROOM_RESERVATION, {
    detail: {
      resource,
      end: {
        date: end.format('YYYY-MM-DD'),
        time: end.format('HH:mm:ss'),
      },
      start: {
        date: start.format('YYYY-MM-DD'),
        time: start.format('HH:mm:ss'),
      },
    },
  });

  return event;
}

/**
 * Creates an Intercept CHANGE reservation event to be dispatched
 *
 * @param {Object} reservation
 *   Room reservation id.
 * @param {[Object]} resources
 *   An array of available resources.
 * @return {CustomEvent}
 *   A Custom Event object.
 */
function getChangeRoomReservationEvent(reservation, resources) {
  const tz = utils.getUserTimezone();
  const start = moment(reservation.start).tz(tz);
  const end = moment(reservation.end).tz(tz);
  const resource = resources.find(item => item.id === reservation.resourceId);

  const event = new CustomEvent(CHANGE_ROOM_RESERVATION, {
    detail: {
      ...reservation.event,
      resource,
      end: {
        date: end.format('YYYY-MM-DD'),
        time: end.format('HH:mm:ss'),
      },
      start: {
        date: start.format('YYYY-MM-DD'),
        time: start.format('HH:mm:ss'),
      },
    },
  });

  return event;
}

/**
 * Creates an Intercept VIEW reservation event to be dispatched
 *
 * @param {string} id
 *   Room reservation id.
 * @return {CustomEvent}
 *   A Custom Event object.
 */
function getSelectRoomReservationEvent(id) {
  const event = new CustomEvent(VIEW_ROOM_RESERVATION, {
    detail: {
      id,
    },
  });

  return event;
}

/**
 * Creates an Intercept EDIT reservation event to be dispatched
 *
 * @param {string} id
 *   Room reservation id.
 * @return {CustomEvent}
 *   A Custom Event object.
 */
function getEditRoomReservationEvent(id) {
  const event = new CustomEvent(EDIT_ROOM_RESERVATION, {
    detail: {
      id,
    },
  });

  return event;
}

function getDate(value, view = 'day', boundary = 'start') {
  const method = boundary === 'start' ? 'startOf' : 'endOf';
  const date = moment.tz(value, utils.getUserTimezone())[method](view);
  // The calendar view may include date from the previous or next month
  // so we make sure to include the beginning of the first week and
  // end of the last week.
  if (view === 'month') {
    date[method]('week');
  }

  return date.format();
}

const RoomReservationScheduler = ({
  availability,
  date,
  getLocationHours,
  fetchAvailability,
  fetchLocations,
  fetchUserStatus,
  room,
  start,
  end,
  userStatus,
  onChangeDate,
  onChangeEnd,
  onChangeRoom,
  onChangeStart,
  onChangeView,
  purgeReservations,
  locations,
  view,
}) => {
  const { rooms } = useContext(RoomsContext);
  const { setGroups } = useContext(GroupsContext);

  const doFetchBlockedTime = useCallback(() => {
    const roomIds = rooms.map(room => room.id);
    const tz = utils.getUserTimezone();
    const day = moment.tz(date, tz);
    const start = day.clone().startOf('day');
    const end = day.clone().endOf('day');
    fetchAvailability({
      rooms: roomIds,
      start,
      end,
    });
  }, [rooms, date]);

  const [
    selectedEvent,
    setSelectedEvent,
  ] = useState(null);

  // Component Did Mount
  useEffect(() => {
    fetchLocations({
      filters: {
        onlyBranchLocation: {
          path: 'field_branch_location',
          value: '1',
        },
        published: {
          path: 'status',
          value: '1',
        },
      },
      fields: {
        [c.TYPE_LOCATION]: ['title', 'field_location_hours', 'field_branch_location', 'status'],
      },
    });
    fetchUserStatus();
  }, []);

  useEffect(() => {
    // Avoid overloading the system by only fetching if we are filtering by rooms.
    if (rooms.length > 0) {
      doFetchBlockedTime();
    }
  }, [date, view, rooms]);

  useEffect(() => {
    setGroups(getResourceGroupsFromLocations(locations, getLocationHours));
  }, [locations]);

  const resources = getResourcesFromRooms(rooms, getLocationHours);
  const events = getEventsFromAvailability(availability);

  useEffect(() => {
    if (resources.length === 0 || !room || !start || !end) {
      return;
    }
    const resource = resources.find(item => item.id === room);
    if (resource) {
      setSelectedEvent({
        title: '',
        start,
        end,
        resourceId: room,
        isEditable: true,
      });
      window.dispatchEvent(getAddRoomReservationEventFromUrl(resource, start, end));
    }
  }, [rooms]);

  // Scroll the selected reservation into view.
  useLayoutEffect(() => {
    const selected = document.querySelector('.rbc-selected');
    if (selected && selected.scrollIntoViewIfNeeded) {
      selected.scrollIntoViewIfNeeded();
    }
  }, [selectedEvent]);

  const onDoubleClickEvent = (event) => {
    if (event.drupal_internal__id) {
      window.dispatchEvent(getEditRoomReservationEvent(event.drupal_internal__id));
    }
  };

  const onChangeEvent = (event) => {
    setSelectedEvent({
      ...event.event,
      start: moment(event.start).tz(utils.getUserTimezone()).format(),
      end: moment(event.end).tz(utils.getUserTimezone()).format(),
      resourceId: event.resourceId,
    });
    window.dispatchEvent(getChangeRoomReservationEvent(event, resources));
  };

  const onSelectSlot = (event) => {
    const startTime = moment(event.start).tz(utils.getUserTimezone()).format();
    const endTime = moment(event.end).tz(utils.getUserTimezone()).format();

    setSelectedEvent({
      title: '',
      start: startTime,
      end: endTime,
      resourceId: event.resourceId,
      isEditable: true,
    });
    onChangeRoom(event.resourceId);
    onChangeEnd(endTime);
    onChangeStart(startTime);
    window.dispatchEvent(getAddRoomReservationEvent(event, resources));
  };

  /**
   * Handles a room reservation updated externally, such
   * as from a Drupal reservation form.
   *
   * @param {Object} values
   *  The incoming reservation values.
   * @param {String} values.id
   *  The reservation UUID.
   * @param {String} values.start
   *  The RFC 3339 formatted reservation start date time.
   * @param {String} values.end
   *  The RFC 3339 formatted reservation end date time.
   * @param {String} values.room
   *  The reservation's room UUID.
   */
  const onUpdateEvent = (values) => {
    const event = events.find(item => item.id === values.id) || {
      title: '',
    };

    // Get the uuid of the selected room.
    const resource = rooms.find(item => item.attributes.drupal_internal__nid === Number(values.room));
    const resourceId = resource ? resource.id : null;

    // Apply the updated values to the stored event if found.
    setSelectedEvent({
      ...event,
      start: moment(values.start).tz(utils.getUserTimezone()).format(),
      end: moment(values.end).tz(utils.getUserTimezone()).format(),
      resourceId,
    });
  };

  const draggableAccessor = (event) => {
    if (event.drupal_internal__id) {
      return true;
    }
    return false;
  };

  const startAccessor = (event) => {
    const dateFromDrupal = moment(utils.dateFromDrupal(event.start));
    const midnight = moment(date)
      .tz(utils.getUserTimezone())
      .startOf('day');
    const startDate = moment.max(dateFromDrupal, midnight);
    return startDate.toDate();
  };

  const endAccessor = (event) => {
    const dateFromDrupal = moment(utils.dateFromDrupal(event.end));
    const midnight = moment(date)
      .tz(utils.getUserTimezone())
      .endOf('day')
      .subtract(1, 'seconds');
    const endDate = moment.min(dateFromDrupal, midnight);
    if (endDate.get('hour') === 0) {
      endDate.subtract(1, 'seconds');
    }
    return endDate.toDate();
  };

  const onSelectEvent = (event) => {
    onChangeRoom(null);
    onChangeEnd(null);
    onChangeStart(null);

    if (event.drupal_internal__id) {
      setSelectedEvent(event);
      window.dispatchEvent(getSelectRoomReservationEvent(event.drupal_internal__id));
    }
  };

  useEventListener(
    saveRoomReservationSuccess,
    () => {
      doFetchBlockedTime();
      // If we were creating a new reservation deselect the temp event.
      if (selectedEvent && typeof selectedEvent.id !== 'string') {
        setSelectedEvent(null);
      }
    },
    window,
    true,
  );

  useEventListener(
    REFRESH_ROOM_RESERVATION,
    (event, values) => {
      onUpdateEvent(values);
    },
    window,
    true,
  );

  useEventListener(CLOSE_ROOM_RESERVATION, () => {
    onChangeRoom(null);
    onChangeEnd(null);
    onChangeStart(null);
    setSelectedEvent(null);
  });

  //
  // Poll for availability changes.
  // This keeps the calendar up to date with
  // changes made by other users.
  //
  useAvailabilityRefresh(() => {
    // Avoid overloading the system by only fetching if we are filtering by rooms.
    if (rooms.length > 0) {
      doFetchBlockedTime();
    }
  }, POLL_INTERVAL);

  return (<div>
    <RoomLimitWarning userStatus={userStatus} />
    <Calendar
      date={date}
      events={events}
      resources={resources}
      view={view}
      onDateChange={(values) => {
        purgeReservations();
        onChangeDate(values);
      }}
      onViewChange={onChangeView}
      onChangeEvent={onChangeEvent}
      onSelectEvent={onSelectEvent}
      draggableAccessor={draggableAccessor}
      startAccessor={startAccessor}
      endAccessor={endAccessor}
      onSelectSlot={onSelectSlot}
      onDoubleClickEvent={onDoubleClickEvent}
      selected={selectedEvent}
    />
  </div>);
};

RoomReservationScheduler.propTypes = {
  getLocationHours: PropTypes.func.isRequired,
  locations: PropTypes.arrayOf(Object).isRequired,
  locationsLoading: PropTypes.bool.isRequired,
  fetchAvailability: PropTypes.func.isRequired,
  fetchLocations: PropTypes.func.isRequired,
  fetchUser: PropTypes.func.isRequired,
  fetchUserStatus: PropTypes.func.isRequired,
  onChangeDate: PropTypes.func.isRequired,
  onChangeEnd: PropTypes.func.isRequired,
  onChangeRoom: PropTypes.func.isRequired,
  onChangeStart: PropTypes.func.isRequired,
  onChangeView: PropTypes.func.isRequired,
  purgeReservations: PropTypes.func.isRequired,
  date: PropTypes.instanceOf(Date),
  view: PropTypes.string,
};

RoomReservationScheduler.defaultProps = {
  view: 'day',
  date: utils.getUserTimeNow(),
};

const mapStateToProps = (state, ownProps) => ({
  locations: select.locationsAscending(state),
  locationsLoading: select.recordsAreLoading(c.TYPE_LOCATION)(state),
  getLocationHours: id => select.locationHoursTimesOnDate(id, ownProps.date || utils.getUserTimeNow())(state),
  openHoursLimit: select.locationsOpenHoursLimit(state),
  calendarRooms: [],
});

const mapDispatchToProps = dispatch => ({
  fetchLocations: (options) => {
    dispatch(api[c.TYPE_LOCATION].fetchAll(options));
  },
  fetchUser: (options) => {
    dispatch(api[c.TYPE_USER].fetchAll(options));
  },
  purgeReservations: (options) => {
    dispatch(api[c.TYPE_ROOM_RESERVATION].purge(options));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(withUserStatus(withAvailability(RoomReservationScheduler)));
