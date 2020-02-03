/* eslint-disable react/no-multi-comp */

import React from 'react';
import PropTypes from 'prop-types';
import RoomDetail from './RoomDetail';
// import RoomDetailActions from './RoomDetailActions';

import { withStyles } from '@material-ui/core/styles';
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

class RoomDetailDialog extends React.Component {
  render() {
    const { classes, close, onClose, open, id } = this.props;

    return (
      <Dialog close={close} onClose={onClose} open={open} keepMounted>
        <div className={classes.main}>
          {id ? <RoomDetail id={id} /> : <div />}
        </div>
        <div className={classes.footer}>
        {/* <RoomDetailActions id={id} /> */}
        </div>
      </Dialog>
    );
  }
}

RoomDetailDialog.propTypes = {
  classes: PropTypes.object.isRequired,
  onClose: PropTypes.func,
  open: PropTypes.bool,
  id: PropTypes.string,
};

RoomDetailDialog.defaultProps = {
  open: false,
  onClose: null,
  id: null,
};

export default withStyles(styles)(RoomDetailDialog);
