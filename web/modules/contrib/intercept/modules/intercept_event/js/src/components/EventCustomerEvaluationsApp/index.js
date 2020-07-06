import React from 'react';
import PropTypes from 'prop-types';

import get from 'lodash/get';

/* eslint-disable */
import interceptClient from 'interceptClient';
/* eslint-enable */
import LoadingIndicator from 'intercept/LoadingIndicator';

import EvaluationSummary from './EvaluationSummary';
import withEvaluations from './withEvaluations';

// Application States
const IDLE = 'idle';
const LOADING = 'loading';

// Evaluation Position
const LIKE = '1';
const NUETRAL = null;
const DISLIKE = '0';

class EventCustomerEvaluationsApp extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      state: IDLE,
    };
  }

  componentDidMount() {
    this.props.fetchEvaluations(this.props.eventId);
  }

  likeIcon = color => (
    <svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
      <title>Like</title>
      <g fill="none" fillRule="evenodd">
        <circle stroke={color} strokeWidth="5" cx="30" cy="30" r="27.5" />
        <circle fill={color} cx="20.5" cy="24.5" r="3.5" />
        <circle fill={color} cx="39.5" cy="24.5" r="3.5" />
        <path
          d="M19 39c7.7 6.4 14.4 6.4 22 0"
          stroke={color}
          strokeWidth="5"
          strokeLinecap="round"
        />
      </g>
    </svg>
  );

  dislikeIcon = color => (
    <svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
      <title>Dislike</title>
      <g fill="none" fillRule="evenodd">
        <circle stroke={color} strokeWidth="5" cx="30" cy="30" r="27.5" />
        <circle fill={color} cx="20.5" cy="24.5" r="3.5" />
        <circle fill={color} cx="39.5" cy="24.5" r="3.5" />
        <path d="M19 43.9c7.2-6 13.7-7 22 0" stroke={color} strokeWidth="5" strokeLinecap="round" />
      </g>
    </svg>
  );

  render() {
    const { eventId, evaluations } = this.props;

    if (evaluations === null || !evaluations.loaded) {
      return (<LoadingIndicator loading />);
    }

    const likes = get(evaluations, `response.${eventId}.1`);
    const dislikes = get(evaluations, `response.${eventId}.0`);

    return (
      <div className="customer-evaluations__app">
        <EvaluationSummary {...likes} icon={this.likeIcon('#747481')} label={'Like'} />
        <EvaluationSummary {...dislikes} icon={this.dislikeIcon('#747481')} label={'Dislike'} />
      </div>
    );
  }
}

EventCustomerEvaluationsApp.propTypes = {
  eventId: PropTypes.string.isRequired,
  fetchEvaluations: PropTypes.func.isRequired,
  evaluations: PropTypes.object,
};

EventCustomerEvaluationsApp.defaultProps = {
  evaluations: {},
};

export default withEvaluations(EventCustomerEvaluationsApp);
