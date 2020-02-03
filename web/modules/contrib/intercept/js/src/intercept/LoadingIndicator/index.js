// React
import React from 'react';
import PropTypes from 'prop-types';

import { CSSTransition } from 'react-transition-group';
import { CircularProgress } from '@material-ui/core';

const LoadingIndicator = (props) => {
  const { loading, label, size } = props;

  return (
    <CSSTransition
      in={loading}
      timeout={300}
      classNames="loading-indicator"
    >
      <div className="loading-indicator">
        <CircularProgress size={size} /> <span className="loading-indicator__label">{label}</span>
      </div>
    </CSSTransition>
  );
};

LoadingIndicator.propTypes = {
  loading: PropTypes.bool.isRequired,
  label: PropTypes.string,
  size: PropTypes.number,
};

LoadingIndicator.defaultProps = {
  label: 'Loading',
  size: 40,
};

export default LoadingIndicator;
