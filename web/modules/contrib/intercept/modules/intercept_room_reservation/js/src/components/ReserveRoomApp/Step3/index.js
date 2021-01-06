import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import debounce from 'lodash/debounce';
import get from 'lodash/get';
import pick from 'lodash/pick';
import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';

// Material UI

// Local Components
import ReserveRoomForm from './ReserveRoomForm';
import DateSummary from './DateSummary';
import RoomSummary from './RoomSummary';
import ValueSummaryFooter from './ValueSummaryFooter';
import withAvailability from './../withAvailability';

const { constants, select, utils } = interceptClient;
const c = constants;
const roomIncludes = ['image_primary', 'image_primary.field_media_image'];

function getPublishedFilters(value = true) {
  return {
    published: {
      path: 'status',
      value: value ? '1' : '0',
    },
  };
}

function getFilters(values) {
  const filter = {
    ...getPublishedFilters(true),
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

class ReserveRoomStep3 extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      date: props.date,
      room: {
        current: null,
        previous: null,
        exiting: false,
      },
      availability: {
        loading: false,
        shouldUpdate: false,
        rooms: {},
      },
    };
    this.handleCalendarNavigate = this.handleCalendarNavigate.bind(this);
    this.handleCalendarView = this.handleCalendarView.bind(this);
    this.handleViewChange = this.handleViewChange.bind(this);
    this.handleFormChange = this.handleFormChange.bind(this);
    this.doFetchRooms = debounce(this.doFetchRooms, 500).bind(this);
  }

  componentDidMount() {
    const { fetchAvailability, formValues, room } = this.props;
    const { start, end, date } = formValues;
    const shouldValidateConflicts = !!(room && start && end && date);
    this.mounted = true;

    if (shouldValidateConflicts) {
      fetchAvailability(this.getRoomAvailabilityQuery());
    }
  }

  componentDidUpdate(prevProps) {
    const { fetchAvailability, formValues, room } = this.props;
    const { start, end, date } = formValues;
    const hasValues = !!(room && start && end && date);
    const valuesChanged =
      prevProps.room !== room ||
      prevProps.formValues.end !== end ||
      prevProps.formValues.start !== start ||
      prevProps.formValues.date !== date;
    const shouldValidateConflicts = hasValues && valuesChanged;

    if (shouldValidateConflicts) {
      fetchAvailability(this.getRoomAvailabilityQuery());
    }
  }

  componentWillUnmount() {
    this.mounted = false;
  }

  // Get query params based on current rooms and filters.
  getRoomAvailabilityQuery = () => {
    const { formValues } = this.props;

    if (!formValues.start || !formValues.start || !this.props.room) {
      return {};
    }

    const start = utils.getDateFromTime(formValues.start, formValues[c.DATE]);
    const end = utils.getDateFromTime(formValues.end, formValues[c.DATE]);
    const options = {
      rooms: [this.props.room],
    };

    // Compute duration of reservation.
    options.duration = utils.getDurationInMinutes(start, end);
    options.start = start;
    options.end = end;

    return options;
  };

  handleViewChange = (value) => {
    // this.props.onChangeView(value);
    // this.doFetchRooms(this.props.filters, value, this.props.calView, this.props.date);
  };

  handleCalendarNavigate = (date, calView) => {
    this.props.onChangeDate(date);
    this.doFetchRooms(this.props.filters, 'calendar', calView, date);
  };

  handleCalendarView = (calView) => {
    this.props.onChangeCalView(calView);
    this.doFetchRooms(this.props.filters, 'calendar', calView, this.props.date);
  };

  handleFilterChange = (values) => {
    this.props.onChangeFilters(values);
    this.doFetchRooms(values);
  };

  handleFormChange(formValues) {
    this.setState({
      // room,
      formValues: {
        ...this.state.formValues,
        ...formValues,
      },
    });
  }

  doFetchRooms(
    values = this.props.filters,
    view = this.props.view,
    calView = this.props.calView,
    date = this.props.date,
  ) {
    const { fetchRooms } = this.props;

    fetchRooms({
      filters: getFilters(values, view, calView, date),
      include: roomIncludes,
      replace: true,
      headers: {
        'X-Consumer-ID': interceptClient.consumer,
      },
    });
  }

  hasConflict = () => {
    const availability = get(this, `props.availability.rooms.${this.props.room}`) || null;
    if (!availability) {
      return false;
    }
    const isStaff = utils.userIsStaff();
    const conflictProp = isStaff ? 'has_reservation_conflict' : 'has_conflict';

    return availability[conflictProp];
  };

  render() {
    const { props } = this;
    const { event, room, formValues, onChange, onChangeStep, userStatus } = props;
    // const { date, start, end } = formValues;
    const dateValues = pick(formValues, ['date', 'start', 'end']);
    const values = pick(formValues, [
      'attendees',
      'meetingDetails',
      'refreshments',
      'refreshmentsDesc',
      'publicize',
      'groupName',
      'agreement',
      c.TYPE_MEETING_PURPOSE,
    ]);
    const hasConflict = this.hasConflict();

    // Hide this step if:
    // - the reservation status has not been checked
    // - the user has exceeded their limit
    // - the user has been blocked/barred

    if (!userStatus.initialized || userStatus.loading ||
        userStatus.exceededLimit ||
        window.drupalSettings.intercept.user.barred) {
      return null;
    }

    return (
      <div className="l--default">
        <div className="l__header">
          <div className="value-summary__wrapper">
            <RoomSummary
              value={room}
              onClickChange={() => {
                this.props.fetchUserStatus();
                onChangeStep(0);
              }}
            />
            <DateSummary
              value={dateValues}
              onClickChange={() => {
                this.props.fetchUserStatus();
                onChangeStep(1);
              }}
            />
          </div>
          {hasConflict && (
            <ValueSummaryFooter
              level={'error'}
              message={'This room is not available during this time.'}
            />
          )}
        </div>
        <div className="l__main">
          <div className="l__primary">
            <div className="l--subsection--tight">
              <ReserveRoomForm
                values={values}
                combinedValues={formValues}
                onChange={onChange}
                room={room}
                event={event}
                hasConflict={hasConflict}
                availabilityQuery={this.getRoomAvailabilityQuery()}
              />
            </div>
          </div>
        </div>
      </div>
    );
  }
}

const mapStateToProps = state => ({
  rooms: select.roomsAscending(state),
  roomsLoading: select.recordsAreLoading(c.TYPE_ROOM)(state),
  calendarRooms: [],
});

ReserveRoomStep3.propTypes = {
  fetchRooms: PropTypes.func.isRequired,
};

ReserveRoomStep3.defaultProps = {};

export default connect(mapStateToProps)(withAvailability(ReserveRoomStep3));
