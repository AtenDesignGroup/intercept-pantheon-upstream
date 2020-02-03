import React from 'react';
import PropTypes from 'prop-types';
import { withStyles } from '@material-ui/core/styles';
import { LinearProgress } from '@material-ui/core';

const styles = {
  root: {
    flexGrow: 1,
    position: 'fixed',
    top: 0,
    left: 0,
    right: 0,
    zIndex: 1000,
  },
};

function PageSpinner(props) {
  const { classes, loading } = props;
  return loading ? (
    <div className={`${classes.root} page-spinner`}>
      <LinearProgress />
    </div>
  ) : null;
}

PageSpinner.propTypes = {
  loading: PropTypes.bool,
  classes: PropTypes.object.isRequired,
};

PageSpinner.defaultProps = {
  loading: false,
};

export default withStyles(styles)(PageSpinner);
