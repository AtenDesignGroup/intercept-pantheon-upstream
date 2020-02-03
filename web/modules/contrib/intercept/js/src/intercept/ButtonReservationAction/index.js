import React from 'react';
import PropTypes from 'prop-types';

/* eslint-disable */
import interceptClient from 'interceptClient';

import { Button } from '@material-ui/core';
/* eslint-enable */

const { utils } = interceptClient;

const defaultUserId = utils.getUserUuid();

class ButtonReservationAction extends React.PureComponent {
  render() {
    const { onClick, text, variant } = this.props;

    return (
      <Button
        variant={variant}
        size="small"
        color="primary"
        className={'action-button__button'}
        onClick={onClick}
      >
        {text}
      </Button>
    );
  }
}

ButtonReservationAction.propTypes = {
  // Passed Props
  entityId: PropTypes.string.isRequired, // eslint-disable-line react/no-unused-prop-types
  onClick: PropTypes.func,
  text: PropTypes.string,
  variant: PropTypes.string,
};

ButtonReservationAction.defaultProps = {
  onClick: null,
  userId: defaultUserId,
  mustRegister: false,
  registrationAllowed: false,
  registerUrl: null,
  text: '',
  variant: 'outlined',
};

export default ButtonReservationAction;
