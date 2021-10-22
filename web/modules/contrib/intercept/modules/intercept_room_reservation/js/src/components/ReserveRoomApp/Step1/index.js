import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import moment from 'moment';
import debounce from 'lodash/debounce';
import get from 'lodash/get';
import isEqual from 'lodash/isEqual';

/* eslint-disable */
import drupalSettings from 'drupalSettings';

// Intercept Components
/* eslint-disable */
import interceptClient from 'interceptClient';

import LoadingIndicator from 'intercept/LoadingIndicator';
import PageSpinner from 'intercept/PageSpinner';
import RoomTeaser from 'intercept/RoomTeaser';
/* eslint-enable */

// Local Components
import RoomFilters from './RoomFilters';
import RoomList from './RoomList';
import withAvailability from './../withAvailability';

import { Button, Slide } from '@material-ui/core';

const { constants, api, select, utils } = interceptClient;
const c = constants;
const ATTENDEES = 'attendees';
const TIME = 'time';
const DURATION = 'duration';
const NOW = 'now';
const roomIncludes = ['image_primary', 'image_primary.field_media_image'];

function getPublishedFilters(value = true) {
  return {
    published: {
      path: 'status',
      value: value ? '1' : '0',
    },
  };
}

function getRoomsWithLocationsFilters() {
  return {
    locations: {
      type: 'group',
      conjunction: 'AND',
    },
    withLocation: {
      path: 'field_location.id',
      value: null,
      operator: '<>',
      memberOf: 'locations',
    },
    onlyBranchLocation: {
      path: 'field_location.field_branch_location',
      value: '1',
      memberOf: 'locations',
    },
  };
}

/**
 * Get a set of JSON:API compliant search query parameters that will
 * filter the list of rooms returned based on the user's role.
 *
 * Note: This is simply to reduce the amount of rooms returned from the API for performance
 * reasons and is not a suitable substitute for permissions based filtering which should
 * always be enforced on the server side.
 *
 * @returns object
 *   An object which can be parsed into JSON:API compliant URL search query parameters.
 */

function getRoleBasedFilters() {
  let filter = {
    roomUsage: {
      type: 'group',
      conjunction: 'OR',
    },
    staffOnly: {
      path: 'field_staff_use_only',
      value: '0',
      memberOf: 'roomUsage',
    },
    requiresCert: {
      path: 'field_requires_certification',
      value: '1',
      memberOf: 'roomUsage',
    },
  };

  // Omit filters if user is staff.
  if (utils.userIsStaff()) {
    filter = {};
  }

  return filter;
}

function getAttendeesFilters(values = {}) {
  if (!values[ATTENDEES]) {
    return {};
  }

  return {
    capacity: {
      path: 'field_capacity_max',
      value: values[ATTENDEES],
      operator: '>=',
    },
  };
}

function getFilters(values) {
  const filter = {
    ...getPublishedFilters(true),
    ...getAttendeesFilters(values),
    ...getRoleBasedFilters(),
    ...getRoomsWithLocationsFilters(),
  };

  if (!values) {
    return filter;
  }

  const types = [
    { id: c.TYPE_ROOM_TYPE, path: 'field_room_type.id', conjunction: 'OR' },
    { id: c.TYPE_LOCATION, path: 'field_location.id', conjunction: 'OR' },
  ];

  types.forEach((type) => {
    if (values[type.id] && values[type.id].length > 0) {
      if (type.conjunction === 'AND') {
        const group = `${type.id}-group`;
        filter[group] = {
          type: 'group',
          conjunction: type.conjunction,
        };
        values[type.id].forEach((element, key) => {
          const id = `${type.id}-${key}`;
          filter[id] = {
            path: type.path,
            value: element,
            memberOf: group,
          };
        });
      }
      else {
        filter[type.id] = {
          path: type.path,
          value: values[type.id],
          operator: 'IN',
        };
      }
    }
  });

  return filter;
}

// Filter function for room keyword searchs.
const byKeyword = keyword => (room) => {
  if (!keyword) {
    return true;
  }

  const title = get(room, 'data.attributes.title').toLowerCase();
  const teaser = get(room, 'data.attributes.field_text_teaser.value');
  const equipment = get(room, 'data.attributes.field_room_standard_equipment');

  const haystack = [
    title,
    teaser,
    equipment.length > 0 ? equipment.reduce((allItems, item) => allItems.concat(item)) : '',
  ].reduce((allFields, field) => allFields.concat(field
    .toLowerCase()
    // PHP strip_tags
    .replace(/<.*?>/g, '')
    // Remove non alphanumeric characters
    .replace(/[^a-zA-Z 0-9\-]/g, '')));
  const needle = keyword.toLowerCase();

  return haystack.indexOf(needle) >= 0;
};

