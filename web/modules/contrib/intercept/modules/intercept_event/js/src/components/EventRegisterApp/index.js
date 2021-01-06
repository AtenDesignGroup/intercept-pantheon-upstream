import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

import get from 'lodash/get';

// Intercept
/* eslint-disable */
import interceptClient from 'interceptClient';
import LoadingIndicator from 'intercept/LoadingIndicator';
/* eslint-enable */

import EventRegisterGuestForm from './EventRegisterGuestForm';

import EventRegisterForm from './EventRegisterForm';
import EventRegistrationTable from './EventRegistrationTable';

const { api, select } = interceptClient;
const c = interceptClient.constants;

class EventRegisterApp extends React.Component {
  componentDidMount() {
    this.props.fetchSegments();
    this.props.fetchEvent(this.props.eventId);
    this.props.fetchUser(this.props.user.uuid);
    this.props.fetchRegistrations(this.props.eventId);
  }

  componentDidUpdate(prevProps) {
    // Refetch events after any registrations have updated.
    // We do this to keep registration data fresh, especially
    // after a registration is canceled. Otherwise the registration
    // count would be off.
    const wasSyncing = prevProps.registrations.some(i => i.state.syncing);
    if (wasSyncing && !this.props.registrations.some(i => i.state.syncing)) {
      this.props.fetchEvent(this.props.eventId);
    }
  }

  onlyActiveOrWaitlist = () =>
    this.props.registrations.filter(
      r =>
        (r.data.attributes.status === 'active' || r.data.attributes.status === 'waitlist') &&
        r.state.saved,
    );

  acceptingReservations = () => {
    const status = get(this, 'props.event.data.attributes.registration.status');

    switch (status) {
      case 'open':
      case 'waitlist':
        return true;
      default:
        return false;
    }
  };

  render() {
    const { registrations, registrationsLoading, eventId } = this.props;

    if (registrationsLoading) {
      return <LoadingIndicator loading />;
    }

    let action = null;
    let form = null;
    if (window.drupalSettings.user.uid === 0) {
      action = <EventRegisterGuestForm {...this.props} eventId={eventId} />;
    }
    else {
      action = <EventRegisterForm {...this.props} eventId={eventId} />;
    }

    const userHasNotRegistered = this.onlyActiveOrWaitlist().length <= 0;

    if (userHasNotRegistered) {
      form = this.acceptingReservations() ? (
        action
      ) : (
        <p>Registrations are not being accepted at this time.</p>
      );
    }

    const table = registrations.length > 0 ? <EventRegistrationTable eventId={eventId} /> : null;

    return (
      <div className="l--offset">
        <div className="l--section">{form}</div>
        <div className="l--section">{table}</div>
      </div>
    );
  }
}

EventRegisterApp.propTypes = {
  registrations: PropTypes.array,
  event: PropTypes.object,
  user: PropTypes.object.isRequired,
  segments: PropTypes.arrayOf(PropTypes.object),
  eventId: PropTypes.string.isRequired,
  fetchEvent: PropTypes.func.isRequired,
  fetchUser: PropTypes.func.isRequired,
  fetchRegistrations: PropTypes.func.isRequired,
  fetchSegments: PropTypes.func.isRequired,
  registrationsLoading: PropTypes.bool.isRequired,
  eventsLoading: PropTypes.bool.isRequired,
  segmentsLoading: PropTypes.bool.isRequired,
};

EventRegisterApp.defaultProps = {
  event: null,
  registrations: [],
  segments: [],
};

const mapStateToProps = (state, ownProps) => ({
  registrations: select.eventRegistrationsByEventByUser(ownProps.eventId, ownProps.user.uuid)(
    state,
  ),
  registrationsLoading: select.recordsAreLoading(c.TYPE_EVENT_REGISTRATION)(state),
  event: select.record(select.getIdentifier(c.TYPE_EVENT, ownProps.eventId))(state),
  users: select.record(select.getIdentifier(c.TYPE_USER, ownProps.user.uuid))(state),
  eventsLoading: select.recordIsLoading(c.TYPE_EVENT, ownProps.eventId)(state),
  segments: select.recordOptions(c.TYPE_POPULATION_SEGMENT)(state),
  segmentsLoading: select.recordsAreLoading(c.TYPE_POPULATION_SEGMENT)(state),
});

const mapDispatchToProps = (dispatch, ownProps) => ({
  fetchEvent: (id) => {
    dispatch(
      api[c.TYPE_EVENT].fetchResource(id, {
        fields: {
          [c.TYPE_EVENT]: [
            'drupal_internal__nid',
            'field_capacity_max',
            'field_waitlist_max',
            'field_event_register_period',
            'field_event_user_reg_max',
            'field_has_waitlist',
            'field_must_register',
            'registration',
          ],
        },
      }),
    );
  },
  fetchUser: (id) => {
    dispatch(
      api[c.TYPE_USER].fetchResource(id),
    );
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
  fetchRegistrations: (eventId) => {
    dispatch(
      api[c.TYPE_EVENT_REGISTRATION].fetchAll({
        filters: {
          event: {
            value: eventId,
            path: 'field_event.id',
          },
          user: {
            value: ownProps.user.uuid,
            path: 'field_user.id',
          },
        },
      }),
    );
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(EventRegisterApp);
