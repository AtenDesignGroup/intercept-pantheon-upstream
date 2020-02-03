import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

import get from 'lodash/get';
import map from 'lodash/map';

/* eslint-disable */
import interceptClient from 'interceptClient';
/* eslint-enable */

/* eslint-disable */
import ButtonRegister from 'intercept/ButtonRegister';
import EvaluationWidget from './EvaluationWidget';

import CriteriaWidget from './CriteriaWidget';
import withEvaluation from './withEvaluation';
import { Button } from '@material-ui/core';

const { api, select } = interceptClient;
const c = interceptClient.constants;

// Application States
const IDLE = 'idle';
const IN_PROGRESS = 'inProgress';
const SAVING = 'saving';
const SAVED = 'saved';
const LOADING = 'loading';
const COMPLETE = 'complete';
const ERROR = 'error';

// Evaluation Position
const LIKE = '1';
const NUETRAL = null;
const DISLIKE = '0';

class EventCustomerEvaluationApp extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      state: IDLE,
      value: {
        event: props.eventId,
        user: props.user.uuid,
        evaluation: NUETRAL,
        evaluation_criteria: [],
      },
    };

    this.onSubmit = this.onSubmit.bind(this);
  }

  componentDidMount() {
    if (!this.props.eventTypesInitialized) {
      this.props.fetchEventType();
    }
    if (!this.props.criteriaInitialized) {
      this.props.fetchEvaluationCriteria();
    }
  }

  onSubmit() {
    this.setState({ state: LOADING });
    this.props.saveEvaluation(this.state.value, (res) => {
      this.setState({ state: SAVED });
    });
  }

  getCriteriaOptions = () => {
    const evaluation = get(this, 'state.value.evaluation') || NUETRAL;
    const { eventType } = this.props;
    let options = [];

    // If there is no eventType or no evaluation, return.
    if (!eventType || evaluation === NUETRAL) {
      return options;
    }

    // Determine which field to use based on the evaluation.
    const criteriaProp =
      evaluation === LIKE ? 'field_evaluation_criteria_pos' : 'field_evaluation_criteria_neg';

    const criteria = get(eventType, `relationships.${criteriaProp}`);

    // If there is no criteria, return.
    if (!criteria) {
      return options;
    }

    options = map(criteria, item => ({
      key: item.id,
      value: get(item, 'attributes.name'),
    }));

    return options;
  };

  updateValue = key => (value) => {
    this.setState({
      value: {
        ...this.state.value,
        [key]: value,
      },
    });
  };

  updateEval = (value) => {
    this.setState({
      value: {
        ...this.state.value,
        evaluation: value,
        evaluation_criteria: [],
      },
    });
  };

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
    const { eventId } = this.props;
    const { state, value } = this.state;

    if (state === SAVED) {
      return (
        <div className="evaluation__eval-widget">
          <h3 className="evaluation__widget-label">Thank you for your feedback!</h3>
          {this[value.evaluation === '1' ? 'likeIcon' : 'dislikeIcon']('#00AFD0')}
        </div>
      );
    }

    const evaluation = (
      <EvaluationWidget
        disabled={false}
        onChange={this.updateEval}
        value={this.state.value.evaluation}
        name={eventId}
        likeIcon={this.likeIcon}
        dislikeIcon={this.dislikeIcon}
      />
    );

    const criteria = (
      <CriteriaWidget
        options={this.getCriteriaOptions()}
        onChange={this.updateValue('evaluation_criteria')}
        value={this.state.value.evaluation_criteria}
        name={'evaluation_criteria'}
      />
    );

    const submit = (
      <Button
        variant={'contained'}
        size="small"
        color="primary"
        className={''}
        disabled={this.state.value.evaluation === NUETRAL}
        onClick={this.onSubmit}
      >
        {'Submit'}
      </Button>
    );

    return (
      <div className="evaluation__app">
        {evaluation}
        <div className="evaluation__criteria">
          {criteria}
          {submit}
        </div>
      </div>
    );
  }
}

EventCustomerEvaluationApp.propTypes = {
  event: PropTypes.object,
  eventId: PropTypes.string.isRequired,
  eventTypeId: PropTypes.string,
  eventType: PropTypes.object,
  registrations: PropTypes.array,
  fetchEventType: PropTypes.func.isRequired,
  saveEvaluation: PropTypes.func.isRequired,
  user: PropTypes.object,
};

EventCustomerEvaluationApp.defaultProps = {
  event: null,
  eventTypeId: null,
  eventType: null,
  registrations: [],
  user: null,
};

const mapStateToProps = (state, ownProps) => ({
  event: select.record(select.getIdentifier(c.TYPE_EVENT, ownProps.eventId))(state),
  eventType: select.bundle(select.getIdentifier(c.TYPE_EVENT_TYPE, ownProps.eventTypeId))(state),
  registrations: select.eventRegistrationsByEventByUser(ownProps.eventId, ownProps.user.uuid)(
    state,
  ),
  eventTypesInitialized:
    select.recordsAreLoading(c.TYPE_EVENT_TYPE)(state) ||
    select.recordsUpdated(c.TYPE_EVENT_TYPE)(state) !== null,
  criteriaInitialized:
    select.recordsAreLoading(c.TYPE_EVALUATION_CRITERIA)(state) ||
    select.recordsUpdated(c.TYPE_EVALUATION_CRITERIA)(state) !== null,
});

const mapDispatchToProps = dispatch => ({
  fetchEventType: () => {
    dispatch(
      api[c.TYPE_EVENT_TYPE].fetchAll({
        filters: {
          status: {
            value: 1,
            path: 'status',
          },
        },
        fields: {
          [c.TYPE_EVENT_TYPE]: [
            'name',
            'field_evaluation_criteria_neg',
            'field_evaluation_criteria_pos',
          ],
        },
      }),
    );
  },
  fetchEvaluationCriteria: () => {
    dispatch(
      api[c.TYPE_EVALUATION_CRITERIA].fetchAll({
        filters: {
          status: {
            value: 1,
            path: 'status',
          },
        },
        fields: {
          [c.TYPE_EVALUATION_CRITERIA]: ['name'],
        },
      }),
    );
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(withEvaluation(EventCustomerEvaluationApp));
