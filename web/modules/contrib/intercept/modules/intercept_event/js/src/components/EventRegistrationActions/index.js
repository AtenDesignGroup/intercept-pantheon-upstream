// React
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

// Intercept
import DialogConfirm from 'intercept/Dialog/DialogConfirm';
import interceptClient from 'interceptClient';

const { constants, api, select } = interceptClient;
const c = constants;

// Local Components
import EventRegisterConfirmation from '../EventRegisterApp/EventRegisterConfirmation';
import EventRegistrationStatus from '../EventRegisterApp/EventRegistrationStatus';

import { Button } from '@material-ui/core';

const actionProperties = {
  default: {
    status: '',
    text: '',
    heading: '',
  },
  cancel: {
    status: 'canceled',
    heading: 'Are you sure you want to cancel this registration?',
    text: null,
  },
  deny: {
    status: 'denied',
    heading: 'Confirm deny',
    text: 'Confirm deny',
  },
  approve: {
    status: 'approved',
    heading: 'Confirm approval',
    text: 'Confirm approval',
  },
};

function getRegistrationActions(status) {
  let actions = [];
  switch (status) {
    case 'active':
      actions = ['cancel'];
      break;
    case 'canceled':
      actions = [];
      break;
    case 'waitlist':
      actions = ['cancel'];
      break;
    default:
      break;
  }

  return actions;
}

class EventRegistrationActions extends PureComponent {
  constructor(props) {
    super(props);
    this.state = {
      open: false,
      action: 'default',
    };
    this.onCancel = this.onCancel.bind(this);
    this.onClick = this.onClick.bind(this);
    this.onClose = this.onClose.bind(this);
    this.onConfirm = this.props.onConfirm.bind(this);
  }

  onClose() {
    this.onCancel();
  }

  onCancel() {
    this.setState({
      open: false,
    });
  }

  onClick(action) {
    return () => {
      this.setState({
        open: true,
        action,
      });
    };
  }

  render() {
    const { entity, registrationId, status } = this.props;
    const actions = getRegistrationActions(status);

    return (
      <div>
        {actions.length > 0 &&
          actions.map(action => (
            <Button key={action} onClick={this.onClick(action)} variant={action === 'cancel' ? 'outlined' : 'contained'} color="primary">
              {action}
            </Button>
          ))}
        <EventRegisterConfirmation
          open={this.state.open}
          onClose={this.onClose}
          onConfirm={this.onConfirm(entity, this.state.action)}
          onCancel={this.onCancel}
          uuid={registrationId}
          text={actionProperties[this.state.action].text}
          heading={actionProperties[this.state.action].heading}
        />
      </div>
    );
  }
}

EventRegistrationActions.propTypes = {
  // Pased Props
  registrationId: PropTypes.string.isRequired,
  // Connect
  entity: PropTypes.object,
  status: PropTypes.string,
  onConfirm: PropTypes.func,
};

EventRegistrationActions.defaultProps = {
  entity: null,
  onConfirm: null,
  status: null,
};

const mapStateToProps = (state, ownProps) => ({
  entity: select.record({ type: c.TYPE_EVENT_REGISTRATION, id: ownProps.registrationId })(state),
  status: select.registrationStatus(ownProps.registrationId)(state),
});

const mapDispatchToProps = (dispatch, ownProps) => {
  const onConfirm = (entity, action) => () => {
    const data = { ...entity.data };
    data.attributes.status = actionProperties[action].status;
    dispatch(interceptClient.actions.edit(data, c.TYPE_EVENT_REGISTRATION, ownProps.registrationId));
    return ownProps.registrationId;
  };

  return {
    onConfirm,
  };
};

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(EventRegistrationActions);
