import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import DialogConfirm from 'intercept/Dialog/DialogConfirm';
import interceptClient from 'interceptClient';

import { connect } from 'react-redux';

import { Button } from '@material-ui/core';

const { constants, api, select } = interceptClient;
const c = constants;

const ActionProperties = {
  cancel: {
    status: 'canceled',
    text: 'Confirm cancellation',
    heading: 'Confirm cancel',
  },
  deny: {
    status: 'denied',
    text: 'Confirm deny',
    heading: 'Confirm deny',
  },
  approve: {
    status: 'approved',
    text: 'Confirm approval',
    heading: 'Confirm approval',
  },
};

class ButtonActions extends PureComponent {
  constructor(props) {
    super(props);
    this.state = {
      open: false,
      action: false,
    };
  }

  onClose () {
    this.onCancel();
  }

  onCancel () {
    this.setState({
      open: false,
    });
  }

  onClick (action) {
    this.setState({
      open: true,
      text: this.getActionProperties(action).text,
      heading: this.getActionProperties(action).text,
      action: action,
    });
  }

  getActionProperties(action) {
    return ActionProperties[action];
  }

  render() {
    const { actions } = this.props;

    return (
      <div>
        <div>
          {actions.map(action => (
            <Button
              key={action}
              onClick={this.onClick.bind(this, action)}
            >{action}
            </Button>
          ))}
        </div>
        <DialogConfirm
          open={this.state.open}
          onClose={this.onClose.bind(this)}
          onConfirm={this.props.onConfirm.bind(this)}
          onCancel={this.onCancel.bind(this)}
          text={this.state.text}
          heading={this.state.heading}
        />
      </div>
    );
  }
}

ButtonActions.propTypes = {
  id: PropTypes.string.isRequired,
  actions: PropTypes.array.isRequired,
  reservation: PropTypes.object,
};

const mapStateToProps = (state, ownProps) => ({
  reservation: select.record({ type: c.TYPE_ROOM_RESERVATION, id: ownProps.id })(state),
});

const mapDispatchToProps = (dispatch, ownProps) => ({
  onConfirm (meh) {
    const data = this.props.reservation.data;
    data.attributes.field_status = this.getActionProperties(this.state.action).status;
    dispatch(interceptClient.actions.edit(data, c.TYPE_ROOM_RESERVATION, this.props.id));
    dispatch(interceptClient.api[c.TYPE_ROOM_RESERVATION].sync(this.props.id));
    this.setState({ open: false });
  },
});

export default connect(mapStateToProps, mapDispatchToProps)(ButtonActions);
