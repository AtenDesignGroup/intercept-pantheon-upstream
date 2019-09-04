import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import interceptClient from 'interceptClient';

import ButtonRegister from 'intercept/ButtonRegister';
import RegistrationStatus from 'intercept/RegistrationStatus';

const { api, select } = interceptClient;
const c = interceptClient.constants;

class EventRegisterButtonApp extends React.Component {
  componentDidMount() {
    this.props.fetchEvent(this.props.eventId);
    this.props.fetchRegistration(this.props.eventId, this.props.user);
  }

  render() {
    return (
      <div className="event-register-button__inner">
        {this.props.event && <ButtonRegister {...this.props} event={this.props.event.data} />}
        {this.props.event && <RegistrationStatus {...this.props} event={this.props.event.data} />}
      </div>
    );
  }
}

EventRegisterButtonApp.propTypes = {
  event: PropTypes.object,
  eventId: PropTypes.string.isRequired,
  registrations: PropTypes.array,
  fetchEvent: PropTypes.func.isRequired,
  fetchRegistration: PropTypes.func.isRequired,
  user: PropTypes.object,
};

EventRegisterButtonApp.defaultProps = {
  event: null,
  registrations: [],
};

const mapStateToProps = (state, ownProps) => ({
  event: select.record(select.getIdentifier(c.TYPE_EVENT, ownProps.eventId))(state),
  registrations: select.eventRegistrationsByEventByUser(ownProps.eventId, ownProps.user.uuid)(state),
});

const mapDispatchToProps = dispatch => ({
  fetchEvent: (id) => {
    dispatch(
      // @todo: Add support for fetching a single entity rather than fetching all filtered by uuid.
      api[c.TYPE_EVENT].fetchAll({
        filters: {
          uuid: {
            value: id,
            path: 'uuid',
          },
        },
      }),
    );
  },
  fetchRegistration: (id, user) => {
    dispatch(
      // @todo: Add support for fetching a single entity rather than fetching all filtered by uuid.
      api[c.TYPE_EVENT_REGISTRATION].fetchAll({
        filters: {
          uuid: {
            value: id,
            path: 'field_event.uuid',
          },
          user: {
            value: user.uuid,
            path: 'field_user.uuid',
          },
        },
      }),
    );
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(EventRegisterButtonApp);
