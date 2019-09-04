import React from 'react';
import debounce from 'lodash/debounce';

/* eslint-disable */
import interceptClient from 'interceptClient';
/* eslint-enable */

function withEvaluation(WrappedComponent, debounceTime = 200) {
  return class extends React.Component {
    constructor(props) {
      super(props);
      this.handleResponse = this.handleResponse.bind(this);
      this.saveEvaluation = debounce(this.saveEvaluation, debounceTime).bind(this);
      this.state = {
        evaluation: {
          loading: false,
          response: [],
          saved: false,
          errors: null,
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
     * Make an API request for room availabilty.
     * @param {Object} query
     * @param {Array} query.rooms
     *  An array of room ids
     * @param {Number} query.duration
     *  The duration of a reservation in minutes. Availability will be determined based on the
     *  the duration of the desired reservation.
     * @param {Date} query.end
     *  The start of a span of time to check for availablity within.
     * @param {Date} query.end
     *  The end of a span of time to check for availablity within.
     *
     * @return {Promise}
     *  The Promise returned from the fetch.
     */
    saveEvaluation(values, callback = res => res) {
      this.setState({
        evaluation: {
          ...this.state.evaluation,
          loading: true,
          shouldUpdate: false,
        },
      });

      return fetch('/api/event/evaluate', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify(values),
      })
        .then(res => res.text())
        .then(res => callback(this.handleResponse(res)))
        .catch((e) => {
          if (this.mounted) {
            console.log(e);
            this.setState({
              evaluation: {
                ...this.state.evaluation,
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
          evaluation: {
            ...this.state.evaluation,
            loading: false,
            response: JSON.parse(res),
            saved: true,
          },
        });
      }

      return res;
    };

    render() {
      return (
        <WrappedComponent
          evaluation={this.state.evaluation}
          saveEvaluation={this.saveEvaluation}
          {...this.props}
        />
      );
    }
  };
}

export default withEvaluation;
