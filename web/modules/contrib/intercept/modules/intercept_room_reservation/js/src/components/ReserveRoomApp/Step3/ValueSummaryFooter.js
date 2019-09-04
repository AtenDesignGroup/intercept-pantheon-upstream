import React from 'react';
import PropTypes from 'prop-types';

class ValueSummaryFooter extends React.PureComponent {
  render() {
    return (
      <div className={`value-summary__footer value-summary__footer--${this.props.level}`}>
        <p>{this.props.message}</p>
      </div>
    );
  }
}

ValueSummaryFooter.propTypes = {
  message: PropTypes.string.isRequired,
  level: PropTypes.string,
};

ValueSummaryFooter.defaultProps = {
  level: 'error',
};

export default ValueSummaryFooter;
