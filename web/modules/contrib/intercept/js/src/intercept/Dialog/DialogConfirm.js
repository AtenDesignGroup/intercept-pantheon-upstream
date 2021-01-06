/* eslint-disable react/no-array-index-key */
import React from 'react';
import debounce from 'lodash/debounce';
import PropTypes from 'prop-types';

import {
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogContentText,
  DialogTitle,
  withMobileDialog,
} from '@material-ui/core';

// Click debounce options to prevent click happy multi-confirmations.
const DEBOUNCE_WAIT = 1000;
const DEBOUNCE_OPTIONS = {
  trailing: false,
  leading: true,
};

class DialogConfirm extends React.PureComponent {
  render() {
    const {
      fullScreen,
      open,
      children,
      confirmText,
      cancelText,
      disableBackdropClick,
      disableEscapeKeyDown,
      heading,
      onBackdropClick,
      onConfirm,
      onCancel,
      text,
    } = this.props;

    const textContent = text && [].concat(text);

    return (
      <div>
        <Dialog
          fullScreen={fullScreen}
          open={open}
          onClose={this.handleClose}
          aria-labelledby="responsive-dialog-title"
          onBackdropClick={onBackdropClick}
          disableBackdropClick={disableBackdropClick}
          disableEscapeKeyDown={disableEscapeKeyDown}
        >
          <DialogTitle id="responsive-dialog-title">{heading}</DialogTitle>
          { text || children
            ? <DialogContent>
              {textContent && textContent.map((content, index) =>
                <DialogContentText key={index}>{content}</DialogContentText>,
              )}
              {children}
            </DialogContent>
            : null
          }
          <DialogActions>
            {onCancel &&
              cancelText && (
              <Button onClick={debounce(onCancel, DEBOUNCE_WAIT, DEBOUNCE_OPTIONS)} color="secondary">
                {cancelText}
              </Button>
            )}
            {onConfirm &&
              confirmText && (
              <Button onClick={debounce(onConfirm, DEBOUNCE_WAIT, DEBOUNCE_OPTIONS)} color="primary" autoFocus>
                {confirmText}
              </Button>
            )}
          </DialogActions>
        </Dialog>
      </div>
    );
  }
}

DialogConfirm.propTypes = {
  fullScreen: PropTypes.bool.isRequired,
  confirmText: PropTypes.string,
  cancelText: PropTypes.string,
  disableBackdropClick: PropTypes.bool,
  disableEscapeKeyDown: PropTypes.bool,
  heading: PropTypes.string,
  text: PropTypes.oneOfType([
    PropTypes.string,
    PropTypes.arrayOf(PropTypes.string),
  ]),
  onConfirm: PropTypes.func,
  onCancel: PropTypes.func,
  onClose: PropTypes.func,
  open: PropTypes.bool,
};

DialogConfirm.defaultProps = {
  confirmText: 'Yes',
  cancelText: 'No',
  disableBackdropClick: false,
  disableEscapeKeyDown: false,
  heading: 'Are you sure?',
  text: null,
  onConfirm: null,
  onCancel: null,
  onClose: null,
  open: false,
};

export default withMobileDialog()(DialogConfirm);
