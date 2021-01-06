import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import interceptClient from 'interceptClient';
import moment from 'moment';

import ButtonRegister from 'intercept/ButtonRegister';
import RegistrationStatus from 'intercept/RegistrationStatus';

const { api, select, utils } = interceptClient;
const c = interceptClient.constants;

class EventRegisterButtonApp extends React.Component {
  componentDidMount() {
    this.props.fetchEvent(this.props.eventId);
    this.props.fetchRegistration(this.props.eventId, this.props.user);
  }

  render() {
    console.log({...this.props});
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
      api[c.TYPE_EVENT].fetchResource(id),
    );
  },
  fetchRegistration: (id, user) => {
    dispatch(
      api[c.TYPE_EVENT_REGISTRATION].fetchAll({
        filters: {
          date: {
            value: moment().tz(utils.getUserTimezone()).startOf('day').format(),
            path: 'field_event.field_date_time.value',
            operator: '>=',
          },
          user: {
            value: user.uuid,
            path: 'field_user.id',
          },
        },
      })
    );
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(EventRegisterButtonApp);
