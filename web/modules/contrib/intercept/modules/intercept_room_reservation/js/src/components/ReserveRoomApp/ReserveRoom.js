import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import moment from 'moment';
import debounce from 'lodash/debounce';
import get from 'lodash/get';
import pick from 'lodash/pick';
import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';

// Material UI
import Slide from '@material-ui/core/Slide';

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

addValidationRule(
  'isFutureDate',
  (values, value) =>
    // return true;
    !value || value >= utils.getUserStartOfDay(),
);

function getDateSpan(value, view = 'day') {
  const start = moment(value).startOf(view);
  const end = moment(value).endOf(view);

  // The calendar view may include date from the previous or next month
  // so we make sure to include the beginning of the first week and
  // end of the last week.
  if (view === 'month') {
    start.startOf('week');
    end.endOf('week');
  }
  return [start.toISOString(), end.toISOString()];
}

function getPublishedFilters(value = true) {
  return {
    published: {
      path: 'status',
      value: value ? '1' : '0',
    },
  };
}

function getDateFilters(values, view = 'list', calView = 'day', date = new Date()) {
  const path = 'field_date_time.value';
  let operator = '>';
  let value = moment(new Date())
    .subtract(1, 'day')
    .endOf('day')
    .toISOString();

  // Handler Calendar view.
  // The date should be determined by the date and calendar view type
  // rather than the selected date value.
  if (view === 'calendar') {
    value = getDateSpan(date, calView);
    operator = 'BETWEEN';
  }
  else if (values.date) {
    value = getDateSpan(values.date, 'day');
    operator = 'BETWEEN';
  }

  return {
    data: {
      path,
      value,
      operator,
    },
  };
}

function getFilters(values, view = 'list', calView = 'day', date = new Date()) {
  const filter = {
    ...getPublishedFilters(true),
  };

  if (!values) {
    return filter;
  }

  const types = [
    { id: c.TYPE_ROOM_TYPE, path: 'field_room_type.uuid', conjunction: 'OR' },
    { id: c.TYPE_LOCATION, path: 'field_location.uuid', conjunction: 'OR' },
    // { id: c.TYPE_AUDIENCE, path: 'field_event_audience.uuid', conjunction: 'OR' },
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

class ReserveRoom extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      // date: props.date,
      date: null,
      filters: props.filters,
      formValues: {
        [c.TYPE_ROOM]: props.room || null,
        date: null,
        start: null,
        end: null,
        attendees: null,
        groupName: '',
        meeting: false,
        [c.TYPE_MEETING_PURPOSE]: null,
        meetingDetails: '',
        refreshmentsDesc: '',
        user: utils.getUserUuid(),
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
          path: 'uuid',
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
          path: 'uuid',
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
        [c.TYPE_LOCATION]: ['uuid', 'title', 'field_location_hours', 'field_branch_location'],
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
    const nowish = utils.roundTo(new Date()).tz(tz);
    const minTime = '0000';
    const maxTime = '2345';
    const { duration } = filters;
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

  onExited() {
    this.setState({
      room: {
        ...this.state.room,
        exiting: false,
      },
    });
  }

  render() {
    const {
      step,
      onChangeStep,
      detail,
      roomDetail,
      onChangeDetail,
      onChangeRoomDetail,
      userStatus,
    } = this.props;

    const steps = [
      <ReserveRoomStep1
        onViewRoomDetail={(id) => {
          onChangeRoomDetail(id);
          onChangeDetail(true);
        }}
        {...this.props}
      />,
      <ReserveRoomStep2
        {...this.props}
        onChange={this.handleFormChange}
        formValues={this.state.formValues}
      />,
      <ReserveRoomStep3
        {...this.props}
        onChange={this.handleFormChange}
        formValues={this.state.formValues}
      />,
    ];

    return (
      <div className="l--offset">
        <header className="l__header l--section">
          <h1 className="page-title">Reserve a Room</h1>
          <ReserveRoomStepper
            {...this.props}
            step={step}
            onChangeStep={(s) => {
              this.props.fetchUserStatus();
              onChangeStep(s);
            }}

            values={this.state.formValues}
          />
          <RoomLimitWarning userStatus={userStatus} />
        </header>
        <div className="l__main">
          <div className="l__primary">{steps[step]}</div>
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
  date: new Date(),
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
