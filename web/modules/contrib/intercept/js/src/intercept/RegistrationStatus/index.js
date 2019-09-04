import React from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

// Lodash
import get from 'lodash/get';

/* eslint-disable */
import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';
/* eslint-enable */

const { select } = interceptClient;

const defaultUserId = get(drupalSettings, 'intercept.user.uuid');

function RegistrationStatus(props) {
  const { text } = props;

  return text ? <p className="action-button__message">{text}</p> : null;
}

RegistrationStatus.propTypes = {
  // Passed Props
  eventId: PropTypes.string.isRequired,
  userId: PropTypes.string,
  // connect
  text: PropTypes.string,
};

RegistrationStatus.defaultProps = {
  text: null,
  userId: defaultUserId,
};

const mapStateToProps = (state, ownProps) => {
  const { eventId, userId } = ownProps;

  const text = select.registrationStatusText(eventId, userId || defaultUserId)(state);

  return {
    text,
  };
};

export default connect(mapStateToProps)(RegistrationStatus);
