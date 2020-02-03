/* eslint-disable react/no-multi-comp */

import React from 'react';
import PropTypes from 'prop-types';
import { withStyles } from '@material-ui/core/styles';
import EventSummary from 'intercept/EventSummary';

import LoadingIndicator from 'intercept/LoadingIndicator';
import EventSummaryActions from './EventSummaryActions';

import { Dialog } from '@material-ui/core';

const styles = theme => ({
  root: {
    display: 'flex',
    flexDirection: 'column',
  },
  main: {
    overflowY: 'auto',
  },
  footer: {
    margin: 0,
  },
});

class EventSummaryDialog extends React.PureComponent {
  render() {
    const { classes, close, onClose, open, id, loading } = this.props;
    if (!id) {
      return null;
    }

    let content = null;

    if (loading) {
      content = (
        <div style={{ padding: '2em' }}>
          <LoadingIndicator loading />
        </div>
      );
    }
    else {
      content = (
        <React.Fragment>
          <div className={classes.main}>{id ? <EventSummary id={id} /> : <div />}</div>
          <div className={classes.footer}>
            <EventSummaryActions id={id} />
          </div>
        </React.Fragment>
      );
    }

    return (
      <Dialog close={close} onClose={onClose} open={open} keepMounted>
        {content}
      </Dialog>
    );
  }
}

EventSummaryDialog.propTypes = {
  classes: PropTypes.object.isRequired,
  onClose: PropTypes.func,
  loading: PropTypes.bool,
  open: PropTypes.bool,
  id: PropTypes.string,
};

EventSummaryDialog.defaultProps = {
  open: false,
  onClose: null,
  id: null,
  loading: false,
};

export default withStyles(styles)(EventSummaryDialog);
