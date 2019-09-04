import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

// Lodash
import get from 'lodash/get';

/* eslint-disable */
import interceptClient from 'interceptClient';

import ButtonReservationAction from 'intercept/ButtonReservationAction';
import DialogConfirm from 'intercept/Dialog/DialogConfirm';
import LoadingIndicator from 'intercept/LoadingIndicator';
import ReservationStatus from 'intercept/ReservationStatus';
/* eslint-enable */

const { actions, api, select, session, utils } = interceptClient;
const c = interceptClient.constants;

class RoomReservationActionButtonApp extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      open: false,
      disableBackdropClick: true,
      disableEscapeKeyDown: true,
      dialogProps: {},
    };
    this.actionButton = this.actionButton.bind(this);
  }

  componentDidMount() {
    this.props.fetchReservation(this.props.entityId);
  }

  getActions = (status) => {
    const { cancel, deny, approve, request } = this;
    const isManager = utils.userIsManager();

    switch (status) {
      case 'requested':
        return isManager ? [approve(), deny()] : [cancel()];
      case 'denied':
        return isManager ? [approve(), cancel()] : null;
      case 'approved':
        return isManager ? [deny(), cancel()] : [cancel()];
      case 'canceled':
        return [request()];
      default:
        return null;
    }
  };

  getDialogProps = (status) => {
    switch (status) {
      case 'requested':
        return {
          status: 'requested',
          heading: 'Are you sure you want to rerequest this reservation?',
        };
      case 'denied':
        return {
          status: 'denied',
          heading: 'Are you sure you want to deny this reservation?',
        };
      case 'approved':
        return {
          status: 'approved',
          heading: 'Are you sure you want to approve this reservation?',
        };
      case 'canceled':
        return {
          status: 'canceled',
          heading: 'Are you sure you want to cancel this reservation?',
        };
      default:
        return null;
    }
  };

  openDialog = (status) => {
    this.setState({ open: true, dialogProps: this.getDialogProps(status) });
  }

  closeDialog = () => {
    this.setState({ open: false });
  }

  confirmDialog = (status) => {
    this.props.setStatusTo(status);
    this.closeDialog();
  }

  cancel = () => this.actionButton({
    status: 'canceled',
    label: 'Cancel',
  });

  deny = () => this.actionButton({
    status: 'denied',
    label: 'Deny',
  });

  request = () => this.actionButton({
    status: 'requested',
    label: 'Rerequest',
  });

  approve = () => this.actionButton({
    status: 'approved',
    label: 'Approve',
    variant: 'raised',
  });

  actionButton({ status, label, variant }) {
    const { record, entityId } = this.props;

    return record ? (
      <ButtonReservationAction
        entityId={entityId}
        type={c.TYPE_ROOM_RESERVATION}
        record={record}
        text={label}
        onClick={() => this.openDialog(status)}
        key={status}
        variant={variant}
      />
    ) : null;
  }

  dialog = () => {
    const { dialogProps } = this.state;
    return (
      <DialogConfirm
        {...dialogProps}
        open={this.state.open}
        onClose={this.onDialogClose}
        onCancel={this.closeDialog}
        onBackdropClick={null}
        disableEscapeKeyDown={this.state.disableEscapeKeyDown}
        disableBackdropClick={this.state.disableBackdropClick}
        onConfirm={() => this.confirmDialog(dialogProps.status)}
        confirmText="Yes"
        cancelText="No"
      />
    );
  };

  render() {
    const { record, isLoading } = this.props;
    const status = get(record, 'data.attributes.field_status') || this.props.status;

    const buttons = isLoading ? (
      <LoadingIndicator loading={isLoading} size={20} />
    ) : (
      this.getActions(status)
    );

    return (
      <div className="reservation-register-button__inner">
        <ReservationStatus status={status} syncing={get(record, 'state.syncing')} />
        {buttons}
        {this.dialog()}
      </div>
    );
  }
}

RoomReservationActionButtonApp.propTypes = {
  // Passed Props
  entityId: PropTypes.string.isRequired,
  type: PropTypes.string.isRequired,
  status: PropTypes.string.isRequired,
  // Connect
  fetchReservation: PropTypes.func.isRequired,
  isLoading: PropTypes.bool,
  setStatusTo: PropTypes.func.isRequired,
  record: PropTypes.object,
};

RoomReservationActionButtonApp.defaultProps = {
  record: null,
  isLoading: false,
};

const mapStateToProps = (state, ownProps) => ({
  record: select.record(select.getIdentifier(c.TYPE_ROOM_RESERVATION, ownProps.entityId))(state),
  isLoading:
    select.recordsAreLoading(c.TYPE_ROOM_RESERVATION)(state) ||
    select.recordIsLoading(c.TYPE_ROOM_RESERVATION, ownProps.entityId)(state),
});

const mapDispatchToProps = (dispatch, ownProps) => {
  const uuid = ownProps.entityId;
  const { type } = ownProps;

  const save = () => {
    session
      .getToken()
      .then((token) => {
        dispatch(api[type].sync(uuid, { headers: { 'X-CSRF-Token': token } }));
      })
      .catch((e) => {
        console.log('Unable to save Reservation', e);
      });
  };

  const setStatus = (status, record) => {
    const { data } = record;
    data.attributes.field_status = status;
    dispatch(actions.edit(data, type, uuid));
  };

  return {
    setStatusTo: (status, record) => {
      setStatus(status, record);
      save(type);
    },
    fetchReservation: (id) => {
      dispatch(
        // @todo: Add support for fetching a single entity rather than fetching all filtered by uuid.
        api[c.TYPE_ROOM_RESERVATION].fetchAll({
          filters: {
            uuid: {
              value: id,
              path: 'uuid',
            },
          },
        }),
      );
    },
  };
};

function mergeProps(stateProps, dispatchProps, ownProps) {
  return {
    ...ownProps,
    ...stateProps,
    ...dispatchProps,
    setStatusTo: status => dispatchProps.setStatusTo(status, stateProps.record),
  };
}

export default connect(
  mapStateToProps,
  mapDispatchToProps,
  mergeProps,
)(RoomReservationActionButtonApp);
