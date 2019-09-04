import React from 'react';
import debounce from 'lodash/debounce';

function withUserStatus(WrappedComponent, debounceTime = 50) {
  return class extends React.Component {
    constructor(props) {
      super(props);
      this.handleResponse = this.handleResponse.bind(this);
      this.fetchUserStatus = debounce(this.fetchUserStatus, debounceTime).bind(this);
      this.state = {
        userStatus: {
          loading: false,
          initialized: false,
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
     * Make an API request for user's room reservation status.
     * @param {Function} callback
     *  A callback to fire after a successful response.
     *
     * @return {Promise}
     *  The Promise returned from the fetch.
     */
    fetchUserStatus(callback = res => res) {
      if (window.drupalSettings.user.uid === 0) {
        this.setState({
          userStatus: {
            ...this.state.userStatus,
            loading: false,
            initialized: true,
            exceededLimit: true,
          },
        });

        return Promise.resolve({
          exceededLimit: true,
          limit: 0,
        });
      }

      this.setState({
        userStatus: {
          ...this.state.userStatus,
          loading: true,
          initialized: true,
        },
      });

      return fetch('/api/rooms/user/status', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
      })
        .then(res => res.text())
        .then(res => callback(this.handleResponse(res)))
        .catch((e) => {
          if (this.mounted) {
            console.log(e);
            this.setState({
              userStatus: {
                ...this.state.userStatus,
                loading: false,
              },
            });
          }
        });
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
          userStatus: {
            ...this.state.userStatus,
            loading: false,
            ...JSON.parse(res),
          },
        });
      }

      return res;
    };

    render() {
      return (
        <WrappedComponent
          userStatus={this.state.userStatus}
          fetchUserStatus={this.fetchUserStatus}
          {...this.props}
        />
      );
    }
  };
}

export default withUserStatus;
