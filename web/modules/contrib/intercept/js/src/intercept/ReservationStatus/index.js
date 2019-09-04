import React from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

// Lodash

/* eslint-disable */
import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';
/* eslint-enable */

class ReservationStatus extends React.PureComponent {
  getText = (status, syncing) => {
    switch (status) {
      case 'denied':
        return syncing ? 'Denying' : 'Denied';
      case 'approved':
        return syncing ? 'Approving' : 'Approved';
      case 'canceled':
        return syncing ? 'Cancelling' : 'Canceled';
      case 'requested':
        return syncing ? 'Rerequesting' : 'Awaiting Approval';
      default:
        return null;
    }
  };

  render() {
    const text = this.getText(this.props.status, this.props.syncing);
    return text ? <p className="action-button__message">{text}</p> : null;
  }
}

ReservationStatus.propTypes = {
  status: PropTypes.string.isRequired,
  syncing: PropTypes.bool,
};

ReservationStatus.defaultProps = {
  syncing: false,
};

export default ReservationStatus;
