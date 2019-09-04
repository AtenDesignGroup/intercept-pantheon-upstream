import React from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

// Lodash
import get from 'lodash/get';

// Material UI
import Button from '@material-ui/core/Button';

/* eslint-disable */
import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';
/* eslint-enable */

const { select } = interceptClient;

const defaultUserId = get(drupalSettings, 'intercept.user.uuid');

class ButtonReservationAction extends React.PureComponent {

  render() {
    const {
      onClick,
      text,
      variant,
    } = this.props;

    return (<Button
      variant={variant}
      size="small"
      color="primary"
      className={'action-button__button'}
      onClick={onClick}
    >
      {text}
    </Button>);
  }
}

ButtonReservationAction.propTypes = {
  // Passed Props
  entityId: PropTypes.string.isRequired, // eslint-disable-line react/no-unused-prop-types
  onClick: PropTypes.func,
  type: PropTypes.string.isRequired,
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

const mapStateToProps = (state, ownProps) => {
  const { entityId, type } = ownProps;

  // Reservations
  const text = select.reservationButtonText(entityId, type)(state);

  return {
    text,
  };
};

export default ButtonReservationAction;
// export default connect(mapStateToProps)(ButtonReservationAction);
