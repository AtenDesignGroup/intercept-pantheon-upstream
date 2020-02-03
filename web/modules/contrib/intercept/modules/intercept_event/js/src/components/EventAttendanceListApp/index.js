// React
import React, { Component } from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

// Moment
import moment from 'moment';

// Drupal
import drupalSettings from 'drupalSettings';

// Lodash
import debounce from 'lodash/debounce';

// Intercept
import interceptClient from 'interceptClient';

// Intercept Components
import DialogConfirm from 'intercept/Dialog/DialogConfirm';

import ViewSwitcher from 'intercept/ViewSwitcher';
// Local Components
import ContentList from 'intercept/ContentList';

import EventRegistrationActions from '../EventRegistrationActions';
import EventAttendanceTable from './EventAttendanceTable';
import EventTeaser from 'intercept/EventTeaser';
import { CircularProgress } from '@material-ui/core';

const { constants, api, select } = interceptClient;
const c = constants;

class EventAttendanceListApp extends Component {
  constructor(props) {
    super(props);
    this.state = {
      open: false,
    };
    this.handleViewChange = this.handleViewChange.bind(this);
    this.doFetch = debounce(this.doFetch, 300).bind(this);
    this.doFetchRegistrations = this.doFetchRegistrations.bind(this);
    this.doFetchSavedEvents = this.doFetchSavedEvents.bind(this);
  }

  componentDidMount() {
    this.props.fetchSegments();
    this.doFetch();
  }

  handleViewChange = (value) => {
    this.doFetch(value);
  };

  doFetchAttendance() {
    this.props.fetchAttendance({
      filters: {
        event: {
          path: 'field_event.id',
          value: this.props.event.uuid,
        },
      },
      include: ['field_user'],
      headers: {
        'X-Consumer-ID': interceptClient.consumer,
      },
    });
  }

  doFetchRegistrations() {
    this.props.fetchRegistrations({
      filters: {
        event: {
          path: 'field_event.id',
          value: this.props.event.uuid,
        },
        status: {
          path: 'status',
          value: ['active', 'waitlist'],
          operator: 'IN',
        },
      },
      include: ['field_user'],
      headers: {
        'X-Consumer-ID': interceptClient.consumer,
      },
    });
  }

  doFetchSavedEvents() {
    this.props.fetchSavedEvents({
      filters: {
        event: {
          path: 'entity_id',
          value: this.props.event.nid,
        },
      },
      include: ['uid'],
      headers: {
        'X-Consumer-ID': interceptClient.consumer,
      },
    });
  }

  doFetch() {
    this.doFetchSavedEvents();
    this.doFetchRegistrations();
    this.doFetchAttendance();
  }

  doConfirmAction() {
    this.setState({
      open: true,
      text: 'Confirm cancel',
    });
  }

  render() {
    const { isLoading, event, users, registrations, attendance, savedEvents } = this.props;

    const tableProps = {
      eventId: event.uuid,
      users,
      registrations,
      attendance,
      savedEvents,
    };

    return (
      <EventAttendanceTable {...tableProps} />
    );
  }
}

EventAttendanceListApp.propTypes = {
  event: PropTypes.shape({
    nid: PropTypes.string.isRequired,
    uuid: PropTypes.string.isRequired,
  }).isRequired,
  fetchAttendance: PropTypes.func.isRequired,
  fetchRegistrations: PropTypes.func.isRequired,
  fetchSavedEvents: PropTypes.func.isRequired,
  fetchSegments: PropTypes.func.isRequired,
  users: PropTypes.object,
  attendance: PropTypes.object,
  registrations: PropTypes.object,
  savedEvents: PropTypes.object,
};

EventAttendanceListApp.defaultProps = {
  users: {},
  attendance: {},
  registrations: {},
  savedEvents: {},
};

const mapStateToProps = (state, ownProps) => ({
  attendance: select.records([c.TYPE_EVENT_ATTENDANCE])(state),
  registrations: select.records([c.TYPE_EVENT_REGISTRATION])(state),
  savedEvents: select.records([c.TYPE_SAVED_EVENT])(state),
  users: select.records([c.TYPE_USER])(state),
  isLoading:
    select.recordsAreLoading(c.TYPE_EVENT_ATTENDANCE)(state) ||
    select.recordsAreLoading(c.TYPE_EVENT_REGISTRATION)(state) ||
    select.recordsAreLoading(c.TYPE_SAVED_EVENT)(state),
});

const mapDispatchToProps = (dispatch, ownProps) => ({
  fetchAttendance: (options) => {
    dispatch(api[c.TYPE_EVENT_ATTENDANCE].fetchAll(options));
  },
  fetchRegistrations: (options) => {
    dispatch(api[c.TYPE_EVENT_REGISTRATION].fetchAll(options));
  },
  fetchSavedEvents: (options) => {
    dispatch(api[c.TYPE_SAVED_EVENT].fetchAll(options));
  },
  fetchSegments: (
    options = {
      fields: {
        [c.TYPE_POPULATION_SEGMENT]: ['name', 'weight'],
      },
    },
  ) => {
    dispatch(api[c.TYPE_POPULATION_SEGMENT].fetchAll(options));
  },
});

export default connect(mapStateToProps, mapDispatchToProps)(EventAttendanceListApp);
