import React from 'react';
import PropTypes from 'prop-types';
import map from 'lodash/map';

class EvaluationSummary extends React.PureComponent {
  render() {
    const { icon, label, criteria, count } = this.props;

    const criteriaItems = map(criteria, item => (
      <li className="evaluation-summary__criteria-item" key={item.id}>
        <span className="evaluation-summary__criteria-label">{item.label}:</span>{' '}
        <span className="evaluation-summary__criteria-count">{item.count}</span>
      </li>
    ));

    const criteriaList =
      criteriaItems.length > 0 ? (
        <ul className="evaluation-summary__criteria-list">{criteriaItems}</ul>
      ) : null;

    return (
      <div className={'evaluation-summary'}>
        <div className="evaluation-summary__overview">
          {icon}
          <p className="evaluation-summary__overview-text">
            <span className="evaluation-summary__overview-label visually-hidden">{`${label} count`}</span>{' '}
            <span className="evaluation-summary__overview-equals">{'='}</span>{' '}
            <span className="evaluation-summary__overview-count">{count}</span>
          </p>
        </div>
        <div className="evaluation-summary__criteria">{criteriaList}</div>
      </div>
    );
  }
}

EvaluationSummary.propTypes = {
  label: PropTypes.string.isRequired,
  count: PropTypes.number.isRequired,
  icon: PropTypes.object.isRequired,
  criteria: PropTypes.object,
};

EvaluationSummary.defaultProps = {
  criteria: {},
};

export default EvaluationSummary;