class ReserveRoomStep1 extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      date: props.date,
      filters: props.filters,
      formValues: {
        [c.TYPE_ROOM]: null,
        date: null,
        start: moment()
          .startOf('hour')
          .add(1, 'h')
          .toDate(),
        end: moment()
          .startOf('hour')
          .add(2, 'h')
          .toDate(),
        attendees: 1,
        groupName: '',
        meeting: false,
        [c.TYPE_MEETING_PURPOSE]: null,
        meetingDetails: '',
        refreshments: false,
        refreshmentsDesc: '',
        user: utils.getUserUuid(),
      },
      room: {
        current: null,
        previous: null,
        exiting: false,
      },
      availabilityShouldUpdate: false,
    };
    this.doFetchRooms = debounce(this.doFetchRooms, 500).bind(this);
  }

  componentDidMount() {
    this.doFetchRooms(this.props.filters, this.props.view, this.props.calView, this.props.date);
    this.mounted = true;
  }

  componentDidUpdate(prevProps) {
    const { availabilityShouldUpdate } = this.state;
    const { availability, fetchAvailability, filters, rooms } = this.props;
    const didUpdate = prop => !isEqual(prevProps[prop], this.props[prop]);

    if (
      (filters[c.DATE] || filters[NOW] === true) &&
      (didUpdate('rooms') || didUpdate('filters'))
    ) {
      // Force an update since filters have changed.
      this.setState({
        availabilityShouldUpdate: true,
      });
    }

    // Fetch room availability if necessary.
    if (rooms.length > 0 && availabilityShouldUpdate && !availability.loading) {
      // Prevent further updates until filters have changed.
      this.setState({
        availabilityShouldUpdate: false,
      });
      fetchAvailability(this.getRoomAvailabilityQuery());
    }
  }

  componentWillUnmount() {
    this.mounted = false;
  }

  onExited() {
    this.setState({
      room: {
        ...this.state.room,
        exiting: false,
      },
    });
  }

  onlyAvailable = (availability) => {
    const { rooms } = availability;
    const isStaff = utils.userIsStaff();
    const isManager = utils.userIsManager();

    return (room) => {
      const id = get(room, 'data.id');
      // If the room is not reservable online,
      // we'll show it to non-managers so they can call for availability.
      if (!isManager && !get(room, 'data.attributes.field_reservable_online')) {
        return false;
      }
      if (rooms[id]['has_max_duration_conflict']) {
        return false;
      }
      // If the room is not returned from the availabilty request, lets assume it's available;
      if (!rooms[id]) {
        return true;
      }

      if (isStaff) {
        return !rooms[id]['has_reservation_conflict'];
      }

      // Return true if there is no conflict.
      return !rooms[id]['has_reservation_conflict'] && !rooms[id]['has_open_hours_conflict'];
    };
  };

  // Get query params based on current rooms and filters.
  getRoomAvailabilityQuery = () => {
    const options = {
      rooms: this.props.rooms.map(i => i.data.id),
      duration: 30,
    };

    if (this.props.filters[DURATION]) {
      options.duration = this.props.filters[DURATION];
    }

    const tz = utils.getUserTimezone();

    if (this.props.filters[NOW]) {
      const date = utils.roundTo(new Date(), 15, 'minutes', 'ceil').tz(tz);
      options.start = date.clone();
      options.end = date.clone().add(options.duration, 'minute');
    }
    else if (this.props.filters[c.DATE]) {
      const date = moment.tz(this.props.filters[c.DATE], tz);
      options.start = date.clone().hour(0);
      options.end = date.clone().endOf('day');

      switch (this.props.filters[TIME]) {
        case 'morning':
          options.end.hour(11);
          break;
        case 'afternoon':
          options.start.hour(12);
          options.end.hour(16);
          break;
        case 'evening':
          options.start.hour(17);
          options.end.hour(23);
          break;
        default:
          break;
      }
    }

    return options;
  };

  handleRoomSelect = (value) => {
    this.props.onChangeRoom(value);
    this.props.onChangeStep(1);
  };

  handleFilterChange = (values) => {
    this.props.onChangeFilters(values);
    if (this.shouldFetchRooms(this.props.filters, values)) {
      this.doFetchRooms(values);
    }
  };

  handleFormChange(formValues) {
    let room = this.state.room;
    if (formValues[c.TYPE_ROOM] !== this.state.formValues[c.TYPE_ROOM]) {
      room = {
        current: formValues[c.TYPE_ROOM],
        previous: this.state.room.current,
        exiting: this.state.room.current !== this.state.room.previous,
      };
    }
    this.setState({
      room,
      formValues,
    });
  }

  // Only fetch rooms if relevant filters have changed.
  shouldFetchRooms = (oldValues, newValues) =>
    get(oldValues, `${c.TYPE_LOCATION}.length`) !== get(newValues, `${c.TYPE_LOCATION}.length`) ||
    get(oldValues, `${c.TYPE_ROOM_TYPE}.length`) !== get(newValues, `${c.TYPE_ROOM_TYPE}.length`) ||
    oldValues[ATTENDEES] !== newValues[ATTENDEES];

  doFetchRooms(values = this.props.filters) {
    const { fetchRooms } = this.props;

    fetchRooms({
      filters: getFilters(values),
      include: roomIncludes,
      sort: {
        title: {
          path: 'title',
        },
      },
      replace: true,
      headers: {
        'X-Consumer-ID': interceptClient.consumer,
      },
    });
  }

  showAvailable = (rooms) => {
    const { availability, filters } = this.props;

    if (!filters[NOW] && !filters[c.DATE]) {
      return rooms;
    }

    if (availability.rooms.length === 0 || availability.loading) {
      return rooms;
    }

    return rooms.filter(this.onlyAvailable(availability));
  };

  renderDetailButton = (roomProps) => {
    const { onViewRoomDetail } = this.props;

    return (
      <Button
        variant={'outlined'}
        size="small"
        color="primary"
        className={'action-button__button'}
        onClick={() => onViewRoomDetail(roomProps.uuid)}
      >
        {'View Details'}
      </Button>
    );
  };

  /**
   * Determines whether or not a user should be able to submit a room reservation.
   * This method only limits the options available to the user and does not
   * replace the need for proper server-side validation.
   *
   * @param {object} roomProps
   *   The props passed to the room teaser, including the JSON:API room resource object.
   * @returns {boolean}
   */
  userCanReserveRoom = (roomProps) => {
    const reservable = get(roomProps, 'room.attributes.field_reservable_online');
    const mustCertify = get(roomProps, 'room.attributes.field_requires_certification');
    const userIsCertifiedForRoom = utils.userIsCertifiedForRoom(get(roomProps, 'room.id'));

    // A user can reserve this room if...
    // they are a manager OR...
    if (utils.userIsManager()) {
      return true;
    }
    // the room is reservable and they are staff OR...
    if (reservable && utils.userIsStaff()) {
      return true;
    }
    // ...the room is reservable, requires certification and they are certified OR...
    if (reservable && mustCertify && userIsCertifiedForRoom) {
      return true;
    }
    // ...the room is reservable and does not require certification.
    if (reservable && !mustCertify) {
      return true;
    }

    return false;
  };

  render() {
    const { handleFilterChange, handleRoomSelect } = this;
    const {
      dateLimits,
      rooms,
      roomsLoading,
      filters,
    } = this.props;

    const roomToShow = this.state.room[this.state.room.exiting ? 'previous' : 'current'];

    const roomFooter = (roomProps) => {
      if (!this.userCanReserveRoom(roomProps)) {
        const phoneNumber = get(roomProps, 'room.attributes.field_reservation_phone_number');
        const phoneLink = phoneNumber ? (
          <a href={`tel:${phoneNumber}`} className="call-prompt__link">
            {phoneNumber}
          </a>
        ) : null;

        return (
          <p className="call-prompt">
            <span className="call-prompt__text">Call for information</span> {phoneLink}
          </p>
        );
      }

      return (
        <div className="action-button">
          <Button
            variant={'contained'}
            size="small"
            color="primary"
            className={'action-button__button'}
            onClick={() => handleRoomSelect(roomProps.uuid)}
          >
            {'Reserve'}
          </Button>
          {this.renderDetailButton(roomProps)}
        </div>
      );
    };

    return (
      <div className="l--sidebar-before">
        <div className="l__main">
          <div className="l__secondary">
            <RoomFilters
              onChange={handleFilterChange}
              filters={filters}
              dateLimits={dateLimits}
              loading={roomsLoading}
            />
          </div>
          <div className="l__primary">
            <PageSpinner loading={roomsLoading} />
            <RoomList
              rooms={this.showAvailable(rooms)}
              teaserProps={{ footer: roomFooter }}
              loading={roomsLoading}
            />
            {(this.state.room.previous || this.state.room.current) && (
              <Slide
                direction="up"
                in={!this.state.room.exiting}
                onExited={this.onExited}
                mountOnEnter
              >
                <RoomTeaser uuid={roomToShow} id={roomToShow} className="room-teaser" />
              </Slide>
            )}
            <LoadingIndicator loading={roomsLoading} />
          </div>
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state, ownProps) => ({
  rooms: select.roomsAscending(state).filter(byKeyword(ownProps.filters[c.KEYWORD])),
  roomsLoading: select.recordsAreLoading(c.TYPE_ROOM)(state),
  calendarRooms: [],
});

const mapDispatchToProps = dispatch => ({
  fetchRooms: (options) => {
    dispatch(api[c.TYPE_ROOM].fetchAll(options));
  },
  fetchUser: (options) => {
    dispatch(api[c.TYPE_USER].fetchAll(options));
  },
});

ReserveRoomStep1.propTypes = {
  availability: PropTypes.object,
  filters: PropTypes.object,
  rooms: PropTypes.arrayOf(Object).isRequired,
  roomsLoading: PropTypes.bool.isRequired,
  fetchRooms: PropTypes.func.isRequired,
  onViewRoomDetail: PropTypes.func.isRequired,
  onChangeFilters: PropTypes.func.isRequired,
  onChangeRoom: PropTypes.func.isRequired,
  onChangeStep: PropTypes.func.isRequired,
};

ReserveRoomStep1.defaultProps = {
  availability: {},
  filters: {},
};

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(withAvailability(ReserveRoomStep1));
