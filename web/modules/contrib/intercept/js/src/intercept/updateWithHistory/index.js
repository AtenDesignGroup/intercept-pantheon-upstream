import React, { Component } from 'react';
import interceptClient from 'interceptClient';

function getDisplayName(WrappedComponent) {
  return WrappedComponent.displayName || WrappedComponent.name || 'Component';
}

//
// Higher order component that forces an update
// when history is updated.
//
function updateWithHistory(WrappedComponent) {
  // ...and returns another component...
  class UpdateWithHistory extends Component {
    componentDidMount() {
      // force an update if the URL changes
      interceptClient.history.listen(() => {
        this.forceUpdate();
      });
    }

    render() {
      return <WrappedComponent {...this.props} />;
    }
  }

  UpdateWithHistory.displayName = `UpdateWithHistory(${getDisplayName(WrappedComponent)})`;
  return UpdateWithHistory;
}

export default updateWithHistory;
