// React
import React from 'react';
import PropTypes from 'prop-types';

// Intercept
import interceptClient from 'interceptClient';

// Redux
import { connect } from 'react-redux';

// Lodash
import get from 'lodash/get';

// Material UI
import CircularProgress from '@material-ui/core/CircularProgress';

const { select } = interceptClient;

class EntityStatus extends React.PureComponent {
  constructor(props) {
    super(props);
    this.getMessage = this.getMessage.bind(this);
  }

  getMessage(state, status) {
    const messages = this.props.messages;
    let message = messages.default;

    if (!status) {
      return message;
    }

    if (state.syncing) {
      message = messages.syncing[status];
      return message;
    }

    if (state.error) {
      message = messages.error[status];
      return message;
    }

    if (state.dirty) {
      message = messages.dirty[status];
      return message;
    }

    if (state.saved) {
      message = messages.saved[status];
      return message;
    }

    return message;
  }

  render() {
    const { entity, statusPath } = this.props;
    const message = this.getMessage(entity.state, get(entity, statusPath));

    return (
      <div className="entity-status">
        <p className="entity-status__message">{message}</p>
        {entity.state.syncing && <CircularProgress size={50} />}
      </div>
    );
  }
}

EntityStatus.propTypes = {
  type: PropTypes.string.isRequired,
  id: PropTypes.string.isRequired,
  statusPath: PropTypes.string.isRequired,
  entity: PropTypes.instanceOf(Object).isRequired,
  messages: PropTypes.shape({
    default: PropTypes.string,
    syncing: PropTypes.object,
    error: PropTypes.object,
    saved: PropTypes.object,
  }).isRequired,
};

const mapStateToProps = (state, ownProps) => {
  const entityId = select.getIdentifier(ownProps.type, ownProps.id);
  const entity = select.record(entityId)(state);

  return {
    entity,
  };
};

export default connect(mapStateToProps)(EntityStatus);
