import React from 'react';
import PropTypes from 'prop-types';

/* eslint-disable */
import interceptClient from 'interceptClient';
import EditIcon from '@material-ui/icons/Edit';

import { Button, IconButton } from '@material-ui/core';

// Intercept Components
const { utils } = interceptClient;

// Local Components
class DateSummary extends React.PureComponent {
  render() {
    const { value, onClickChange } = this.props;
    const hasValue = !!value && !!value.date && !!value.start && !!value.end;

    if (hasValue) {
      const label = utils.getDateTimespanDisplay(value);

      return (
        <div className="value-summary">
          <h4 className="value-summary__label">
            Date &amp; Time
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
        <h4 className="value-summary__label">Date &amp; Time</h4>
        <Button
          className="value-summary__button"
          variant="contained"
          color="primary"
          size="small"
          onClick={onClickChange}
        >
          Choose a Time
        </Button>
      </div>
    );
  }
}

DateSummary.propTypes = {
  value: PropTypes.shape({
    date: PropTypes.object,
    start: PropTypes.string,
    end: PropTypes.string,
  }),
  onClickChange: PropTypes.func.isRequired,
};

DateSummary.defaultProps = {
  value: null,
};

export default DateSummary;
