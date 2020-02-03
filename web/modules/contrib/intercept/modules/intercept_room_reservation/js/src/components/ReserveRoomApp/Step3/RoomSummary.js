import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

/* eslint-disable */
import interceptClient from 'interceptClient';
import EditIcon from '@material-ui/icons/Edit';

import { Button, IconButton } from '@material-ui/core';

// Intercept Components
const { select } = interceptClient;

// Local Components
class RoomSummary extends React.PureComponent {
  render() {
    const { value, label, onClickChange } = this.props;
    const hasValue = !!value;

    if (hasValue) {
      return (
        <div className="value-summary">
          <h4 className="value-summary__label">
            Room
            <IconButton
              className="value-summary__icon-button"
              aria-label="Edit"
              color="primary"
              onClick={onClickChange}
            >
              <EditIcon />
            </IconButton>
          </h4>
          <p className="value-summary__value">{label}</p>
        </div>
      );
    }
    return (
      <div className="value-summary">
        <h4 className="value-summary__label">Room</h4>
        <Button
          className="value-summary__button"
          variant="contained"
          color="primary"
          size="small"
          onClick={onClickChange}
        >
          Choose a Room
        </Button>
      </div>
    );
  }
}

const mapStateToProps = (state, ownProps) => {
  if (!ownProps.value) {
    return {};
  }

  const roomLabel = select.roomLabel(ownProps.value)(state);
  const locationLabel = select.roomLocationLabel(ownProps.value)(state);

  if (!roomLabel && !locationLabel) {
    return {};
  }

  return {
    label: locationLabel ? `${locationLabel}: ${roomLabel}` : roomLabel,
  };
};

RoomSummary.propTypes = {
  value: PropTypes.string,
  label: PropTypes.string,
  onClickChange: PropTypes.func.isRequired,
};

RoomSummary.defaultProps = {
  value: '',
  label: null,
};

export default connect(mapStateToProps)(RoomSummary);
