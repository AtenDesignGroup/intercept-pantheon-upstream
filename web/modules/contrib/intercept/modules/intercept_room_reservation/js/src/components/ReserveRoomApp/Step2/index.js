import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import moment from 'moment';

// Lodash
import isEqual from 'lodash/isEqual';
import pick from 'lodash/pick';
import get from 'lodash/get';
import filter from 'lodash/filter';
import map from 'lodash/map';

import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';

// Intercept Components
import LoadingIndicator from 'intercept/LoadingIndicator';

// Local Components
import ReserveRoomDateForm from './ReserveRoomDateForm';

import RoomAvailabilityCalendar from './RoomAvailabilityCalendar';
import withAvailability from './../withAvailability';
import { Button } from '@material-ui/core';

const { constants, select, utils } = interceptClient;
const c = constants;
const lastReservationBeforeClosing = get(drupalSettings, 'intercept.room_reservations.last_reservation_before_closing', '15');

class ReserveRoomStep2 extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      availabilityShouldUpdate: false,
      showClosedHours: false,
    };
  }

  componentDidMount() {
    this.props.onChange(this.getDefaultValues());
    // Fetch room availability if necessary.
    if (this.props.room) {
      // Prevent further updates until filters have changed.
      this.setState({
        availabilityShouldUpdate: false,
      });
      this.props.fetchAvailability(this.getRoomAvailabilityQuery());
    }
  }

  componentDidUpdate(prevProps) {
    const { availabilityShouldUpdate } = this.state;
    const { availability, fetchAvailability, room } = this.props;

    if (!isEqual(prevProps.formValues[c.DATE], this.props.formValues[c.DATE])) {
      this.setState({
        availabilityShouldUpdate: true,
      });
    }

    // Fetch room availability if necessary.
    if (room && availabilityShouldUpdate && !availability.loading) {
      // Prevent further updates until filters have changed.
      this.setState({
        availabilityShouldUpdate: false,
      });
      fetchAvailability(this.getRoomAvailabilityQuery());
    }
  }

  onlyTodaysReservations = (reservation) => {
    const start = utils.dateFromDrupal(reservation.start);
    const eod = moment(get(this, 'props.formValues.date'))
      .tz(utils.getUserTimezone())
      .endOf('day');

    return start < eod;
  };

  // Get query params based on current rooms and filters.
  getRoomAvailabilityQuery = () => {
    const options = {
      rooms: [this.props.room],
      duration: 15,
    };
    const tz = utils.getUserTimezone();
    const date = moment.tz(this.props.formValues[c.DATE], tz);
    options.start = date.clone().startOf('day');
    options.end = date.clone().endOf('day');

    return options;
  };

  getDefaultValues = () => {
    const { formValues, room, event, eventRecord, locationRecord, filters } = this.props;

    const values = pick(formValues, ['date', 'start', 'end']);
    const { showClosed } = formValues;

    // If there's an event but it has not populated yet, hold off on default props.
    if (event && !eventRecord) {
      return values;
    }

    const nowish = utils.roundTo(new Date()).tz(utils.getUserTimezone());

    // @todo: Take into account location hours.
    const { duration } = filters;

    if (!values.date) {
      values.date = filters.date || utils.getUserTimeNow();
    }

    if ((!values.start || !values.end) && ((utils.userIsStaff() && showClosed) || this.isWithinOpenHours(nowish))) {
      if (!values.start) {
        values.start = nowish.format('HHmm');
      }

      if (!values.end) {
        values.end = nowish.add(duration || 30, 'minutes').format('HHmm');
      }
    }

    return values;
  };

  getDisabledTimespans = () => {
    const availability = get(this, `props.availability.rooms.${this.props.room}.dates`);

    if (!availability) {
      return [];
    }

    return map(filter(availability, this.onlyTodaysReservations), item => ({
      start: utils.getTimeFromDate(utils.dateFromDrupal(item.start)),
      end: utils.getTimeFromDate(utils.dateFromDrupal(item.end)),
    }));
  };

  getHours = () => {
    const hours = this.props.hours;

    return hours
      ? {
        min: hours.min,
        max: (hours.max.endsWith('00') ? String(hours.max - 40 - lastReservationBeforeClosing) : String(hours.max - lastReservationBeforeClosing)),
      }
      : {
        min: null,
        max: null,
      };
  }

  isWithinOpenHours = (time) => {
    const hours = this.props.hours;

    if (hours == null) {
      return false;
    }

    const ct = t => parseInt(t, 10) / 1000;

    const now = ct(time.format('HHmm'));
    const start = ct(hours.min);
    const end = ct(hours.max);

    if (start < end && now > start && now < end) {
      return true;
    }

    return false;
  };

  handleCalendarNavigate = (date) => {
    this.props.onChange({
      ...this.props.formValues,
      [c.DATE]: date,
    });
  };

  render() {
    const {
      availability,
      onChange,
      isLoading,
      formValues,
      onChangeStep,
      hours,
      room,
      userStatus,
      dateLimits,
    } = this.props;

    const {
      maxDate,
      minDate,
    } = dateLimits;

    const isClosed = !hours || get(availability, `rooms.${room}.is_closed`);
    const closedMessage = isClosed ? get(availability, `rooms.${room}.closed_message`) : 'Location Closed';
    const limits = (utils.userIsStaff() && formValues.showClosed)
      ? {
        min: '0000',
        max: '2400',
      }
      : this.getHours();

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
      <div className="l--sidebar-before">
        <div className="l__main">
          <div className="l__secondary">
            <ReserveRoomDateForm
              room={room}
              values={formValues}
              onChange={onChange}
              onSubmit={() => onChangeStep(2)}
              min={limits.min}
              max={limits.max}
              disabledTimespans={this.getDisabledTimespans()}
              dateLimits={dateLimits}
            />
          </div>
          <div className="l__primary">
            {isLoading ? (
              <LoadingIndicator loading={isLoading} />
            ) : room ? (
              <RoomAvailabilityCalendar
                room={room}
                min={limits.min}
                max={limits.max}
                maxDate={maxDate}
                minDate={minDate}
                defaultDate={formValues.date}
                date={formValues.date}
                onNavigate={this.handleCalendarNavigate}
                availability={availability}
                isClosed={isClosed}
                closedMessage={closedMessage}
              />
            ) : (
              <div>
                <p>Choose a room to see its availability</p>
                <Button
                  className="value-summary__button"
                  variant="contained"
                  color="primary"
                  size="small"
                  onClick={() => onChangeStep(0)}
                >
                  Choose a Room
                </Button>
              </div>
            )}
          </div>
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state, ownProps) => {
  const hours =
    ownProps.formValues.date && ownProps.room
      ? // Open hours for current day.
      select.roomLocationHours(
        ownProps.room,
        utils.getDayTimeStamp(ownProps.formValues.date),
      )(state)
      : // Default open hours.
      select.locationsOpenHoursLimit(state);

  return {
    rooms: select.roomsAscending(state),
    isLoading:
      select.recordsAreLoading(c.TYPE_ROOM)(state) ||
      select.recordsAreLoading(c.TYPE_LOCATION)(state),
    locationRecord: ownProps.room ? select.roomLocationRecord(ownProps.room)(state) : null,
    // eventRecord: ownProps.event ? select.event(ownProps.event)(state) : null,
    hours,
    calendarRooms: [],
  };
};

ReserveRoomStep2.propTypes = {
  room: PropTypes.string,
  rooms: PropTypes.arrayOf(Object).isRequired,
  isLoading: PropTypes.bool.isRequired,
  onChange: PropTypes.func.isRequired,
  onChangeRoom: PropTypes.func.isRequired,
  onChangeStep: PropTypes.func.isRequired,
  hours: PropTypes.object,
};

ReserveRoomStep2.defaultProps = {
  view: 'list',
  calView: 'month',
  date: new Date(),
  filters: {},
  hours: null,
  room: null,
};

export default connect(mapStateToProps)(withAvailability(ReserveRoomStep2));
