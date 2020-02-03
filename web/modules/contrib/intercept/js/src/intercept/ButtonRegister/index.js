import React from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

/* eslint-disable */
import interceptClient from 'interceptClient';

import { Button } from '@material-ui/core';
/* eslint-enable */

const { select, utils } = interceptClient;

const defaultUserId = utils.getUserUuid();

function ButtonRegister(props) {
  const { onClick, mustRegister, registerUrl, text, registrationAllowed } = props;

  return mustRegister ? (
    <Button
      href={onClick ? null : registerUrl}
      variant={text === 'register' ? 'contained' : 'outlined'}
      size="small"
      color="primary"
      className={'action-button__button'}
      disabled={!registrationAllowed}
      onClick={onClick}
    >
      {text === 'Cancel' && registerUrl ? 'View Registration' : text}
    </Button>
  ) : null;
}

ButtonRegister.propTypes = {
  // Passed Props
  eventId: PropTypes.string.isRequired, // eslint-disable-line react/no-unused-prop-types
  userId: PropTypes.string, // eslint-disable-line react/no-unused-prop-types
  onClick: PropTypes.func,
  // connect
  mustRegister: PropTypes.bool,
  registrationAllowed: PropTypes.bool,
  registerUrl: PropTypes.string,
  text: PropTypes.string,
};

ButtonRegister.defaultProps = {
  onClick: null,
  userId: defaultUserId,
  mustRegister: false,
  registrationAllowed: false,
  registerUrl: null,
  text: '',
};

const mapStateToProps = (state, ownProps) => {
  const { eventId } = ownProps;

  // Event
  const mustRegister = select.mustRegisterForEvent(eventId)(state);
  const registerUrl = select.registerUrl(eventId)(state);

  // User
  const userId = ownProps.userId || defaultUserId;

  // Registrations
  const text = select.registrationButtonText(eventId, userId)(state);
  const registrationAllowed = select.registrationAllowed(eventId, userId)(state);

  return {
    mustRegister,
    registerUrl,
    text,
    registrationAllowed,
  };
};

export default connect(mapStateToProps)(ButtonRegister);
