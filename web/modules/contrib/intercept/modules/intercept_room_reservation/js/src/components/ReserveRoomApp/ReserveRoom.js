import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import moment from 'moment';
import get from 'lodash/get';
import pick from 'lodash/pick';
import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';

// Formsy
import { addValidationRule } from 'formsy-react';

// Local Components
import ReserveRoomStepper from './ReserveRoomStepper';

import ReserveRoomStep1 from './Step1';
import ReserveRoomStep2 from './Step2';
import ReserveRoomStep3 from './Step3';
import RoomLimitWarning from './RoomLimitWarning';
import RoomDetailDialog from './RoomDetailDialog';
import withUserStatus from './withUserStatus';

const { constants, api, select, utils } = interceptClient;
const c = constants;
const roomIncludes = ['image_primary', 'image_primary.field_media_image'];

// Buffer around meeting times for events.
const ROOM_RESERVATION_MEETING_BUFFER = 30;
const ROOM_RESERVATION_DEFAULT_ATTENDEES_COUNT = 10;

const daysInAdvance = get(drupalSettings, 'intercept.room_reservations.customer_advanced_limit', '10');
const daysInAdvanceText = get(drupalSettings, 'intercept.room_reservations.customer_advanced_text');

const getMaxDate = () => {
  if (utils.userIsStaff()) {
    return undefined;
  }

  return (daysInAdvance && daysInAdvance !== '0')
    ? moment()
      .tz(utils.getUserTimezone())
      .add(daysInAdvance, 'days')
      .format('YYYY-MM-DD')
    : undefined;
};

const getMinDate = () => {
  if (utils.userIsStaff()) {
    return undefined;
  }

  return moment()
    .tz(utils.getUserTimezone())
    .format('YYYY-MM-DD');
};

const getMaxDateDescription = () => {
  if (utils.userIsStaff()) {
    return undefined;
  }

  return daysInAdvanceText;
};

const MAX_DATE = getMaxDate();
const MAX_DATE_DESCRIPTION = getMaxDateDescription();
const MIN_DATE = getMinDate();

export const isFutureTime = (time, date) => {
  if (time === null) {
    return true;
  }
  const now = new Date();
  return utils.getDateFromTime(time, date) >= now;
};

export const isLessThanMaxTime = (time, date) => {
  if (!MAX_DATE) {
    return true;
  }

  if (time === null) {
    return true;
  }

  return utils.getDateFromTime(time, date) <= utils.getDateFromTime(time, MAX_DATE);
};

export const isLessThanMaxDate = (date) => {
  if (!MAX_DATE) {
    return true;
  }

  return utils.getDayTimeStamp(date) <= MAX_DATE;
};

/**
 * Checks to see if the entered start and end times are valid reservation times.
 * @param {*} date
 * @param {*} start
 * @param {*} end
 */
export const isValidDateTime = (date, start, end) => {
  if (start === null || end === null || date === null) {
    return false;
  }
  return isFutureTime(start, date) && isLessThanMaxTime(end, date);
};

addValidationRule(
  'isFutureDate',
  (values, value) =>
    // return true;
    !value || value >= utils.getUserStartOfDay(),
);

addValidationRule(
  'isLessThanMaxDate',
  (values, value) =>
    // return true;
    !value || isLessThanMaxDate(value),
);

