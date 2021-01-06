import React from 'react';
import debounce from 'lodash/debounce';

import get from 'lodash/get';

/* eslint-disable */
import interceptClient from 'interceptClient';
/* eslint-enable */

const { api, constants } = interceptClient;
const c = constants;


function withEventRegistrations(WrappedComponent, debounceTime = 200) {
  return class extends React.Component {
    constructor(props) {
      super(props);
      this.handleResponse = this.handleResponse.bind(this);
      this.fetchEventRegistrations = debounce(this.fetchEventRegistrations, debounceTime).bind(
        this,
      );
      this.state = {
        registrations: {
          loading: false,
          shouldUpdate: false,
          data: null,
        },
      };
    }

    componentDidMount() {
      // Store the mounted state of this component so we know when to handle fetch responses.
      this.mounted = true;
    }

    componentWillUnmount() {
      // Store the unmounted state of this component so we know when to avoid
      // handling fetch response.
      this.mounted = false;
    }

    /**
     * Handles the response from a fetch request.
     * This is used internally by the component to update the state based on the
     * fetch response.
     */
    handleResponse = (res) => {
      // Verify this component is still mounted when the response is received.
      // We do this to avoid setting state on an unmounted component.
      if (this.mounted) {
        this.setState({
          registrations: {
            ...this.state.registrations,
            loading: false,
            data: get(JSON.parse(res), 'data.attributes'),
            shouldUpdate: false,
          },
        });
      }

      return res;
    };

    render() {
      return (
        <WrappedComponent
          registrations={this.state.registrations}
          fetchEventRegistrations={this.fetchEventRegistrations}
          {...this.props}
        />
      );
    }
  };
}

export default withEventRegistrations;
