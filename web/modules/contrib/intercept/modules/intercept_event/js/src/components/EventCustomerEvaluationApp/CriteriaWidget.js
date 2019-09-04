import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import interceptClient from 'interceptClient';

import InputCheckboxes from 'intercept/Input/InputCheckboxes';

const { api, select } = interceptClient;
const c = interceptClient.constants;

class CriteriaWidget extends React.PureComponent {
  render() {
    const { options, onChange, disabled, value, name } = this.props;

    if (options.length <= 0) {
      return null;
    }

    return (
      <InputCheckboxes
        name={name}
        onChange={onChange}
        value={value}
        options={options}
        label={'Tell us Why'}
        className="evaluation__criteria-widget"
        labelProps={{
          className: 'evaluation__widget-label',
        }}
      />
    );
  }
}

CriteriaWidget.propTypes = {
  options: PropTypes.arrayOf(
    PropTypes.shape({
      key: PropTypes.string,
      value: PropTypes.string,
    }),
  ),
  value: PropTypes.array,
  onChange: PropTypes.func,
  disabled: PropTypes.bool,
};

CriteriaWidget.defaultProps = {
  options: [],
  value: [],
  onChange: console.log,
  disabled: false,
};

export default CriteriaWidget;
