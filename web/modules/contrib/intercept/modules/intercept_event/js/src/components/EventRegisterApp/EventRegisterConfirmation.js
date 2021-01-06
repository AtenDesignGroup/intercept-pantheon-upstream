import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

import get from 'lodash/get';

import interceptClient from 'interceptClient';
import DialogConfirm from 'intercept/Dialog/DialogConfirm';
import LoadingIndicator from 'intercept/LoadingIndicator';

import EventRegistrationStatus from './EventRegistrationStatus';
import withEventRegistrations from './withEventRegistrations';

const { api, constants, session, select } = interceptClient;
const c = constants;

// State constants
const IDLE = 'idle';
const CONFLICT = 'conflict';
const SAVED = 'saved';
const ERROR = 'error';
const LOADING = 'loading';
const VALIDATE = 'validate';

function getCapacity(data) {
  return get(data, 'data.attributes.field_capacity_max') || 0;
}

function getRegistrationCount(data) {
  return get(data, 'data.attributes.registration.total');
}

function getRegistrationStatus(data) {
  return get(data, 'data.attributes.registration.status');
}

function getAvailableCapacity(data) {
  return getCapacity(data) - getRegistrationCount(data);
}

function getWaitlistCapacity(data) {
  return get(data, 'data.attributes.field_waitlist_max') || 0;
}

function getWaitlistRegistrationCount(data) {
  return get(data, 'data.attributes.registration.total_waitlist');
}

function getAvailableWaitlistCapacity(data) {
  return getWaitlistCapacity(data) - getWaitlistRegistrationCount(data);
}

function isAcceptingRegistrations(data) {
  return ['open', 'waitlist'].indexOf(getRegistrationStatus(data)) >= 0;
}

function isOverCapacity(total, data) {
  if (getCapacity(data) === 0) {
    return false;
  }
  return getAvailableCapacity(data) - total < 0;
}

function isOverTotalCapacity(total, data) {
  const capacity = getCapacity(data);
  if (capacity === 0) {
    return false;
  }
  return capacity > 0 && capacity - total < 0;
}

function isOverWaitlistCapacity(total, data) {
  if (getWaitlistCapacity(data) === 0) {
    return false;
  }
  return getAvailableWaitlistCapacity(data) - total < 0;
}

function canRegister(total, data, status) {
  if (!isAcceptingRegistrations(data)) {
    return false;
  }

  switch (status) {
    case 'active':
      return !isOverCapacity(total, data) && !isOverTotalCapacity(total, data);
    case 'waitlist':
      return !isOverWaitlistCapacity(total, data) && !isOverTotalCapacity(total, data);
    default:
      return !isOverTotalCapacity(total, data);
  }
}

class EventRegisterConfirmation extends React.PureComponent {
  constructor(props) {
    super(props);
    this.state = {
      saved: false,
      state: IDLE,
      uuid: props.uuid || null,
    };

    this.handleConfirm = this.handleConfirm.bind(this);
  }

  /**
   * Checks room availabilty.
   *
   * @memberof ReserveRoomConfirmation
   */
  checkEventRegistrations(eventId, total, status) {
    const { fetchEventRegistrations } = this.props;

    this.setState({ state: VALIDATE });

    // Checks reservation for potential conflicts.
    return new Promise((resolve, reject) => {
      try {
        fetchEventRegistrations(eventId, (r) => {
          const res = JSON.parse(r);

          if (!canRegister(total, res, status)) {
            reject();
            this.setState({ state: CONFLICT });
          }
          else {
            resolve(res);
          }
        });
      }
      catch (error) {
        reject(error);
        this.setState({ state: ERROR });
      }
    });
  }

  handleConfirm() {
    const { onConfirm, save, eventId, total, status } = this.props;

    if (this.props.uuid) {
      const uuid = onConfirm();
      save(uuid);
      this.setState({
        saved: true,
        uuid,
        state: LOADING,
      });
    }
    else {
      // Make one last avaialbilty check
      // then -> save
      // reject -> display error with link back to step 2
      this.checkEventRegistrations(eventId, total, status)
        .then((res) => {
          const uuid = onConfirm();
          save(uuid);
          this.setState({
            saved: true,
            uuid,
            state: LOADING,
          });
        })
        .catch(() => {
          this.setState({ state: CONFLICT });
        });
    }
  }

  render() {
    const { open, onCancel, heading, text } = this.props;
    const { saved, uuid, state } = this.state;

    let dialogProps = {
      confirmText: 'Yes',
      cancelText: 'No',
      heading,
      text,
      onConfirm: this.handleConfirm,
      onCancel,
    };
    let content = null;

    if (saved) {
      if (uuid) {
        content = <EventRegistrationStatus uuid={uuid} />;
      }
      dialogProps = {
        confirmText: null,
        cancelText: 'Close',
        heading: '',
        onConfirm: () => {
          window.location.href = '/user';
        },
        onCancel,
      };
    }

    if (state === CONFLICT) {
      dialogProps = {
        confirmText: null,
        cancelText: 'Close',
        heading: 'Registration Incomplete',
        onConfirm: () => {},
        text: 'We were unable to confirm your registration. Please try again.',
        onCancel: () => {
          onCancel();
          // Reload the page to get fresh event data.
          document.location.reload(true);
        },
      };
    }

    return (
      <DialogConfirm {...dialogProps} open={open}>
        {content}
      </DialogConfirm>
    );
  }
}

EventRegisterConfirmation.propTypes = {
  eventId: PropTypes.string,
  onConfirm: PropTypes.func,
  onCancel: PropTypes.func,
  open: PropTypes.bool,
  save: PropTypes.func.isRequired,
  uuid: PropTypes.string,
  heading: PropTypes.string,
  status: PropTypes.string,
  text: PropTypes.string,
  total: PropTypes.number,
};

EventRegisterConfirmation.defaultProps = {
  eventId: null,
  onConfirm: null,
  onCancel: null,
  open: false,
  uuid: null,
  heading: 'Are you sure you want to register?',
  status: null,
  text: null,
  total: 0,
};

const mapStateToProps = () => ({});

const mapDispatchToProps = dispatch => ({
  save: (uuid) => {
    session
      .getToken()
      .then((token) => {
        dispatch(api[c.TYPE_EVENT_REGISTRATION].sync(uuid, { headers: { 'X-CSRF-Token': token } }));
      })
      .catch((e) => {
        console.log('Unable to save registration', e);
      });
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(withEventRegistrations(EventRegisterConfirmation));