class ReserveRoom extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      date: null,
      filters: props.filters,
      formValues: {
        [c.TYPE_ROOM]: props.room || null,
        date: null,
        start: null,
        end: null,
        agreement: utils.userIsStaff(),
        attendees: null,
        groupName: '',
        meeting: false,
        [c.TYPE_MEETING_PURPOSE]: null,
        meetingDetails: '',
        refreshmentsDesc: '',
        user: utils.getUserUuid(),
        showClosed: false,
      },
      room: {
        current: null,
        previous: null,
        exiting: false,
      },
    };
    this.onExited = this.onExited.bind(this);
  }

  componentDidMount() {
    if (this.props.event) {
      const filters = {
        uuid: {
          path: 'id',
          value: this.props.event,
        },
      };
      this.props.fetchEvent({
        filters,
        include: ['field_room'],
      });
    }

    if (this.props.room) {
      const filters = {
        uuid: {
          path: 'id',
          value: this.props.room,
        },
      };
      this.props.fetchRooms({
        filters,
        sort: {
          title: {
            path: 'title',
            // direction: getSortDirection(view, values),
          },
        },
        include: [...roomIncludes],
        headers: {
          'X-Consumer-ID': interceptClient.consumer,
        },
      });
    }

    this.props.fetchLocations({
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

    this.props.fetchUserStatus();
  }

  componentDidUpdate(prevProps) {
    // If we just received event data, let's go ahead and update the form values.
    if (prevProps.eventRecord !== this.props.eventRecord) {
      this.handleFormChange(this.getEventValues());
      this.props.onChangeRoom(get(this, 'props.eventRecord.data.relationships.field_room.data.id'));
    }
  }

  onExited() {
    this.setState({
      room: {
        ...this.state.room,
        exiting: false,
      },
    });
  }

  getEventValues = () => {
    const { formValues, room, event, eventRecord, locationRecord, filters } = this.props;

    const values = pick(formValues, [
      'date',
      'start',
      'end',
      'attendees',
      'groupName',
    ]);

    // If there's an event but it has not populated yet, hold off on default props.
    if (event && !eventRecord) {
      return values;
    }

    const { data } = eventRecord;

    const tz = utils.getUserTimezone();
    const startValue = utils.dateFromDrupal(get(data, 'attributes.field_date_time.value'));
    const endValue = utils.dateFromDrupal(get(data, 'attributes.field_date_time.end_value'));

    if (!values.date) {
      values.date = moment(startValue)
        .tz(tz)
        .startOf('day')
        .toDate();
    }

    if (!values.start) {
      values.start = moment(startValue)
        .tz(tz)
        .subtract(ROOM_RESERVATION_MEETING_BUFFER, 'minutes')
        .format('HHmm');
    }

    if (!values.end) {
      values.end = moment(endValue)
        .tz(tz)
        .add(ROOM_RESERVATION_MEETING_BUFFER, 'minutes')
        .format('HHmm');
    }

    if (!values.attendees) {
      values.attendees =
        get(data, 'attributes.field_capacity_max') || ROOM_RESERVATION_DEFAULT_ATTENDEES_COUNT;
    }

    if (!values.groupName) {
      values.groupName = get(data, 'attributes.title');
    }

    return values;
  };

  handleFormChange = (formValues) => {
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
      formValues: {
        ...this.state.formValues,
        ...formValues,
        [c.TYPE_ROOM]: room,
      },
    });
  };

  render() {
    const {
      step,
      onChangeStep,
      detail,
      room,
      roomDetail,
      onChangeDetail,
      onChangeRoomDetail,
      userStatus,
    } = this.props;

    const {
      formValues,
    } = this.state;

    const {
      date,
      end,
      start,
    } = formValues;

    const dateLimits = {
      maxDate: MAX_DATE,
      minDate: MIN_DATE,
      maxDateDescription: MAX_DATE_DESCRIPTION,
    };

    const steps = [
      <ReserveRoomStep1
        onViewRoomDetail={(id) => {
          onChangeRoomDetail(id);
          onChangeDetail(true);
        }}
        dateLimits={dateLimits}
        {...this.props}
      />,
      <ReserveRoomStep2
        {...this.props}
        dateLimits={dateLimits}
        onChange={this.handleFormChange}
        formValues={this.state.formValues}
      />,
      <ReserveRoomStep3
        {...this.props}
        dateLimits={dateLimits}
        onChange={this.handleFormChange}
        formValues={this.state.formValues}
      />,
    ];

    let currentStep = step;

    // Redirect to step 1 if the date is invalid
    if (step === 2 && !isValidDateTime(date, start, end)) {
      currentStep = 1;
    }
    else if (step === 2 && room === null) {
      currentStep = 0;
    }

    return (
      <div className="l--offset">
        <header className="l__header l--section">
          <h1 className="page-title">Reserve a Room</h1>
          <ReserveRoomStepper
            {...this.props}
            step={currentStep}
            onChangeStep={(s) => {
              this.props.fetchUserStatus();
              onChangeStep(s);
            }}
            values={this.state.formValues}
          />
          <RoomLimitWarning userStatus={userStatus} />
        </header>
        <div className="l__main">
          <div className="l__primary">{steps[currentStep]}</div>
        </div>
        <RoomDetailDialog
          open={!!(detail && roomDetail)}
          onClose={() => {
            onChangeDetail(false);
          }}
          id={roomDetail}
        />
      </div>
    );
  }
}

ReserveRoom.propTypes = {
  rooms: PropTypes.arrayOf(Object).isRequired,
  roomsLoading: PropTypes.bool.isRequired,
  fetchLocations: PropTypes.func.isRequired,
  fetchRooms: PropTypes.func.isRequired,
  fetchUser: PropTypes.func.isRequired,
  fetchUserStatus: PropTypes.func.isRequired,
  fetchEvent: PropTypes.func.isRequired,
  detail: PropTypes.bool,
  // Props from URL
  event: PropTypes.string,
  onChangeStep: PropTypes.func.isRequired,
  onChangeRoom: PropTypes.func.isRequired,
  onChangeDetail: PropTypes.func.isRequired,
  onChangeRoomDetail: PropTypes.func.isRequired,
  room: PropTypes.string,
  roomDetail: PropTypes.string,
  step: PropTypes.number,
  filters: PropTypes.object,
};

ReserveRoom.defaultProps = {
  view: 'list',
  calView: 'month',
  date: utils.getUserTimeNow(),
  filters: {},
  step: 0,
  detail: false,
  roomDetail: null,
  room: null,
  event: null,
};

const mapStateToProps = (state, ownProps) => ({
  eventRecord: ownProps.event ? select.event(ownProps.event)(state) : null,
  rooms: select.roomsAscending(state),
  roomsLoading: select.recordsAreLoading(c.TYPE_ROOM)(state),
  openHoursLimit: select.locationsOpenHoursLimit(state),
  calendarRooms: [],
});

const mapDispatchToProps = dispatch => ({
  fetchRooms: (options) => {
    dispatch(api[c.TYPE_ROOM].fetchAll(options));
  },
  fetchEvent: (options) => {
    dispatch(api[c.TYPE_EVENT].fetchAll(options));
  },
  fetchLocations: (options) => {
    dispatch(api[c.TYPE_LOCATION].fetchAll(options));
  },
  fetchUser: (options) => {
    dispatch(api[c.TYPE_USER].fetchAll(options));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(withUserStatus(ReserveRoom));
